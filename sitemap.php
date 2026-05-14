<?php
/**
 * WealthMetre – sitemap.php
 * Dynamic XML sitemap with:
 *  - Auto-detected blog posts (real lastmod from file mtime)
 *  - All city × product landing pages
 *  - Important static/SEO pages
 *  - www-only canonical URLs
 *  - Sensible priority + changefreq values
 *
 * Access: https://www.wealthmetre.com/sitemap.php
 * Submit to Search Console: https://www.wealthmetre.com/sitemap.php
 */

// ── Config ────────────────────────────────────────────────
define('BASE_URL',   'https://www.wealthmetre.com');
define('PUBLIC_DIR', __DIR__);   // public_html root

// ── Force www redirect ────────────────────────────────────
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host === 'wealthmetre.com') {
    header('Location: https://www.wealthmetre.com/sitemap.php', true, 301);
    exit;
}

// ── Output headers ─────────────────────────────────────────
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

// ── Helper: format date for lastmod ───────────────────────
function lastmod(string $filePath): string {
    if (file_exists($filePath)) {
        return date('Y-m-d', filemtime($filePath));
    }
    return date('Y-m-d');
}

// ── Helper: build <url> block ─────────────────────────────
function url(string $loc, string $lastmod, string $changefreq, float $priority): string {
    return sprintf(
        "\t<url>\n\t\t<loc>%s</loc>\n\t\t<lastmod>%s</lastmod>\n\t\t<changefreq>%s</changefreq>\n\t\t<priority>%.1f</priority>\n\t</url>\n",
        htmlspecialchars(BASE_URL . $loc, ENT_XML1),
        $lastmod,
        $changefreq,
        $priority
    );
}

// ── City × Product combinations ───────────────────────────
$products = [
    'home-loan',
    'lap',
    'business-loan',
    'personal-loan',
    'car-loan',
    'balance-transfer',
];

$cities = [
    // Priority 1 — main cities (highest traffic)
    'jaipur'   => 0.9,
    'jodhpur'  => 0.8,
    'udaipur'  => 0.8,
    'kota'     => 0.8,
    // Priority 2 — secondary cities
    'ajmer'    => 0.7,
    'bikaner'  => 0.7,
    'alwar'    => 0.7,
    'bhilwara' => 0.6,
];

// ── Auto-scan blog folder ─────────────────────────────────
function getBlogPosts(): array {
    $blogDir = PUBLIC_DIR . '/blog/';
    $posts   = [];

    if (!is_dir($blogDir)) return $posts;

    $files = glob($blogDir . '*.html');
    if (!$files) return $posts;

    foreach ($files as $file) {
        $filename = basename($file);
        // Skip index.html — handled as /blog/ separately
        if ($filename === 'index.html') continue;

        $slug     = pathinfo($filename, PATHINFO_FILENAME);
        $modified = date('Y-m-d', filemtime($file));

        $posts[] = [
            'slug'     => $slug,
            'path'     => "/blog/{$filename}",
            'modified' => $modified,
        ];
    }

    // Sort newest first
    usort($posts, fn($a, $b) => strcmp($b['modified'], $a['modified']));
    return $posts;
}

// ── Important static/SEO pages ────────────────────────────
// Add any important root-level pages here manually
$staticPages = [
    // [path, priority, changefreq]
    ['/',                         1.0, 'weekly'],
    ['/blog/',                    0.8, 'weekly'],

    // High-intent Jaipur specific pages
    ['/loan-jda-property-jaipur.html',   0.8, 'monthly'],
    ['/loan-low-cibil-jaipur.html',      0.8, 'monthly'],

    // Customer-facing pages
    ['/customer-portal.html',            0.6, 'monthly'],

    // About / trust pages (add once created)
    // ['/about-us.html',                0.5, 'monthly'],
    // ['/contact.html',                 0.5, 'monthly'],
];

// ── Generate XML ──────────────────────────────────────────
$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 1. Static pages
foreach ($staticPages as [$path, $priority, $changefreq]) {
    // Resolve file path for lastmod
    if ($path === '/') {
        $filePath = PUBLIC_DIR . '/index.html';
    } else {
        $filePath = PUBLIC_DIR . $path;
    }

    // Skip if file doesn't exist (prevents dead URLs in sitemap)
    if ($path !== '/' && !file_exists($filePath)) {
        continue;
    }

    $xml .= url($path, lastmod($filePath), $changefreq, $priority);
}

// 2. City × Product landing pages
foreach ($cities as $city => $basePriority) {
    foreach ($products as $product) {
        $path     = "/{$product}-{$city}.html";
        $filePath = PUBLIC_DIR . '/seo-page.php'; // all served by seo-page.php

        // Use seo-page.php mtime as lastmod for all these pages
        $modified = lastmod($filePath);

        // Jaipur pages get slightly higher priority
        $priority = ($city === 'jaipur') ? min(0.9, $basePriority + 0.05) : $basePriority;

        // Home loan and LAP are highest intent
        if (in_array($product, ['home-loan', 'lap'])) {
            $priority = min(0.95, $priority + 0.05);
        }

        $xml .= url($path, $modified, 'monthly', round($priority, 1));
    }
}

// 3. Blog posts (auto-detected)
$blogPosts = getBlogPosts();
foreach ($blogPosts as $post) {
    $xml .= url($post['path'], $post['modified'], 'monthly', 0.7);
}

$xml .= '</urlset>';

echo $xml;
