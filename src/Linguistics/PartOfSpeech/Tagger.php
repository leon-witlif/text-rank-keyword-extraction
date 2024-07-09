<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\PartOfSpeech;

use KeywordGenerator\Struct\TaggedWord;

interface Tagger
{
    /**
     * @return TaggedWord[]
     */
    public function tag(string $text): array;
}
