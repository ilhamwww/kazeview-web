<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Mode worker AI indexing.
 *
 * Web (www-data) hanya menulis nama mode ke file request. Perubahan supervisor
 * dilakukan oleh systemd path unit `kazeview-worker-mode.path` (root) yang
 * mencocokkan isi file dengan whitelist. PHP tidak pernah menjalankan shell.
 */
class AiWorkerModeService
{
    public const MODES = [
        'default' => ['label' => 'Default', 'workers' => 1],
        'sunmori' => ['label' => 'Sunmori', 'workers' => 10],
    ];

    private function dir(): string
    {
        return storage_path('app/worker-mode');
    }

    public function requestedMode(): string
    {
        $mode = trim((string) @file_get_contents($this->dir() . '/mode'));

        return array_key_exists($mode, self::MODES) ? $mode : 'default';
    }

    /** Status terakhir yang benar-benar diterapkan oleh service root. */
    public function appliedStatus(): ?array
    {
        $raw = @file_get_contents($this->dir() . '/status.json');
        $data = $raw ? json_decode($raw, true) : null;

        return is_array($data) ? $data : null;
    }

    public function setMode(string $mode): void
    {
        if (! array_key_exists($mode, self::MODES)) {
            throw new InvalidArgumentException("Mode worker tidak valid: {$mode}");
        }

        $path = $this->dir() . '/mode';
        $tmp = $path . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, $mode . "\n");
        rename($tmp, $path); // atomic, memicu PathChanged
    }

    /** Hitung proses queue:work ai-indexing yang sedang hidup (baca /proc, tanpa shell). */
    public function runningWorkers(): int
    {
        $count = 0;

        foreach (glob('/proc/[0-9]*/cmdline') ?: [] as $file) {
            $cmd = @file_get_contents($file);
            if ($cmd !== false
                && str_contains($cmd, 'queue:work')
                && str_contains($cmd, '--queue=ai-indexing')) {
                $count++;
            }
        }

        return $count;
    }

    public function isPending(): bool
    {
        $applied = $this->appliedStatus();

        return ($applied['mode'] ?? null) !== $this->requestedMode();
    }
}
