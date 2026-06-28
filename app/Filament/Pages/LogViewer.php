<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class LogViewer extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-bug-ant';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int    $navigationSort  = 99;
    protected static ?string $title           = 'Log Viewer';
    protected static string  $view            = 'filament.pages.log-viewer';

    public string  $selectedFile  = '';
    public string  $selectedLevel = 'all';
    public int     $lineLimit     = 500;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        $files = $this->logFiles();
        if ($files) {
            $this->selectedFile = array_key_first($files);
        }
    }

    public function logFiles(): array
    {
        $path  = storage_path('logs');
        $files = File::files($path);

        $result = [];
        foreach ($files as $file) {
            if ($file->getExtension() === 'log') {
                $result[$file->getPathname()] = $file->getFilename();
            }
        }

        // newest first by modification time
        uasort($result, fn ($a, $b) => filemtime(array_search($b, $result)) <=> filemtime(array_search($a, $result)));

        return $result;
    }

    public function getLogs(): array
    {
        if (! $this->selectedFile || ! file_exists($this->selectedFile)) {
            return [];
        }

        $content = $this->tailFile($this->selectedFile, $this->lineLimit);
        $entries = $this->parse($content);

        if ($this->selectedLevel !== 'all') {
            $entries = array_filter($entries, fn ($e) => strtolower($e['level']) === $this->selectedLevel);
        }

        return array_reverse(array_values($entries));
    }

    private function tailFile(string $path, int $lines): string
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return '';
        }

        fseek($handle, 0, SEEK_END);
        $size     = ftell($handle);
        $chunk    = 8192;
        $buffer   = '';
        $newlines = 0;
        $target   = $lines + 1;
        $pos      = $size;

        while ($pos > 0 && $newlines < $target) {
            $read    = min($chunk, $pos);
            $pos    -= $read;
            fseek($handle, $pos);
            $buffer   = fread($handle, $read) . $buffer;
            $newlines = substr_count($buffer, "\n");
        }

        fclose($handle);

        $lineArr = explode("\n", $buffer);
        return implode("\n", array_slice($lineArr, max(0, count($lineArr) - $lines)));
    }

    private function parse(string $content): array
    {
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\] (\w+)\.(\w+): (.*?)(?=^\[|\z)/ms';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $entries = [];
        foreach ($matches as $m) {
            $entries[] = [
                'datetime' => $m[1],
                'env'      => $m[2],
                'level'    => strtolower($m[3]),
                'message'  => trim($m[4]),
            ];
        }

        return $entries;
    }

    public function clearLog(): void
    {
        if ($this->selectedFile && file_exists($this->selectedFile)) {
            file_put_contents($this->selectedFile, '');
        }
    }

    public function levels(): array
    {
        return [
            'all'       => 'All Levels',
            'emergency' => 'Emergency',
            'alert'     => 'Alert',
            'critical'  => 'Critical',
            'error'     => 'Error',
            'warning'   => 'Warning',
            'notice'    => 'Notice',
            'info'      => 'Info',
            'debug'     => 'Debug',
        ];
    }
}
