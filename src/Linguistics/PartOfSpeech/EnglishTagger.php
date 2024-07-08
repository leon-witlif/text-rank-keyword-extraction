<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\PartOfSpeech;

use KeywordGenerator\Enum\Tag;
use KeywordGenerator\Struct\TaggedWord;

class EnglishTagger implements Tagger
{
    public function __construct(
        /** @var TaggedWord[] */
        private array $taggedWords = []
    ) {
    }

    public function tag(string $text): array
    {
        $this->splitText($text);
        $this->tagWords();

        return $this->taggedWords;
    }

    private function splitText(string $text): void
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

    private function tagWords(): void
    {
        foreach ($this->taggedWords as $taggedWord) {
            if ($taggedWord->word === ',') {
                $taggedWord->tag = Tag::COMMA;
                continue;
            }

            if ($taggedWord->word === '.') {
                $taggedWord->tag = Tag::PERIOD;
                continue;
            }

            if ($this->isAdverb($taggedWord->word)) {
                $taggedWord->tag = Tag::ADVERB;
                continue;
            }

            if ($this->isConjunction($taggedWord->word)) {
                $taggedWord->tag = Tag::CONJUNCTION;
                continue;
            }

            if ($this->isPreposition($taggedWord->word)) {
                $taggedWord->tag = Tag::PREPOSITION;
                continue;
            }

            if ($this->isDeterminer($taggedWord->word)) {
                $taggedWord->tag = Tag::DETERMINER;
                continue;
            }

            $taggedWord->tag = Tag::NOUN;
        }
    }

    /**
     * NN: Noun, singular or mass
     */
    private function isNoun(): bool { }

    /**
     * PRP: Personal pronoun (he, she, it, they)
     * PRP$: Possessive pronoun (his, its, mine, theirs)
     */
    private function isPronoun(): bool { }

    /**
     * JJ: Adjective
     */
    private function isAdjective(): bool { }

    /**
     * RB: Adverb
     */
    private function isAdverb(string $word): bool
    {
        return str_ends_with($word, 'ly');
    }

    /**
     * CC: Coordinating conjunction (and, but, or, so)
     */
    private function isConjunction(string $word): bool
    {
        return in_array($word, ['and', 'but', 'or', 'so']);
    }

    /**
     * IN: Preposition (on, in, across, after) or subordinating conjunction (because, although, before, since)
     */
    private function isPreposition(string $word): bool
    {
        return in_array($word, ['on', 'in', 'across', 'after', 'because', 'although', 'before', 'since']);
    }

    /**
     * UH: Interjection
     */
    private function isInterjection(): bool { }

    /**
     * DT: Determiner (the, a, an, that, your, many)
     */
    private function isDeterminer(string $word): bool
    {
        return in_array($word, ['the', 'a', 'an', 'that', 'your', 'many']);
    }
}
