<?php

function wm_wrap_text($text, $font, $fontSize, $maxWidth) {
    $words = preg_split('/\s+/', trim($text));
    $lines = [];
    $line = '';

    foreach ($words as $word) {
        $test = trim($line . ' ' . $word);
        $box = imagettfbbox($fontSize, 0, $font, $test);
        $width = abs($box[2] - $box[0]);

        if ($width > $maxWidth && $line !== '') {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $test;
        }
    }

    if ($line !== '') $lines[] = $line;
    return $lines;
}

function wm_find_font() {
    $fonts = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf'
    ];

    foreach ($fonts as $f) {
        if (file_exists($f)) return $f;
    }

    return null;
}

function wm_create_social_creative($id, $title, $topic, $loanProduct, $city) {
    if (!extension_loaded('gd')) {
        throw new Exception('PHP GD extension not loaded');
    }

    $font = wm_find_font();
    if (!$font) {
        throw new Exception('No TTF font found on server');
    }

    $w = 1080;
    $h = 1080;
    $img = imagecreatetruecolor($w, $h);

    // Colors
    $white = imagecolorallocate($img, 255, 255, 255);
    $navy = imagecolorallocate($img, 10, 22, 50);
    $blue = imagecolorallocate($img, 32, 95, 255);
    $orange = imagecolorallocate($img, 255, 111, 32);
    $lightBlue = imagecolorallocate($img, 235, 242, 255);
    $soft = imagecolorallocate($img, 245, 247, 252);
    $gray = imagecolorallocate($img, 95, 105, 125);

    imagefilledrectangle($img, 0, 0, $w, $h, $soft);

    // Top brand band
    imagefilledrectangle($img, 0, 0, $w, 170, $navy);
    imagefilledellipse($img, 930, 80, 260, 260, $blue);
    imagefilledellipse($img, 1010, 150, 220, 220, $orange);

    // Logo mark substitute
    imagefilledroundrectangle($img, 70, 50, 125, 105, 12, $blue);
    imagefilledrectangle($img, 90, 62, 105, 95, $white);
    imagefilledrectangle($img, 78, 75, 118, 88, $white);

    imagettftext($img, 34, 0, 145, 92, $white, $font, 'WealthMetre');
    imagettftext($img, 22, 0, 145, 128, $lightBlue, $font, 'AI Loan Advisor');

    // Main card
    imagefilledroundrectangle($img, 70, 220, 1010, 830, 35, $white);

    // Category pill
    $pill = trim(($loanProduct ?: 'Loan') . ' • ' . ($city ?: 'Jaipur'));
    imagefilledroundrectangle($img, 105, 260, 540, 318, 29, $lightBlue);
    imagettftext($img, 24, 0, 130, 298, $blue, $font, $pill);

    // Headline
    $headline = trim($title ?: $topic ?: 'Find suitable loan options');
    $headline = preg_replace('/\s+/', ' ', $headline);
    $headline = mb_substr($headline, 0, 120);

    $lines = wm_wrap_text($headline, $font, 52, 820);
    $y = 405;
    $maxLines = 4;
    $i = 0;

    foreach ($lines as $line) {
        if ($i >= $maxLines) break;
        imagettftext($img, 52, 0, 110, $y, $navy, $font, $line);
        $y += 70;
        $i++;
    }

    // Bullet section
    $bulletY = 680;
    $bullets = [
        'Compare 140+ Banks & NBFCs',
        'Check property, income & CIBIL fit',
        'Ask Diva before applying'
    ];

    foreach ($bullets as $b) {
        imagefilledellipse($img, 125, $bulletY - 8, 18, 18, $orange);
        imagettftext($img, 25, 0, 155, $bulletY, $gray, $font, $b);
        $bulletY += 48;
    }

    // CTA footer
    imagefilledroundrectangle($img, 190, 890, 890, 980, 45, $orange);
    imagettftext($img, 34, 0, 300, 948, $white, $font, 'Check with Diva on WealthMetre');

    $outDir = '/var/www/wealthmetre/public_html/uploads/social';
    if (!is_dir($outDir)) mkdir($outDir, 0755, true);

    $file = $outDir . '/creative-' . (int)$id . '.jpg';

    imagejpeg($img, $file, 92);
    imagedestroy($img);

    chown($file, 'www-data');
    chmod($file, 0644);

    return 'https://wealthmetre.com/uploads/social/creative-' . (int)$id . '.jpg';
}

if (!function_exists('imagefilledroundrectangle')) {
    function imagefilledroundrectangle($im, $x1, $y1, $x2, $y2, $radius, $color) {
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
