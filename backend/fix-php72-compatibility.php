#!/usr/bin/env php
<?php

/**
 * Script to remove return type declarations from PHP files for PHP 7.2 compatibility
 * This script will remove `: JsonResponse`, `: array`, `: bool`, `: int`, `: string`, `: void` from method signatures
 */

$directory = __DIR__ . '/app/Http/Controllers';

function removeReturnTypes($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
    
    $count = 0;
    foreach ($phpFiles as $file) {
        $filePath = $file[0];
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Remove return type declarations from public/private/protected methods
        // Pattern: ): JsonResponse, ): array, ): bool, etc.
        $patterns = [
            '/\): JsonResponse\s*\n/m' => ")\n",
            '/\): array\s*\n/m' => ")\n",
            '/\): bool\s*\n/m' => ")\n",
            '/\): int\s*\n/m' => ")\n",
            '/\): string\s*\n/m' => ")\n",
            '/\): void\s*\n/m' => ")\n",
            '/\): float\s*\n/m' => ")\n",
            '/\): mixed\s*\n/m' => ")\n",
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "✓ Fixed: " . basename($filePath) . "\n";
            $count++;
        }
    }
    
    echo "\n✅ Total files fixed: $count\n";
}

echo "🔧 Removing return type declarations for PHP 7.2 compatibility...\n\n";
removeReturnTypes($directory);
echo "\n✅ Done!\n";
