<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\PartOfSpeech;

use KeywordGenerator\Enum\Tag;
use KeywordGenerator\Linguistics\Dictionary\Dictionary;
use KeywordGenerator\Struct\TaggedWord;

abstract class Tagger
{
    protected Dictionary $dictionary;

    /** @var TaggedWord[] */
    protected array $taggedWords;

    public function __construct(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
        $this->taggedWords = [];
    }

    /**
     * @return TaggedWord[]
     */
    public function tag(string $text): array
    {
        $this->splitText($text);
        $this->tagWords();

        return $this->taggedWords;
    }

    protected function splitText(string $text): void
    {
        $parts = explode(' ', $text);

        foreach ($parts as $part) {
            if (!strlen($part)) {
                continue;
            }

            if (str_ends_with($part, ',') || str_ends_with($part, '.')) {
                array_push(
                    $this->taggedWords,
                    new TaggedWord(substr($part, 0, -1), Tag::UNTAGGED),
                    new TaggedWord(substr($part, -1), Tag::UNTAGGED)
                );
            } else {
                $this->taggedWords[] = new TaggedWord($part, Tag::UNTAGGED);
            }
        }
    }

    abstract protected function tagWords(): void;
}
