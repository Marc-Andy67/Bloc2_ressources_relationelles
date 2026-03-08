<?php

$templatesDir = 'c:/Users/marca/resources_relationnelles/templates';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templatesDir));

$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'twig') {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        // Find all class="..." or class='...' attributes
        $content = preg_replace_callback('/class=(["\'])(.*?)\1/is', function($matches) {
            $classStr = $matches[2];
            $quote = $matches[1];
            
            // Does this string have a hardcoded text color?
            $hasLightText = preg_match('/\btext-(slate|gray|zinc)-[4-9]00\b/', $classStr);
            // Does it already have a dark mode text color?
            $hasDarkText = preg_match('/\bdark:text-/', $classStr);
            
            if ($hasLightText && !$hasDarkText) {
                // If the light text is very dark (800, 900), it should be white in dark mode
                // If it's medium (500, 600, 700), it should be slate-200 or 300
                if (preg_match('/\btext-(slate|gray|zinc)-(800|900)\b/', $classStr)) {
                    $classStr .= ' dark:text-white';
                } else {
                    $classStr .= ' dark:text-slate-200';
                }
            }
            
            // Just to be absolutely sure about contrast, let's also fix any dark:text-slate-700/800/900 
            // that might have been accidentally created or left behind.
            $classStr = preg_replace('/\bdark:text-(slate|gray|zinc)-(600|700|800|900)\b/', 'dark:text-slate-200', $classStr);
            
            return 'class=' . $quote . $classStr . $quote;
        }, $content);
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
            $count++;
        }
    }
}

echo "Done! Updated $count files.\n";
