<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminUserCreated;
use App\Support\AdminUsers;
use App\Support\LoginEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Menu admin Admin Users: daftar user administrator dan tambah user baru (mgr.all_login),
 * sekaligus membuat grup baru (mgr.administrator) bila diperlukan. Logika: App\Support\AdminUsers.
 * Hanya grup dengan admincall 'admin' (config admin_users.manager_admincall); lainnya 403.
 */
class AdminUserController extends Controller
{
    public function callAction($method, $parameters)
    {
        abort_unless(AdminUsers::canManage(), 403);
        return $this->{$method}(...array_values($parameters));
    }

    public function index()
    {
        return view('admin.users.index', [
            'users'           => AdminUsers::users(),
            'groups'          => AdminUsers::groups(),
            'defaultPassword' => AdminUsers::defaultPassword(),
            'mailEnabled'     => (bool) config('admin_users.mail.enabled'),
        ]);
    }

    /**
     * Ganti email user administrator (termasuk email sendiri): email, with_tenants (1 = akun tenant
     * dengan email yang sama ikut diubah), current_password (password admin yang sedang login, wajib).
     * Validasi email sama dengan View Profile (App\Support\LoginEmail).
     */
    public function email(Request $request, int $id)
    {
        $user = AdminUsers::find($id);
        if (!$user) {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/users.not_found')], 404);
        }
        $new = LoginEmail::normalize($request->input('email'));
        if ($new === LoginEmail::normalize($user->email)) {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/users.email_same')], 422);
        }
        $error = LoginEmail::validate($user->email, $new);
        if ($error !== null) {
            return response()->json(['status' => 'Failed', 'pesan' => $error], 422);
        }
        // konfirmasi password admin yang sedang login
        $password = (string) $request->input('current_password');
        if (trim($password) === '') {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/users.err_admin_password_required')], 422);
        }
        if (!AdminUsers::currentPasswordMatches($password)) {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/users.err_admin_password_wrong')], 422);
        }

        try {
            AdminUsers::changeEmail($user, $new, $request->boolean('with_tenants'));
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/users.err_save', ['message' => $e->getMessage()])], 500);
        }
        return response()->json(['status' => 'OK', 'pesan' => __('admin/users.email_changed', ['name' => $user->name, 'old' => $user->email, 'new' => $new])]);
    }

    /**
     * Tambah user: name, email, handphone, group (id grup | 'new' + group_name, group_code),
     * password_mode ('default' | 'custom') + password, password_confirmation.
     */
    public function store(Request $request)
    {
        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $phone = trim((string) $request->input('handphone'));
        $groupInput = (string) $request->input('group');
        $groupName = trim((string) $request->input('group_name'));
        $groupCode = trim((string) $request->input('group_code'));
        $customPassword = $request->input('password_mode') === 'custom';
        $password = (string) $request->input('password');

        $errors = [];
        if ($name === '') {
            $errors[] = __('admin/users.err_name');
        } elseif (mb_strlen($name) > AdminUsers::MAX_NAME) {
            $errors[] = __('admin/users.err_too_long', ['field' => __('admin/users.name'), 'max' => AdminUsers::MAX_NAME]);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > AdminUsers::MAX_EMAIL) {
            $errors[] = __('admin/users.err_email');
        } elseif (AdminUsers::emailExists($email)) {
            $errors[] = __('admin/users.err_email_exists');
        }
        if ($phone !== '' && (mb_strlen($phone) > AdminUsers::MAX_PHONE || !preg_match('/^[0-9+\-\s()]+$/', $phone))) {
            $errors[] = __('admin/users.err_phone');
        }

        // grup: pilih yang ada atau buat baru
        $group = null;
        if ($groupInput === 'new') {
            $groupCode = $groupCode !== '' ? $groupCode : AdminUsers::codeFrom($groupName);
            if ($groupName === '') {
                $errors[] = __('admin/users.err_group_name');
            } elseif (mb_strlen($groupName) > AdminUsers::MAX_NAME) {
                $errors[] = __('admin/users.err_too_long', ['field' => __('admin/users.group_name'), 'max' => AdminUsers::MAX_NAME]);
            } elseif (AdminUsers::groupNameExists($groupName)) {
                $errors[] = __('admin/users.err_group_exists');
            }
            if ($groupName !== '' && !preg_match('/^[a-z0-9_]{2,' . AdminUsers::MAX_CODE . '}$/', $groupCode)) {
                $errors[] = __('admin/users.err_group_code', ['max' => AdminUsers::MAX_CODE]);
            } elseif ($groupName !== '' && AdminUsers::groupCodeExists($groupCode)) {
                $errors[] = __('admin/users.err_group_code_exists');
            }
            $group = ['name' => $groupName, 'code' => $groupCode];
        } elseif (ctype_digit($groupInput) && AdminUsers::groups()->has((int) $groupInput)) {
            $group = ['id' => (int) $groupInput];
        } else {
            $errors[] = __('admin/users.err_group');
        }

        // password: default (config admin_users.default_password, '@Admintwp') atau diisi
        if ($customPassword) {
            if (mb_strlen($password) < AdminUsers::MIN_PASSWORD) {
                $errors[] = __('admin/users.err_password', ['min' => AdminUsers::MIN_PASSWORD]);
            } elseif ($password !== (string) $request->input('password_confirmation')) {
                $errors[] = __('admin/users.err_password_confirm');
            }
        } else {
            $password = AdminUsers::defaultPassword();
            if ($password === '') {
                $errors[] = __('admin/users.err_default_password');
            }
        }

        if ($errors) {
            return response()->json(['status' => 'Failed', 'pesan' => implode("\n", $errors)], 422);
        }

        try {
            $result = AdminUsers::create(['name' => $name, 'email' => $email, 'handphone' => $phone], $group, $password);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/users.err_save', ['message' => $e->getMessage()])], 500);
        }

        $groupLabel = $result['group_created'] ? $groupName : AdminUsers::groups()->get($result['group_id'])->name;
        $message = __($result['group_created'] ? 'admin/users.saved_new_group' : 'admin/users.saved', ['name' => $name, 'group' => $groupLabel]);
        $mail = $this->sendWelcome($name, $email, $groupLabel, $customPassword ? null : $password);
        if ($mail !== null) {
            $message .= ' ' . $mail;
        }
        return response()->json(['status' => 'OK', 'pesan' => $message]);
    }

    /**
     * Email pemberitahuan ke user baru lewat SMTP Gmail (MAIL_* di .env); hanya bila ADMIN_USER_MAIL=true.
     * Gagal kirim tidak membatalkan user (sudah tersimpan), hanya dicatat & diberitahukan.
     * Kembali: teks status email untuk pesan sukses, atau null bila email nonaktif.
     */
    private function sendWelcome(string $name, string $email, string $group, ?string $password): ?string
    {
        if (!config('admin_users.mail.enabled')) {
            return null;
        }
        try {
            Mail::mailer(config('admin_users.mail.mailer', 'smtp'))
                ->to($email, $name)
                ->send(new AdminUserCreated($name, $email, $group, $password, url('/')));
            return __('admin/users.mail_sent', ['email' => $email]);
        } catch (Throwable $e) {
            Log::warning('Email user admin baru gagal dikirim: ' . $e->getMessage(), ['email' => $email]);
            return __('admin/users.mail_failed');
        }
    }
}
