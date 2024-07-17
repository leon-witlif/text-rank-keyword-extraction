<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Lemma;

use KeywordGenerator\Struct\TaggedWord;

interface Lemmatizer
{
    /**
     * @return TaggedWord[]
     */
    public function lemmatize(TaggedWord ...$taggedWords): array;
}
