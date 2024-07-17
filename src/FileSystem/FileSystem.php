<?php

declare(strict_types=1);

namespace KeywordGenerator\FileSystem;

class FileSystem
{
    public const FILES_DIRECTORY = __DIR__.'/../../files';

    /**
     * @return string[]
     */
    public static function readFileContents(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        $lines = [];

        while ($line = fgets($handle)) {
            $line = trim($line);

            if (str_starts_with($line, '#')) {
                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }
}
