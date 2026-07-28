<?php
declare(strict_types=1);

// Genera web/assets/og-image.png (1200x630) para las meta tags Open Graph/Twitter.
// Correr una sola vez (o cuando cambie el branding): php tools/generate_og_image.php

$width = 1200;
$height = 630;
$img = imagecreatetruecolor($width, $height);

$bg = imagecolorallocate($img, 10, 8, 8);
imagefill($img, 0, 0, $bg);

// Degradé diagonal rojo -> naranja, en los tonos de la marca.
$steps = 260;
for ($i = 0; $i < $steps; $i++) {
    $t = $i / $steps;
    $r = (int) (255 * 1.0);
    $g = (int) (31 + (122 - 31) * $t);
    $b = (int) (77 + (61 - 77) * $t);
    $color = imagecolorallocate($img, $r, max(0, $g), max(0, $b));
    $x = (int) ($width * $t) - 200;
    imagefilledpolygon($img, [
        $x, 0,
        $x + 420, 0,
        $x - 200, $height,
        $x - 620, $height,
    ], $color);
}

// Overlay oscuro para que el texto se lea bien.
$overlay = imagecolorallocatealpha($img, 8, 5, 5, 35);
imagefilledrectangle($img, 0, 0, $width, $height, $overlay);

$white = imagecolorallocate($img, 251, 238, 240);
$dim = imagecolorallocate($img, 245, 210, 210);

$fontCandidates = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
];
$fontRegularCandidates = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
];
$font = current(array_filter($fontCandidates, 'file_exists')) ?: null;
$fontRegular = current(array_filter($fontRegularCandidates, 'file_exists')) ?: null;
$hasFont = $font && $fontRegular;

if ($hasFont) {
    imagettftext($img, 64, 0, 80, 260, $white, $font, '# Chateanos');
    imagettftext($img, 30, 0, 82, 330, $dim, $fontRegular, 'Chatea en español, sin vueltas.');
    imagettftext($img, 22, 0, 82, 560, $dim, $fontRegular, 'webchat.chateanos.com');
} else {
    // Sin fuente TTF disponible: usamos la fuente built-in de GD como respaldo.
    imagestring($img, 5, 80, 260, '# Chateanos', $white);
    imagestring($img, 3, 82, 300, 'Chatea en espanol, sin vueltas.', $dim);
    imagestring($img, 2, 82, 560, 'webchat.chateanos.com', $dim);
}

imagepng($img, __DIR__ . '/../web/assets/og-image.png');
imagedestroy($img);

echo "OK: web/assets/og-image.png generado (" . $width . "x" . $height . ")\n";
