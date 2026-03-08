<?php

$templatesDir = 'c:/Users/marca/resources_relationnelles/templates';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templatesDir));

$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'twig') {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        // 1. Fix Light Mode text: Look for text-slate-XYZ not preceded by "dark:"
        // We use a negative lookbehind: (?<!dark:)text-slate-(\d00)
        // If 400 -> 600, 500 -> 700, 600 -> 800, 700 -> 900, 800 -> 900
        $content = preg_replace_callback('/(?<!dark:)text-slate-(\d00)/', function($matches) {
            $val = intval($matches[1]);
            if ($val === 400 || $val === 500) return 'text-slate-700';
            if ($val === 600) return 'text-slate-800';
            if ($val >= 700) return 'text-slate-900';
            return $matches[0];
        }, $content);
        
        // 2. Fix Dark Mode text: Look for dark:text-slate-XYZ
        // If 400, 500, 600, 700, 800, 900 -> make them 200, 100 or white.
        $content = preg_replace_callback('/dark:text-slate-(\d00)/', function($matches) {
            $val = intval($matches[1]);
            // If it was meant to be very subtle (like 600/700/800 originally), maybe 300/400.
            // But let's just make everything super readable. 
            // 800/900 -> 200 (was likely an error from previous script)
            return 'dark:text-slate-100'; 
        }, $content);
        
        // Let's also enforce title text to be white in dark mode and slate-900 in light mode
        // For text-slate-800 or 900 that represents headings
        $content = preg_replace('/(?<!dark:)text-slate-800 dark:text-slate-100/', 'text-slate-900 dark:text-white', $content);
        $content = preg_replace('/(?<!dark:)text-slate-900 dark:text-slate-100/', 'text-slate-900 dark:text-white', $content);

        // Also fix the case where dark:text-slate-800 was injected next to text-slate-800
        $content = preg_replace('/dark:text-slate-800/', 'dark:text-slate-200', $content);
        $content = preg_replace('/dark:text-slate-900/', 'dark:text-slate-200', $content);
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
            $count++;
        }
    }
}

echo "Done! Updated $count files.\n";
