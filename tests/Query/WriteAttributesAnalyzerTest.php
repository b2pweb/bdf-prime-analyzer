<?php

namespace Query;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\WriteAttributesAnalyzer;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;

/**
 * Class WriteAttributesAnalyzerTest
 */
class WriteAttributesAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var WriteAttributesAnalyzer
     */
    private $analyzer;

    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $this->analyzer = new WriteAttributesAnalyzer();
    }

    /**
     *
     */
    public function test_not_update_query()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()));
    }

    /**
     *
     */
    public function test_success()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()->values(['key' => 'response', 'value' => 42])));
        $this->assertEmpty($this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values([
            'bool' => true,
            'int' => 1454447,
            'smallint' => 1445,
            'tinyint' => 25,
            'bigint' => '11458',
            'double' => 1.2,
            'date' => new \DateTime(),
            'array' => ['foo', 'bar'],
        ])));
        $this->assertEmpty($this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values([
            'bool' => '0',
            'int' => '1454447',
            'smallint' => '1445',
            'tinyint' => '25',
            'bigint' => 1458,
            'double' => '1.2',
            'date' => '2020-06-05 15:23:11',
        ])));
    }

    /**
     *
     */
    public function test_undeclared_attribute()
    {
        $this->assertEquals(['Write on undeclared attribute "_key".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()->values(['_key' => 'response', 'value' => 42])));
    }

    /**
     *
     */
    public function test_invalid_type()
    {
        $this->assertEquals(['Bad value "invalid" for "id".'], $this->analyzer->analyze(TestEntity::repository(), TestEntity::builder()->values(['id' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "bool".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['bool' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "int".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['int' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "bigint".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['bigint' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "tinyint".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['tinyint' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "smallint".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['smallint' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "double".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['double' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "date".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['date' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "array".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['array' => 'invalid'])));
        $this->assertEquals(['Bad value "-300" for "tinyint".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['tinyint' => -300])));
        $this->assertEquals(['Bad value "1000000" for "smallint".'], $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['smallint' => 1000000])));
        $this->assertRegExp('/Bad value "Array.*" for "array"./s', $this->analyzer->analyze(EntityWithTypes::repository(), EntityWithTypes::builder()->values(['array' => [[]]]))[0]);
    }
}

class EntityWithTypes extends Model
{
    public $bool;
    public $int;
    public $smallint;
    public $tinyint;
    public $bigint;
    public $double;
    public $date;
    public $array;
}

class EntityWithTypesMapper extends Mapper
{
    public function schema()
    {
        return [];
    }

    public function buildFields($builder)
    {
        $builder
            ->boolean('bool')
            ->integer('int')
            ->smallint('smallint')
            ->tinyint('tinyint')
            ->bigint('bigint')
            ->double('double')
            ->dateTime('date')
            ->simpleArray('array')
        ;
    }
}
