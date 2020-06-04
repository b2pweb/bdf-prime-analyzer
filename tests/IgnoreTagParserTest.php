<?php

namespace Bdf\Prime\Analyzer;

use PHPUnit\Framework\TestCase;

/**
 * Class IgnoreTagParserTest
 */
class IgnoreTagParserTest extends TestCase
{
    /**
     *
     */
    public function test_parseLine()
    {
        $this->assertSame([], IgnoreTagParser::parseLine(''));
        $this->assertSame([], IgnoreTagParser::parseLine('foo'));
        $this->assertSame(['foo', 'bar'], IgnoreTagParser::parseLine('@prime-analyzer-ignore foo bar'));
    }

    /**
     *
     */
    public function test_parseDocBlock()
    {
        $this->assertSame([['foo', 'bar'], ['oof', 'baz']], IgnoreTagParser::parseDocBlock(<<<DOC
/**
 * Some comment
 *
 * @prime-analyzer-ignore foo bar
 * @prime-analyzer-ignore oof baz
 *
 * @other-tag
 */
DOC
        ));
        $this->assertSame([], IgnoreTagParser::parseDocBlock(''));
    }
}
