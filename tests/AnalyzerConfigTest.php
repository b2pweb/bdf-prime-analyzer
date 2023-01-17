<?php

namespace Bdf\Prime\Analyzer;

use PHPUnit\Framework\TestCase;

class AnalyzerConfigTest extends TestCase
{
    public function test_isIgnoredPath()
    {
        $config = new AnalyzerConfig([__DIR__.'/Testing']);

        $this->assertTrue($config->isIgnoredPath(__DIR__.'/Testing/AnalyzerReportDumperTest.php'));
        $this->assertFalse($config->isIgnoredPath(__DIR__.'/Testing/not_found'));
        $this->assertFalse($config->isIgnoredPath(__DIR__.'/Query/LikeWithoutWildcardAnalyzerTest.php'));

        $config->addIgnoredPath(__DIR__.'/Query');
        $this->assertTrue($config->isIgnoredPath(__DIR__.'/Query/LikeWithoutWildcardAnalyzerTest.php'));
    }

    public function test_isIgnoredAnalysis()
    {
        $config = new AnalyzerConfig([], ['foo', 'bar']);

        $this->assertSame(['foo', 'bar'], $config->ignoredAnalysis());

        $config->addIgnoredAnalysis('baz');
        $this->assertSame(['foo', 'bar', 'baz' => 'baz'], $config->ignoredAnalysis());
    }

    public function test_errorAnalysis()
    {
        $config = new AnalyzerConfig([], [], ['foo']);

        $this->assertTrue($config->isErrorAnalysis('foo'));
        $this->assertFalse($config->isErrorAnalysis('bar'));

        $config->addErrorAnalysis('bar');
        $this->assertTrue($config->isErrorAnalysis('bar'));
    }
}
