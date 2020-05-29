<?php

namespace Bdf\Prime\Analyzer\Repository;

use AnalyzerTest\AnalyzerTestCase;
use AnalyzerTest\TestEntity;
use Bdf\Prime\Analyzer\Repository\Util\RepositoryUtil;

/**
 * Class RepositoryUtilTest
 */
class RepositoryUtilTest extends AnalyzerTestCase
{
    /**
     *
     */
    public function test_hasAttribute()
    {
        $util = new RepositoryUtil(TestEntity::repository());

        $this->assertTrue($util->hasAttribute('key'));
        $this->assertTrue($util->hasAttribute('value'));
        $this->assertTrue($util->hasAttribute('embeddedRelation.key'));
        $this->assertTrue($util->hasAttribute('embeddedRelation.label'));
        $this->assertTrue($util->hasAttribute('relationEntity.label'));

        $this->assertFalse($util->hasAttribute('_key'));
        $this->assertFalse($util->hasAttribute('not_found'));
        $this->assertFalse($util->hasAttribute('relationEntity.not_found'));
        $this->assertFalse($util->hasAttribute('not_found.not_found'));
    }

    /**
     *
     */
    public function test_isIndexed()
    {
        $util = new RepositoryUtil(TestEntity::repository());

        $this->assertTrue($util->isIndexed('id'));
        $this->assertTrue($util->isIndexed('key'));
        $this->assertTrue($util->isIndexed('_key'));
        $this->assertTrue($util->isIndexed('relationEntity.key'));

        $this->assertFalse($util->isIndexed('value'));
        $this->assertFalse($util->isIndexed('_value'));
        $this->assertFalse($util->isIndexed('not_found'));
        $this->assertFalse($util->isIndexed('relationEntity.label'));
        $this->assertFalse($util->isIndexed('relationEntity.not_found'));
        $this->assertFalse($util->isIndexed('not_found.not_found'));
    }

    /**
     *
     */
    public function test_relation()
    {
        $util = new RepositoryUtil(TestEntity::repository());

        $this->assertInstanceOf(RepositoryUtil::class, $util->relation('relationEntity'));
        $this->assertNull($util->relation('not_found'));
    }
}
