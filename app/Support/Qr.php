<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class Qr
{
    /** Kode QR (SVG) yang bisa dipindai, mis. untuk tautan verifikasi sertifikat. */
    public static function svg(string $text, int $size = 120): string
    {
        $style = new RendererStyle($size, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(15, 42, 77)));
        $svg = (new Writer(new ImageRenderer($style, new SvgImageBackEnd())))->writeString($text);

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }
}
