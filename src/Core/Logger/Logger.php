<?php

namespace AuthSystem\Core\Logger;

use AuthSystem\Core\Config\Config;


class Logger
{
    private Config $config;
    private string $logFile;
    private string $logLevel;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->logFile = $config->get('log.file', 'storage/logs/app.log');
        $this->logLevel = $config->get('log.level', 'info');

        $this->ensureLogDirectory();
    }


    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }


    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }


    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }


    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }


    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }


    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $record = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'memory' => memory_get_usage(true),
            'file' => $this->getCallerFile(),
            'line' => $this->getCallerLine(),
        ];

        $this->writeLog($record);
    }


    private function shouldLog(string $level): bool
    {
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4];
        $currentLevel = $levels[$this->logLevel] ?? 1;
        $messageLevel = $levels[$level] ?? 1;

        return $messageLevel >= $currentLevel;
    }


    private function writeLog(array $record): void
    {
        $logEntry = sprintf(
            "[%s] %s: %s %s %s:%d\n",
            $record['timestamp'],
            $record['level'],
            $record['message'],
            !empty($record['context']) ? json_encode($record['context'], JSON_UNESCAPED_UNICODE) : '',
            basename($record['file']),
            $record['line']
        );

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);


        $this->rotateLogs();
    }


    private function rotateLogs(): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }

        $maxSize = 10 * 1024 * 1024;
        $maxFiles = $this->config->get('log.max_files', 30);

        if (filesize($this->logFile) > $maxSize) {
            $this->rotateLogFile($maxFiles);
        }
    }


    private function rotateLogFile(int $maxFiles): void
    {
        $logDir = dirname($this->logFile);
        $logName = basename($this->logFile, '.log');


        for ($i = $maxFiles - 1; $i > 0; $i--) {
            $oldFile = $logDir . '/' . $logName . '.' . $i . '.log';
            $newFile = $logDir . '/' . $logName . '.' . ($i + 1) . '.log';

            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }

        $rotatedFile = $logDir . '/' . $logName . '.1.log';
        rename($this->logFile, $rotatedFile);
    }


    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }


    private function getCallerFile(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return $trace[2]['file'] ?? 'unknown';
    }


    private function getCallerLine(): int
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return $trace[2]['line'] ?? 0;
    }
}