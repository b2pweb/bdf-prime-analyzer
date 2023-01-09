<?php

namespace Bdf\Prime\Analyzer\BulkInsertQuery;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Query\WriteAttributesAnalyzer;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;

/**
 * Class InsertValuesAnalyzerTest
 */
class InsertValuesAnalyzerTest extends AnalyzerTestCase
{
    /**
     * @var InsertValuesAnalyzer
     */
    private $analyzer;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new InsertValuesAnalyzer();
    }

    /**
     *
     */
    public function test_success()
    {
        $this->assertEmpty($this->analyzer->analyze(TestEntity::repository(), $this->query(TestEntity::class)->values(['key' => 'response', 'value' => 42])));
        $this->assertEmpty($this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values([
            'bool' => true,
            'int' => 1454447,
            'smallint' => 1445,
            'tinyint' => 25,
            'bigint' => '11458',
            'double' => 1.2,
            'date' => new \DateTime(),
            'array' => ['foo', 'bar'],
        ])));
        $this->assertEmpty($this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values([
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
        $this->assertEquals(['Write on undeclared attribute "_key".'], $this->analyzer->analyze(TestEntity::repository(), $this->query(TestEntity::class)->values(['_key' => 'response', 'value' => 42])));
    }

    /**
     *
     */
    public function test_invalid_type()
    {
        $this->assertEquals(['Bad value "invalid" for "id".'], $this->analyzer->analyze(TestEntity::repository(), $this->query(TestEntity::class)->values(['id' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "bool".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['bool' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "int".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['int' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "bigint".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['bigint' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "tinyint".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['tinyint' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "smallint".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['smallint' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "double".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['double' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "date".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['date' => 'invalid'])));
        $this->assertEquals(['Bad value "invalid" for "array".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['array' => 'invalid'])));
        $this->assertEquals(['Bad value "-300" for "tinyint".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['tinyint' => -300])));
        $this->assertEquals(['Bad value "1000000" for "smallint".'], $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['smallint' => 1000000])));
        $this->assertMatchesRegularExpression('/Bad value "Array.*" for "array"./s', $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->values(['array' => [[]]]))[0]);
    }

    /**
     *
     */
    public function test_bulk()
    {
        $this->assertEquals(
            ['Bad value "invalid" for "bool".', 'Bad value "invalid" for "int".'],
            $this->analyzer->analyze(EntityWithTypes::repository(), $this->query(EntityWithTypes::class)->bulk(true)
                ->values(['bool' => 'invalid'])
                ->values(['int' => 'invalid'])
                ->values(['double' => 4.2])
            )
        );
    }

    private function query(string $entity): BulkInsertQuery
    {
        return $this->prime->connection('test')->make(BulkInsertQuery::class)->from($entity::metadata()->table);
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
    public function schema(): array
    {
        return ['connection' => 'test', 'table' => 'with_types'];
    }

    public function buildFields($builder): void
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
