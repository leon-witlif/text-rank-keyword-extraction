<?php

declare(strict_types=1);

namespace KeywordGenerator\Struct;

use KeywordGenerator\Enum\Tag;

class TaggedWord
{
    public function __construct(
        public string $word,
        public Tag $tag
    ) {
    }
}
