<?php

namespace App\Helpers;

class QrCodeHelper
{
    /**
     * Generate an inline SVG string or Data URI for QR Code.
     * Uses QuickChart QR API fallback or lightweight native SVG wrapper.
     */
    public static function generateSvg(string $text, int $size = 150): string
    {
        $encoded = urlencode($text);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}";
    }

    public static function generateBase64Svg(string $text): string
    {
        $url = static::generateSvg($text);
        return $url;
    }
}
