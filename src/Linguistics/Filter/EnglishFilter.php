<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Filter;

class EnglishFilter implements Filter
{
    protected string $characterRegex = '/[^a-z0-9 .,]/';

    public function filter(string $text): string
    {
        return preg_replace($this->characterRegex, '', $text);
    }
}
