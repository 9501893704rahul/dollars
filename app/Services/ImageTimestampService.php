<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Typography\FontFactory;
use Throwable;

class ImageTimestampService
{
    public static function overlay(string $absolutePath, \DateTimeInterface $when): void
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return; // silently skip if file not found or unreadable
        }

        try {
            $manager = new ImageManager(new Driver());
            $image   = $manager->read($absolutePath);

            // Position: lower RIGHT corner with padding
            $x = $image->width() - 20;  // 20px from right edge
            $y = $image->height() - 20; // 20px from bottom edge

            $text = $when->format('Y-m-d H:i:s');

            $image->text($text, $x, $y, function (FontFactory $font) {
                // v3 API — size:int, color:string, align/valign strings, stroke(color,width)
                $font->size(28);
                $font->color('#ffffff');
                $font->align('right');   // Align text to the right
                $font->valign('bottom'); // Align text to the bottom
                $font->stroke('#000000', 2); // Black stroke for visibility

                // Optional: load a TTF if GD lacks good default font
                // $font->filename(resource_path('fonts/Inter-Regular.ttf'));
            });

            // Save with quality (GD ignores on PNG but safe)
            $image->save($absolutePath, 85);
        } catch (Throwable $e) {
            // Avoid breaking uploads; log for later inspection
            report($e);
        }
    }
}
