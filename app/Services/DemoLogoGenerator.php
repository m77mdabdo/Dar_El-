<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates simple monogram-style logos locally via GD for fictional demo
 * brands. Stock photo APIs (Pexels) have no way to return an actual logo
 * for a made-up brand name — a logo is a graphic-design asset, not a
 * photograph — so this renders a clean colored badge with the brand's
 * initials instead of pretending a random photo is a downloaded logo.
 */
class DemoLogoGenerator
{
    /** Palette drawn from the site's own maroon/gold/rose luxury identity. */
    protected const PALETTE = [
        [122, 28, 46],   // maroon
        [166, 124, 0],   // gold
        [58, 48, 41],    // espresso
        [110, 72, 74],   // rose dust
        [40, 60, 55],    // deep emerald
        [76, 47, 79],    // plum
    ];

    protected const SIZE = 400;

    public function generate(string $initials, string $directory, string $filenamePrefix): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $size = self::SIZE;
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        [$r, $g, $b] = self::PALETTE[abs(crc32($initials)) % count(self::PALETTE)];
        $bg = imagecolorallocate($image, $r, $g, $b);
        imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), $size, $size, $bg);

        $font = resource_path('fonts/cairo/Cairo-Bold.ttf');
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = Str::upper(Str::limit($initials, 2, ''));

        if (is_file($font)) {
            $fontSize = 140;
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = abs($box[4] - $box[0]);
            $textHeight = abs($box[5] - $box[1]);
            $x = (int) (($size - $textWidth) / 2);
            $y = (int) (($size + $textHeight) / 2);
            imagettftext($image, $fontSize, 0, $x, $y, $white, $font, $text);
        }

        ob_start();
        imagepng($image);
        $body = ob_get_clean();

        $path = trim($directory, '/').'/'.$filenamePrefix.'-'.Str::random(8).'.png';
        Storage::disk('public')->put($path, $body);

        return $path;
    }
}
