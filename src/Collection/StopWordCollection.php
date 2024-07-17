<?php

declare(strict_types=1);

namespace KeywordGenerator\Collection;

use KeywordGenerator\FileSystem\FileSystem;

class StopWordCollection
{
    public static function fromFile(string $filePath): self
    {
        $lines = FileSystem::readFileContents($filePath);

        return new self($lines);
    }

    public function __construct(
        /** @var string[] */
        private array $stopWords
    ) {
    }

    public function merge(StopWordCollection ...$others): void
    {
        foreach ($others as $other) {
            $this->stopWords = array_merge($this->stopWords, $other->stopWords);
        }
    }

    public function contains(string $word): bool
    {
        return in_array($word, $this->stopWords, true);
    }

    public function toArray(): array
    {
        return $this->stopWords;
    }
}
