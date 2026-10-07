<?php

namespace App\Logging;

use App\Support\ControllerLog;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Handler channel log 'controller': tiap catatan ditulis ke file milik controller yang sedang
 * berjalan, per tanggal (lihat App\Support\ControllerLog::path()).
 */
class ControllerLogHandler extends AbstractProcessingHandler
{
    /** @var array<string, StreamHandler> handler per file yang sudah dibuka */
    private array $streams = [];

    public function __construct(int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $path = ControllerLog::path();
        if (!isset($this->streams[$path])) {
            $stream = new StreamHandler($path, $this->level, true, 0664);
            $stream->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n", 'Y-m-d H:i:s', true, true));
            $this->streams[$path] = $stream;
        }
        $this->streams[$path]->handle($record);
    }

    public function close(): void
    {
        foreach ($this->streams as $stream) {
            $stream->close();
        }
        $this->streams = [];
        parent::close();
    }
}
