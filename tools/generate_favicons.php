<?php
declare(strict_types=1);

// Genera los favicons/PNG a partir del diseño de favicon.svg (fondo #0a0808,
// degradé #ff1f4d -> #ff7a3d, "#" al centro). Correr cuando cambie el
// branding: php tools/generate_favicons.php

function draw_icon(int $size): \GdImage
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    // Degradé diagonal en tiras, como en el favicon.svg.
    $steps = max(40, $size);
    for ($i = 0; $i < $steps; $i++) {
        $t = $i / $steps;
        $r = 255;
        $g = (int) (31 + (122 - 31) * $t);
        $b = (int) (77 + (61 - 77) * $t);
        $color = imagecolorallocate($img, $r, $g, $b);
        $x = (int) ($size * $t) - (int) ($size * 0.3);
        $w = (int) ($size * 0.5);
        imagefilledpolygon($img, [
            $x, 0,
            $x + $w, 0,
            $x - (int) ($size * 0.3), $size,
            $x - (int) ($size * 0.3) - $w, $size,
        ], $color);
    }

    $dark = imagecolorallocate($img, 21, 4, 7);
    $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    $fontSize = $size * 0.52;

    if (file_exists($font)) {
        $box = imagettfbbox($fontSize, 0, $font, '#');
        $textWidth = abs($box[2] - $box[0]);
        $textHeight = abs($box[7] - $box[1]);
        $x = (int) (($size - $textWidth) / 2);
        $y = (int) (($size + $textHeight) / 2);
        imagettftext($img, $fontSize, 0, $x, $y, $dark, $font, '#');
    } else {
        imagestring($img, 5, (int) ($size * 0.4), (int) ($size * 0.4), '#', $dark);
    }

    return $img;
}

$targets = [
    'favicon-16.png' => 16,
    'favicon-32.png' => 32,
    'apple-touch-icon.png' => 180,
    'android-chrome-192x192.png' => 192,
    'android-chrome-512x512.png' => 512,
];

foreach ($targets as $filename => $size) {
    $img = draw_icon($size);
    imagepng($img, __DIR__ . '/../web/assets/' . $filename);
    imagedestroy($img);
    echo "OK: web/assets/{$filename} ({$size}x{$size})\n";
}
