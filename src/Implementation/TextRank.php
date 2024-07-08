<?php

declare(strict_types=1);

namespace KeywordGenerator\Implementation;

use KeywordGenerator\KeywordGenerator;

class TextRank implements KeywordGenerator
{
    public function generateKeywords(string $text): array
    {
        return [];
    }
}
