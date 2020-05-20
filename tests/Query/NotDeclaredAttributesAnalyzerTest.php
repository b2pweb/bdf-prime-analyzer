<?php

namespace Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\NotDeclaredAttributesAnalyzer;
use Bdf\Prime\Query\QueryInterface;

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
    protected function setUp()
    {
        parent::setUp();

        $this->analyzer = new NotDeclaredAttributesAnalyzer();
    }

    /**
     *
     */
    public function test_success()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()->where('id', 1)));
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()->where(function (QueryInterface $query) { $query->where('key', 'response')->orWhere('value', 42); })));
    }

    /**
     *
     */
    public function test_with_not_declared_attribute()
    {
        $this->assertEquals(['Use of undeclared attribute "_id".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()->where('_id', 1)));
        $this->assertEquals(['Use of undeclared attribute "_key".', 'Use of undeclared attribute "_value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()->where(function (QueryInterface $query) { $query->where('_key', 'response')->orWhere('_value', 42); })));
    }

    /**
     *
     */
    public function test_with_ignore_parameter()
    {
        $this->assertEquals(['Use of undeclared attribute "_value".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()->where(function (QueryInterface $query) { $query->where('_key', 'response')->orWhere('_value', 42); }), ['_key']));
    }
}
