<?php

declare(strict_types=1);

$language = 'en';

class TaggedWord
{
    public string $token;

    private string $tag;
    private bool $processed;

    public function __construct(string $token, string $tag)
    {
        $this->token = $token;
        $this->tag = $tag;

        $this->processed = false;
    }

    public function setTag(string $tag): void
    {
        $this->tag = $tag;
        $this->processed = true;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function __toString(): string
    {
        return sprintf('%s/%s', $this->token, $this->tag);
    }
}

class POSTagger // Part-of-Speech Tagger
{
    private string $text;
    private array $tagged;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function tag(): array
    {
        global $language;

        $nouns = ['NN', 'NNS'];
        $counter = 0;

        $words = explode(' ', $this->text);
        $words = array_values(array_filter($words, fn (string $word) => strlen($word)));

        for ($i = count($words) - 1; $i >= 0; $i--) {
            $word = $words[$i];

            if (str_ends_with($word, '.') || str_ends_with($word, ',')) {
                $words[$i] = substr($word, 0, -1);
                array_splice($words, $i + 1, 0, substr($word, -1));
            }
        }

        foreach ($words as $word) {
            $taggedWord = new TaggedWord($word, 'NN');

            if ($taggedWord->token === '.') {
                $taggedWord->setTag('.');
            }

            if ($taggedWord->token === ',') {
                $taggedWord->setTag(',');
            }

            if ($taggedWord->getTag()[0] === 'N' && str_ends_with($taggedWord->token, 'ed')) {
                // Verb, past participle
                $taggedWord->setTag('VBN');
            }

            if (str_ends_with($taggedWord->token, 'ly')) {
                // Adverb
                $taggedWord->setTag('RB');
            }

            if (in_array($taggedWord->getTag(), $nouns) && str_ends_with($taggedWord->token, 'al')) {
                // Adjective
                $taggedWord->setTag('JJ');
            }

            if ($counter > 0 && $taggedWord->getTag() === 'NN' && strtolower($this->tagged[$counter - 1]->token) === 'would') {
                // Verb, base form
                $taggedWord->setTag('VB');
            }

            if ($language === 'en' && $taggedWord->getTag() === 'NN' && str_ends_with($taggedWord->token, 's')) {
                // Noun, plural
                $taggedWord->setTag('NNS');
            }

            if (in_array($taggedWord->getTag(), $nouns) && str_ends_with($taggedWord->token, 'ing')) {
                // Verb, gerund or present participle
                $taggedWord->setTag('VBG');
            }

            $this->tagged[] = $taggedWord;

            $counter++;
        }

        return $this->tagged;
    }
}

class Lemmatizer
{
    public function lemmatize(TaggedWord ...$words): array
    {
        foreach ($words as $word) {
            if ($word->getTag() === 'NNS' && str_ends_with($word->token, 's')) {
                $word->token = substr($word->token, 0, -1);
                $word->setTag('NN');
            }
        }

        return $words;
    }
}

function loadTextFromFile(string $filePath): string
{
    $text = '';

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_map(fn (string $line) => trim($line), $lines);

    foreach ($lines as $line) {
        $text .= $line.' ';
    }

    return $text;
}

if ($language === 'en') {
    $text = loadTextFromFile('sample-english-1.txt');
} else {
    // $text = load_text_from_file('sample-german-1.txt');
    $text = loadTextFromFile('sample-german-2.txt');
}

if ($language === 'en') {
    $allowedCharactersRegex = '/[^a-z0-9 .,]/';
} else {
    $allowedCharactersRegex = '/[^a-z0-9 .,äöüÄÖÜß]/';
}

$text = strtolower($text);
$text = preg_replace($allowedCharactersRegex, '', $text);

$taggedWords = (new POSTagger($text))->tag();
// print_r($taggedWords);

$lemmatizedWords = (new Lemmatizer())->lemmatize(...$taggedWords);
// print_r($lemmatizedWords);

$stateWasFirstAllAppearance = false;

function applyPOSTagCorrection(TaggedWord $word): TaggedWord
{
    global $stateWasFirstAllAppearance;

    switch ($word->token) {
        case 'of':
        case 'over':
        case 'for':
        case 'in':
            $word->setTag('IN');
            break;
        case 'linear':
        case 'diophantine':
        case 'strict':
        case 'nonstrict':
        case 'upper':
        case 'corresponding':
        case 'mixed':
            $word->setTag('JJ');
            break;
        case 'the':
        case 'a':
        case 'these':
            $word->setTag('DT');
            break;
        case 'inequation':
            $word->token = 'inequations';
            $word->setTag('NNS');
            break;
        case 'and':
            $word->setTag('CC');
            break;
        case 'are':
            $word->setTag('VBP');
            break;
        case 'given':
            $word->setTag('VBN');
            break;
        case 'criteria':
            $word->token = 'criterion';
            break;
        case 'supporting':
            $word->setTag('NN');
            break;
        case 'can':
            $word->setTag('MD');
            break;
        case 'be':
            $word->setTag('VB');
            break;
        case 'all':
            if (!$stateWasFirstAllAppearance) {
                $word->setTag('DT');
                $stateWasFirstAllAppearance = true;
            } else {
                $word->setTag('PDT');
            }
            break;
    }

    return $word;
}

foreach ($lemmatizedWords as $word) {
    applyPOSTagCorrection($word);
}

// foreach ($lemmatizedWords as $word) {
//     echo $word.', ';
// }

$wantedPOS = ['NN', 'NNS', 'NNP', 'NNPS', 'JJ', 'JJR', 'JJS', 'VBG', 'FW'];

$stopWords = array_filter($lemmatizedWords, fn (TaggedWord $word) => !in_array($word->getTag(), $wantedPOS) /*|| !$word->isProcessed()*/);
$stopWords = array_map(fn (TaggedWord $word) => $word->token, $stopWords);

function loadStopWordsFromFile(string $filePath): array
{
    $stopWords = [];

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_map(fn (string $line) => trim($line), $lines);

    foreach ($lines as $line) {
        $stopWords[] = $line;
    }

    return $stopWords;
}

if ($language === 'en') {
    array_push($stopWords, ...[...loadStopWordsFromFile('stopwords-english-1.txt'), 'corresponding']);
} else {
    array_push($stopWords, ...loadStopWordsFromFile('stopwords-german-1.txt'));
    // array_push($stopWords, ...loadStopWordsFromFile('stopwords-german-2.txt'));
}

$stopWords = array_values(array_unique($stopWords));

function removeStopWords(TaggedWord ...$words): array
{
    global $stopWords;

    $words = array_filter($words, fn (TaggedWord $word) => !in_array($word->token, $stopWords));
    $words = array_values($words);

    return $words;
}

$processedWords = removeStopWords(...$lemmatizedWords);
$processedText = array_map(fn (TaggedWord $word) => $word->token, $processedWords);
// print_r($processedText);

$lemmatizedText = array_map(fn (TaggedWord $word) => $word->token, $lemmatizedWords);
// print_r($lemmatizedText);

$vocabulary = array_values(array_unique($processedText));
// print_r($vocabulary);

$vocabularyLength = count($vocabulary);
$weightedEdge = [];

$score = [];
$windowSize = 3;
$coveredCoOccurrence = [];

function set2DArrayValue(array &$array, int $i, int $j, int|bool $value): void
{
    if (!array_key_exists($i, $array)) {
        $array[$i] = [];
    }

    $array[$i][$j] = $value;
}

for ($i = 0; $i < $vocabularyLength; $i++) {
    $score[$i] = 1;

    for ($j = 0; $j < $vocabularyLength; $j++) {
        if ($j === $i) {
            set2DArrayValue($weightedEdge, $i, $j, 0);
        } else {
            for ($windowStart = 0; $windowStart < count($processedText) - $windowSize; $windowStart++) {
                $window = array_slice($processedText, $windowStart, $windowSize, true);

                if (in_array($vocabulary[$i], $window) && in_array($vocabulary[$j], $window)) {
                    $indexOfI = $windowStart + array_search($vocabulary[$i], $window);
                    $indexOfJ = $windowStart + array_search($vocabulary[$j], $window);

                    if (!isset($coveredCoOccurrence[$indexOfI][$indexOfJ])) {
                        if (!isset($weightedEdge[$i][$j])) {
                            set2DArrayValue($weightedEdge, $i, $j, 0);
                        }

                        $weightedEdge[$i][$j] += 1 / abs($indexOfI - $indexOfJ);
                        set2DArrayValue($coveredCoOccurrence, $indexOfI, $indexOfJ, true);
                    }
                }
            }
        }
    }
}

// print_r($weightedEdge);

$inout = [];

for ($i = 0; $i < $vocabularyLength; $i++) {
    $inout[$i] = 0;

    for ($j = 0; $j < $vocabularyLength; $j++) {
        if (isset($weightedEdge[$i][$j])) {
            $inout[$i] += $weightedEdge[$i][$j];
        }
    }
}

// print_r($inout);

$maxIterations = 50;
$dampeningFactor = 0.85;
$threshold = 0.0001;

for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
    $prevScore = $score;

