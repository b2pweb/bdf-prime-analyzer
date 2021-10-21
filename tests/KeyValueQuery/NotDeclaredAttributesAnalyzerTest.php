<?php

namespace Bdf\Prime\Analyzer\KeyValueQuery;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;

/**
 * Class NotDeclaredAttributesAnalyzerTest
 */
class NotDeclaredAttributesAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var NotDeclaredAttributesAnalyzer
     */
    private $analyzer;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new NotDeclaredAttributesAnalyzer();
    }

    /**
     *
     */
    public function test_success()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue()));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue()->where('id', 1)));
    }

    /**
     *
     */
    public function test_with_not_declared_attribute()
    {
        $this->assertEquals(['Use of undeclared attribute "_id".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue()->where('_id', 1)));
        $this->assertEquals(['Use of undeclared attribute "_key".', 'Use of undeclared attribute "_value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue()->where('_key', 'response')->where('_value', 42)));
    }

    /**
     *
     */
    public function test_with_ignore_parameter()
    {
        $this->assertEquals(['Use of undeclared attribute "_value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::keyValue()->where('_key', 'response')->where('_value', 42), ['_key']));
    }
}
