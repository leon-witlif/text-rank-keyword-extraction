<?php

declare(strict_types=1);

namespace KeywordGenerator;

use KeywordGenerator\Struct\Keyword;

interface KeywordGenerator
{
    /**
     * @return Keyword[]
     */
    public function generateKeywords(string $text): array;
}