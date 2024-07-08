<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Filter;

interface Filter
{
    public function filter(string $text): string;
}