    for ($i = 0; $i < $vocabularyLength; $i++) {
        $summation = 0;

        for ($j = 0; $j < $vocabularyLength; $j++) {
            if (isset($weightedEdge[$i][$j])) {
                try {
                    $summation += $weightedEdge[$i][$j] / $inout[$j] * $score[$j];
                } catch (Error) {}
            }
        }

        $score[$i] = (1 - $dampeningFactor) + $dampeningFactor * $summation;
    }
}

// for ($i = 0; $i < $vocabularyLength; $i++) {
//     echo sprintf('%s: %f', $vocabulary[$i], $score[$i]).PHP_EOL;
// }

$phrases = [];
$phrase = ' ';

foreach ($lemmatizedText as $word) {
    if (in_array($word, $stopWords)) {
        if ($phrase !== ' ') {
            $phrases[] = explode(' ', trim($phrase));
        }

        $phrase = ' ';
    } else {
        $phrase .= $word.' ';
    }
}

// print_r($phrase);
// print_r($phrases);

$uniquePhrases = array_values(array_unique($phrases, SORT_REGULAR));
// print_r($uniquePhrases);

foreach ($vocabulary as $word) {
    foreach ($uniquePhrases as $phrase) {
        if (in_array($word, $phrase) && in_array([$word], $uniquePhrases) && count($phrase) > 1) {
            unset($uniquePhrases[array_search([$word], $uniquePhrases)]);
        }
    }
}

// print_r($uniquePhrases);

$phraseScores = [];
$keywords = [];

foreach ($uniquePhrases as $phrase) {
    $phraseScore = 0;
    $keyword = '';

    foreach ($phrase as $word) {
        $keyword .= $word.' ';
        $phraseScore += $score[array_search($word, $vocabulary)];
    }

    $phraseScores[] = $phraseScore;
    $keywords[] = trim($keyword);
}

// $counter = 0;
// foreach ($keywords as $keyword) {
//     echo sprintf('Keyword: %s: Score: %f', $keyword, $phraseScores[$counter]).PHP_EOL;
//     $counter++;
// }

array_multisort($phraseScores, SORT_DESC, $keywords);
print_r(array_slice($keywords, 0, 10));
