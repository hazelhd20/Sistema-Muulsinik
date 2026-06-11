<?php

$dir = __DIR__ . '/resources/views';

function processDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            processDir($path);
        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            processFile($path);
        }
    }
}

function processFile($path) {
    $content = file_get_contents($path);
    $original = $content;

    // Matches: <i data-lucide="icon-name" class="w-5 h-5"></i>
    // Ignores dynamic ones like data-lucide="{{ $var }}" for now
    $pattern = '/<i\s+data-lucide="([a-zA-Z0-9\-]+)"([^>]*)><\/i>/';
    
    $content = preg_replace_callback($pattern, function($matches) {
        $iconName = $matches[1];
        $attributes = $matches[2];
        return "<x-lucide-{$iconName}{$attributes} />";
    }, $content);

    // Some icons might have self closing tags <i data-lucide="icon-name" />
    $pattern2 = '/<i\s+data-lucide="([a-zA-Z0-9\-]+)"([^>]*)\/>/';
    $content = preg_replace_callback($pattern2, function($matches) {
        $iconName = $matches[1];
        $attributes = $matches[2];
        return "<x-lucide-{$iconName}{$attributes} />";
    }, $content);

    // Dynamic ones using {{ }} inside data-lucide
    $patternDynamic = '/<i\s+data-lucide="\{\{\s*(.+?)\s*\}\}"([^>]*)><\/i>/';
    $content = preg_replace_callback($patternDynamic, function($matches) {
        $var = trim($matches[1]);
        $attributes = $matches[2];
        return "<x-dynamic-component :component=\"'lucide-' . {$var}\"{$attributes} />";
    }, $content);

    if ($original !== $content) {
        file_put_contents($path, $content);
        echo "Updated: $path\n";
    }
}

processDir($dir);
echo "Done.\n";
