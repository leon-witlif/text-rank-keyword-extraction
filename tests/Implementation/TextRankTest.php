<?php

declare(strict_types=1);

namespace KeywordGenerator\Tests\Implementation;

use KeywordGenerator\Collection\StopWordCollection;
use KeywordGenerator\Enum\Tag;
use KeywordGenerator\FileSystem\FileSystem;
use KeywordGenerator\Implementation\TextRank;
use KeywordGenerator\Linguistics\Filter\EnglishFilter;
use KeywordGenerator\Linguistics\Lemma\EnglishLemmatizer;
use KeywordGenerator\Linguistics\PartOfSpeech\EnglishTagger;
use KeywordGenerator\Struct\TaggedWord;
use PHPUnit\Framework\TestCase;

class TextRankTest extends TestCase
{
    public function testGenerateKeywords(): void
    {
        $filter = new EnglishFilter();
        $tagger = $this->createTestTagger();
        $lemmatizer = new EnglishLemmatizer();

        $stopWords = StopWordCollection::fromFile(FileSystem::FILES_DIRECTORY.'/stopwords-english-1.txt');
        $stopWords->merge(new StopWordCollection(['corresponding']));

        $textRank = new TextRank($filter, $tagger, $lemmatizer, $stopWords);

        $expectedKeywords = [
            'minimal supporting set',
            'minimal generating set',
            'minimal set',
            'linear diophantine equation',
            'strict inequation',
            'nonstrict inequation',
            'system',
            'linear constraint',
            'solution',
            'upper bound',
        ];

        $this->assertEquals($expectedKeywords, $textRank->generateKeywords(''));
    }

    private function createTestTagger(): EnglishTagger
    {
        $tagger = $this->createStub(EnglishTagger::class);

        $tagger->method('tag')
            ->willReturn(
                [
                    new TaggedWord('compatibility', Tag::NOUN),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('systems', Tag::NOUN_PLURAL),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('linear', Tag::ADJECTIVE),
                    new TaggedWord('constraints', Tag::NOUN_PLURAL),
                    new TaggedWord('over', Tag::PREPOSITION),
                    new TaggedWord('the', Tag::DETERMINER),
                    new TaggedWord('set', Tag::NOUN),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('natural', Tag::ADJECTIVE),
                    new TaggedWord('numbers', Tag::NOUN_PLURAL),
                    new TaggedWord('.', Tag::PERIOD),

                    new TaggedWord('criteria', Tag::NOUN_PLURAL),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('compatibility', Tag::NOUN),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('a', Tag::DETERMINER),
                    new TaggedWord('system', Tag::NOUN),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('linear', Tag::ADJECTIVE),
                    new TaggedWord('diophantine', Tag::NOUN),
                    new TaggedWord('equations', Tag::NOUN_PLURAL),
                    new TaggedWord(',', Tag::COMMA),
                    new TaggedWord('strict', Tag::ADJECTIVE),
                    new TaggedWord('inequations', Tag::NOUN_PLURAL),
                    new TaggedWord(',', Tag::COMMA),
                    new TaggedWord('and', Tag::CONJUNCTION),
                    new TaggedWord('nonstrict', Tag::ADJECTIVE),
                    new TaggedWord('inequations', Tag::NOUN_PLURAL),
                    new TaggedWord('are', Tag::UNTAGGED),
                    new TaggedWord('considered', Tag::VERB_PAST_PARTICIPLE),
                    new TaggedWord('.', Tag::PERIOD),

                    new TaggedWord('upper', Tag::ADJECTIVE),
                    new TaggedWord('bounds', Tag::NOUN_PLURAL),
                    new TaggedWord('for', Tag::PREPOSITION),
                    new TaggedWord('components', Tag::NOUN_PLURAL),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('a', Tag::DETERMINER),
                    new TaggedWord('minimal', Tag::ADJECTIVE),
                    new TaggedWord('set', Tag::NOUN),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('solutions', Tag::NOUN_PLURAL),
                    new TaggedWord('and', Tag::CONJUNCTION),
                    new TaggedWord('algorithms', Tag::NOUN_PLURAL),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('construction', Tag::NOUN),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('minimal', Tag::ADJECTIVE),
                    new TaggedWord('generating', Tag::VERB_GERUND),
                    new TaggedWord('sets', Tag::NOUN_PLURAL),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('solutions', Tag::NOUN_PLURAL),
                    new TaggedWord('for', Tag::PREPOSITION),
                    new TaggedWord('all', Tag::DETERMINER),
                    new TaggedWord('types', Tag::NOUN_PLURAL),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('systems', Tag::NOUN_PLURAL),
                    new TaggedWord('are', Tag::UNTAGGED),
                    new TaggedWord('given', Tag::VERB_PAST_PARTICIPLE),
                    new TaggedWord('.', Tag::PERIOD),

                    new TaggedWord('these', Tag::DETERMINER),
                    new TaggedWord('criteria', Tag::NOUN_PLURAL),
                    new TaggedWord('and', Tag::CONJUNCTION),
                    new TaggedWord('the', Tag::DETERMINER),
                    new TaggedWord('corresponding', Tag::ADJECTIVE),
                    new TaggedWord('algorithms', Tag::NOUN_PLURAL),
                    new TaggedWord('for', Tag::PREPOSITION),
                    new TaggedWord('constructing', Tag::VERB_GERUND),
                    new TaggedWord('a', Tag::DETERMINER),
                    new TaggedWord('minimal', Tag::ADJECTIVE),
                    new TaggedWord('supporting', Tag::VERB_GERUND),
                    new TaggedWord('set', Tag::NOUN),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('solutions', Tag::NOUN_PLURAL),
                    new TaggedWord('can', Tag::UNTAGGED),
                    new TaggedWord('be', Tag::VERB),
                    new TaggedWord('used', Tag::VERB_PAST_PARTICIPLE),
                    new TaggedWord('in', Tag::PREPOSITION),
                    new TaggedWord('solving', Tag::VERB_GERUND),
                    new TaggedWord('all', Tag::UNTAGGED),
                    new TaggedWord('the', Tag::DETERMINER),
                    new TaggedWord('considered', Tag::VERB_PAST_PARTICIPLE),
                    new TaggedWord('types', Tag::NOUN_PLURAL),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('systems', Tag::NOUN_PLURAL),
                    new TaggedWord('and', Tag::CONJUNCTION),
                    new TaggedWord('systems', Tag::NOUN_PLURAL),
                    new TaggedWord('of', Tag::PREPOSITION),
                    new TaggedWord('mixed', Tag::ADJECTIVE),
                    new TaggedWord('types', Tag::NOUN_PLURAL),
                    new TaggedWord('.', Tag::PERIOD),
                ]
            );

        return $tagger;
    }
}
