<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\PartOfSpeech;

use KeywordGenerator\Enum\Tag;
use KeywordGenerator\Linguistics\Dictionary\Dictionary;
use KeywordGenerator\Struct\TaggedWord;

class EnglishTagger implements Tagger
{
    private Dictionary $dictionary;

    /** @var TaggedWord[] */
    private array $taggedWords;

    public function __construct(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
        $this->taggedWords = [];
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
        $tagQuote = count($this->taggedWords);

        foreach ($this->taggedWords as $taggedWord) {
            if ($taggedWord->word === ',') {
                $taggedWord->tag = Tag::COMMA;
                continue;
            }

            if ($taggedWord->word === '.') {
                $taggedWord->tag = Tag::PERIOD;
                continue;
            }

            if ($this->dictionary->isNoun($taggedWord->word)) {
                $taggedWord->tag = Tag::NOUN;
                continue;
            }

            if ($this->dictionary->isNounPlural($taggedWord->word)) {
                $taggedWord->tag = Tag::NOUN_PLURAL;
                continue;
            }

            if ($this->dictionary->isPronoun($taggedWord->word)) {
                $taggedWord->tag = Tag::PRONOUN;
                continue;
            }

            if ($this->dictionary->isVerb($taggedWord->word)) {
                $taggedWord->tag = Tag::VERB;
                continue;
            }

            if ($this->dictionary->isVerbGerund($taggedWord->word)) {
                $taggedWord->tag = Tag::VERB_GERUND;
                continue;
            }

            if ($this->dictionary->isVerbPastParticiple($taggedWord->word)) {
                $taggedWord->tag = Tag::VERB_PAST_PARTICIPLE;
                continue;
            }

            if ($this->dictionary->isAdjective($taggedWord->word)) {
                $taggedWord->tag = Tag::ADJECTIVE;
                continue;
            }

            if ($this->dictionary->isAdverb($taggedWord->word)) {
                $taggedWord->tag = Tag::ADVERB;
                continue;
            }

            if ($this->dictionary->isConjunction($taggedWord->word)) {
                $taggedWord->tag = Tag::CONJUNCTION;
                continue;
            }

            if ($this->dictionary->isPreposition($taggedWord->word)) {
                $taggedWord->tag = Tag::PREPOSITION;
                continue;
            }

            if ($this->dictionary->isInterjection($taggedWord->word)) {
                $taggedWord->tag = Tag::INTERJECTION;
                continue;
            }

            $taggedWord->tag = Tag::NOUN;
            --$tagQuote;
        }

        echo sprintf(
                'Tagged %d out of %d words (%d%%)',
                $tagQuote,
                count($this->taggedWords),
                floor($tagQuote / count($this->taggedWords) * 100)
            ).PHP_EOL;
    }
}
