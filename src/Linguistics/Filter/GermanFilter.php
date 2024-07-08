<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Filter;

class GermanFilter implements Filter
{
    protected string $characterRegex = '/[^a-z0-9 .,äöüÄÖÜß]/';

    public function filter(string $text): string
    {
        return preg_replace($this->characterRegex, '', $text);
    }
}
