<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\PartOfSpeech;

interface Tagger
{
    public function tag(string $text): array;
}
