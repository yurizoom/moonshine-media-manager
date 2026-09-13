<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use Illuminate\Support\Carbon;

class MediaFormatter
{
    /**
     * Format a byte count into a human-readable string.
     *
     * Exponent is derived via log() and clamped to the highest supported
     * unit; the epsilon guards against float undershoot on exact powers
     * of 1024 (e.g. log(1048576, 1024) evaluating to 1.9999…).
     */
    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        if ($bytes <= 0) {
            return '0 B';
        }

        $exponent = (int) min(floor(log($bytes, 1024) + 1e-9), \count($units) - 1);
        $value = $bytes / (1024 ** $exponent);

        return round($value, 2).' '.$units[$exponent];
    }

    /**
     * Format a unix timestamp using the application timezone.
     */
    public static function formatTimestamp(int $timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
    }
}
