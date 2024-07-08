<?php

declare(strict_types=1);

namespace KeywordGenerator\Enum;

enum Tag : string
{
    case UNTAGGED = '';

    case PERIOD = '.';
    case COMMA = ',';

    case NOUN = 'NN';
    case NOUN_PLURAL = 'NNS';
    case NOUN_PROPER_SINGULAR = 'NNP';
    case NOUN_PROPER_PLURAL = 'NNPS';

    case ADJECTIVE = 'JJ';
    case ADVERB = 'RB';
    case CONJUNCTION = 'CC';
    case PREPOSITION = 'IN';
    case DETERMINER = 'DT';
}
