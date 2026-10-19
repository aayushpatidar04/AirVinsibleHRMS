<?php

namespace App\Support;

/**
 * Small helpers shared by the Letter of Intent and Offer Letter PDFs.
 */
class LetterHelper
{
    public const DEFAULT_LOGO = 'assets/images/logo.png';

    public const DEFAULT_FOOTER = '300, 3rd Floor, Mangal City Mall, Vijay Nagar, Indore, M.P. – 452011 Mail ID: - hr@airvincible.com';

    /**
     * Company logo as an inline data URI, or null when no file exists.
     *
     * Tries the configured COMPANY_LOGO_PATH first, then the standard
     * public/assets/images/logo.png. The image is embedded rather than
     * linked because dompdf's setOptions() resets its allowed directories,
     * which blocks files under public/ and breaks on Windows paths.
     */
    public static function logoPath(): ?string
    {
        return self::dataUri(self::existingPublicFile([
            config('company.logo_path'),
            self::DEFAULT_LOGO,
        ]));
    }

    /** HR signature image as an inline data URI, or null. */
    public static function signaturePath(): ?string
    {
        return self::dataUri(self::existingPublicFile([
            config('company.hr_signature_path'),
        ]));
    }

    /** Letterhead footer line printed on every page. */
    public static function footer(): string
    {
        return config('company.letter_footer') ?: self::DEFAULT_FOOTER;
    }

    /**
     * Format an amount with Indian digit grouping (1,56,000).
     * Whole numbers have no decimals, fractional amounts show two.
     * Zero or empty values render as "-".
     */
    public static function inr(float|int|string|null $value, bool $dashForZero = true): string
    {
        $number = round((float) $value, 2);

        if ($number == 0.0 && $dashForZero) {
            return '-';
        }

        $negative = $number < 0;
        $number = abs($number);

        $hasFraction = abs($number - floor($number)) > 0.004;
        [$whole, $fraction] = explode('.', number_format($number, 2, '.', ''));

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $whole = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last3;
        }

        return ($negative ? '-' : '') . $whole . ($hasFraction ? '.' . $fraction : '');
    }

    private static function dataUri(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $info = @getimagesize($path);
        $contents = @file_get_contents($path);

        if ($info === false || $contents === false) {
            return null;
        }

        return 'data:' . $info['mime'] . ';base64,' . base64_encode($contents);
    }

    /**
     * @param  array<int, string|null>  $candidates  paths relative to public/
     */
    private static function existingPublicFile(array $candidates): ?string
    {
        foreach ($candidates as $relative) {
            if (!$relative) {
                continue;
            }

            $path = public_path($relative);

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
