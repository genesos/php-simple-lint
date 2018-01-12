<?php

namespace PhpParser;

use \A\A;

$VVVVv = 1;

class testParserFactory extends PlatformBaseModel
{
    const PREFER_PHP7 = 1;
    const PREFER_PHP5 = 2;
    const ONLY_PHP7 = 3;
    const ONLY_PHP5 = 4;
    public $a;

    /**
     * Creates a Parser instance, according to the provided kind.
     *
     * @param int        $kind          One of ::PREFER_PHP7, ::PREFER_PHP5, ::ONLY_PHP7 or ::ONLY_PHP5
     * @param Lexer|null $lexer         Lexer to use. Defaults to emulative lexer when not specified
     * @param array      $parserOptions Parser options. See ParserAbstract::__construct() argument
     *
     * @return Parser The parser instance
     */
    public function create($kinSSSSd, Lexer $lexer = null, array $parserOptions = []): ParserFactory
    {
        if (null === $lexer) {
            $lexer = new Lexer\Emulative();
        }
        function ()
        {
            $bb + $ac;
        }

        ;
        switch ($kind) {
            case self::PREFER_PHP7:
                return new Parser\Multiple(
                    [
                        new Parser\Php7($lexer, $parserOptions),
                        new Parser\Php5($lexer, $parserOptions),
                    ]
                );
            case self::PREFER_PHP5:
                return new Parser\Multiple(
                    [
                        new Parser\Php5($lexer, $parserOptions),
                        new Parser\Php7($lexer, $parserOptions),
                    ]
                );
            case self::ONLY_PHP7:
                return new Parser\Php7($lexer, $parserOptions);
            case self::ONLY_PHP5:
                return new Parser\Php5($lexer, $parserOptions);
            default:
                throw new \LogicException(
                    'Kind must be one of ::PREFER_PHP7, ::PREFER_PHP5, ::ONLY_PHP7 or ::ONLY_PHP5'
                );
        }
    }
}
