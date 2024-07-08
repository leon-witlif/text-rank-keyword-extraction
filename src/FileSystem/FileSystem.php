<?php

declare(strict_types=1);

namespace KeywordGenerator\FileSystem;

class FileSystem
{
    /**
     * @return string[]
     */
    public static function readFileContents(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        $lines = [];

        while ($line = fgets($handle)) {
            $lines[] = $line;
        }

        return $lines;
    }
}
