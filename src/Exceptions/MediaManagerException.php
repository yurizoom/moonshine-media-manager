<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Exceptions;

/**
 * Thrown for expected, user-facing validation failures (bad MIME, blocked
 * extension, oversized upload, disallowed path, non-local disk, etc.).
 *
 * These are NOT reported to the error handler / logs — they represent bad
 * user input, not server faults. The controller surfaces the message to the
 * client as a 4xx response.
 */
class MediaManagerException extends \RuntimeException
{
}
