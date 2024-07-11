<?php

declare(strict_types=1);

namespace KeywordGenerator\Implementation;

use KeywordGenerator\Collection\StopWordCollection;
use KeywordGenerator\Enum\Tag;
use KeywordGenerator\Linguistics\Filter\Filter;
use KeywordGenerator\Linguistics\Lemma\Lemmatizer;
use KeywordGenerator\Linguistics\PartOfSpeech\Tagger;
use KeywordGenerator\Struct\TaggedWord;

class TextRank implements KeywordGenerator
{
    /** @var TaggedWord[] */
    private array $lemmatizedWords;

    /** @var TaggedWord[] */
    private array $processedWords;

    public function __construct(
        private Filter $filter,
        private Tagger $tagger,
        private Lemmatizer $lemmatizer,
        private StopWordCollection $stopWords
    ) {
    }

    public function generateKeywords(string $text): array
    {
        $filteredText = $this->filter->filter($text);

        $taggedWords = $this->tagger->tag($filteredText);

        $this->lemmatizedWords = $this->lemmatizer->lemmatize(...$taggedWords);

        $this->addNotWantedWordsToStopWordsByPOS();

        $this->removeStopWords();

        return $this->processedWords;
    }

    private function addNotWantedWordsToStopWordsByPOS(): void
    {
        $wantedPOS = [
            Tag::NOUN,
            Tag::NOUN_PLURAL,
            Tag::NOUN_PROPER_PLURAL,
            Tag::NOUN_PROPER_PLURAL,
            Tag::ADJECTIVE,
            // 'JJR',
            // 'JJS',
            Tag::VERB_GERUND,
            // 'FW',
        ];

        $notWantedTaggedWords = array_filter(
            $this->lemmatizedWords,
            fn (TaggedWord $taggedWord): bool => !in_array($taggedWord->tag, $wantedPOS, true)
        );

        $notWantedWords = array_map(fn (TaggedWord $taggedWord): string => $taggedWord->word, $notWantedTaggedWords);

        $this->stopWords->merge(new StopWordCollection($notWantedWords));
    }

    private function removeStopWords(): void
    {
        foreach ($this->lemmatizedWords as $taggedWord) {
            if (!$this->stopWords->contains($taggedWord->word)) {
                $this->processedWords[] = $taggedWord;
            }
        }
    }

    // Temporary getter functions
    public function getLemmatizedWords(): array
    {
        return $this->lemmatizedWords;
    }

    public function getStopWordCollection(): StopWordCollection
    {
        return $this->stopWords;
    }
}
