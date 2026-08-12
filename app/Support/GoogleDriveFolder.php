<?php

namespace App\Support;

final class GoogleDriveFolder
{
    public static function extractId(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9_-]+$/', $input)) {
            return $input;
        }

        $url = parse_url($input);

        if ($url === false) {
            return null;
        }

        $host = strtolower((string) ($url['host'] ?? ''));

        if (! in_array($host, [
            'drive.google.com',
            'www.drive.google.com',
        ], true)) {
            return null;
        }

        $path = rawurldecode((string) ($url['path'] ?? ''));

        if (preg_match(
            '~/(?:drive/(?:u/\d+/)?folders|folders)/([A-Za-z0-9_-]+)(?:/|$)~i',
            $path,
            $matches,
        )) {
            return $matches[1];
        }

        return null;
    }

    public static function isValid(?string $input): bool
    {
        return self::extractId($input) !== null;
    }

    public static function isGoogleDriveUrl(?string $input): bool
    {
        $input = trim((string) $input);

        if ($input === '') {
            return false;
        }

        $url = parse_url($input);

        if ($url === false) {
            return false;
        }

        $scheme = strtolower((string) ($url['scheme'] ?? ''));
        $host = strtolower((string) ($url['host'] ?? ''));

        return $scheme === 'https'
            && in_array($host, [
                'drive.google.com',
                'www.drive.google.com',
            ], true)
            && trim((string) ($url['path'] ?? ''), '/') !== '';
    }
}