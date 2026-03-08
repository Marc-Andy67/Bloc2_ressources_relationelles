<?php

$templatesDir = 'c:/Users/marca/resources_relationnelles/templates';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templatesDir));

$replacements = [
    // Light mode text (make darker)
    'text-slate-400 ' => 'text-slate-600 ',
    'text-slate-500 ' => 'text-slate-700 ',
    'text-slate-600 ' => 'text-slate-800 ',
    'text-slate-700 ' => 'text-slate-900 ',
    // Adding quote catchers for exact tailwind classes at the end of className strings
    'text-slate-400"' => 'text-slate-600"',
    'text-slate-500"' => 'text-slate-700"',
    'text-slate-600"' => 'text-slate-800"',
    'text-slate-700"' => 'text-slate-900"',
    
    // Dark mode text (make lighter/whiter)
    'dark:text-slate-500 ' => 'dark:text-slate-300 ',
    'dark:text-slate-400 ' => 'dark:text-slate-200 ',
    'dark:text-slate-300 ' => 'dark:text-slate-100 ',
    'dark:text-slate-200 ' => 'dark:text-white ',
    // Adding quote catchers
    'dark:text-slate-500"' => 'dark:text-slate-300"',
    'dark:text-slate-400"' => 'dark:text-slate-200"',
    'dark:text-slate-300"' => 'dark:text-slate-100"',
    'dark:text-slate-200"' => 'dark:text-white"',
];

$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'twig') {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
            $count++;
        }
    }
}

echo "Done! Updated $count files.\n";
