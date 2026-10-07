<?php

namespace App\Http\Middleware;

use App\Support\ControllerLog;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Catat setiap request ke log controller-nya (storage/logs/{tanggal}/{portal}/{Controller}.log):
 * method + URL, Controller@method, user, IP, status, durasi, input (rahasia disamarkan), dan hasil
 * JSON "gagal" (status Failed / Error true) sebagai WARNING walau HTTP 200.
 */
class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        try {
            $this->write($request, $response, $start);
        } catch (Throwable $e) {
            // log tidak boleh mengganggu request
        }

        return $response;
    }

    private function write(Request $request, Response $response, float $start): void
    {
        $status = $response->getStatusCode();
        $context = [
            'action' => ControllerLog::action(),
            'user'   => $this->user(),
            'ip'     => $request->ip(),
            'ms'     => (int) round((microtime(true) - $start) * 1000),
        ];

        $input = ControllerLog::sanitize($request->except(['_token', '_method']));
        if ($input) {
            $context['input'] = $input;
        }

        $result = $this->jsonResult($response);
        if ($result) {
            $context['result'] = $result['text'];
        }
        if ($response->isRedirection()) {
            $context['redirect'] = $response->headers->get('Location');
        }

        $level = match (true) {
            $status >= 500                       => 'error',
            $status >= 400 || ($result['failed'] ?? false) => 'warning',
            default                              => 'info',
        };

        Log::channel('controller')->log($level, $request->method() . ' /' . ltrim($request->path(), '/') . ' ' . $status, $context);
    }

    /** User yang login: admin (Tsemail) atau tenant (Tenemail), dengan nama. */
    private function user(): string
    {
        if (!Session::isStarted()) {
            return 'guest';
        }
        $email = Session::get('Tsemail') ?: Session::get('Tenemail');
        $name = Session::get('Tsuname') ?: Session::get('Tuname');
        if (!$email) {
            return 'guest';
        }
        return $name ? $email . ' (' . $name . ')' : $email;
    }

    /** Ringkasan respons JSON {status/Status, pesan/Pesan/message, Error}; failed = gagal menurut aplikasi. */
    private function jsonResult(Response $response): ?array
    {
        if (!$response instanceof JsonResponse || strlen((string) $response->getContent()) > 200000) {
            return null;
        }
        $data = $response->getData(true);
        if (!is_array($data) || array_is_list($data)) {
            return null;
        }
        $status = $data['status'] ?? $data['Status'] ?? null;
        $message = $data['pesan'] ?? $data['Pesan'] ?? $data['message'] ?? null;
        $error = $data['Error'] ?? $data['error'] ?? null;
        if ($status === null && $message === null && $error === null) {
            return null;
        }
        $failed = $error === true
            || (is_string($status) && in_array(strtolower($status), ['failed', 'fail', 'error', 'gagal'], true))
            || $status === false;
        $text = trim((is_bool($status) ? ($status ? 'true' : 'false') : (is_scalar($status) ? (string) $status : '')) . ' ' . (is_scalar($message) ? (string) $message : ''));
        return ['failed' => $failed, 'text' => mb_substr($text, 0, 500)];
    }
}
