<?php

declare(strict_types=1);

namespace KeywordGenerator\Struct;

class Keyword
{
    public function __construct(
        public string $keyword,
        public float $score
    ) {
    }
}
