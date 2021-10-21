<?php

namespace Storage\Instant;

use Bdf\Prime\Analyzer\Storage\Instant\DummyInstant;
use Bdf\Prime\Analyzer\Storage\Instant\DummyInstantFactory;
use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class DummyInstantTest
 */
class DummyInstantTest extends TestCase
{
    /**
     * @var DummyInstantFactory
     */
    private $factory;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->factory = new DummyInstantFactory();
    }

    /**
     *
     */
    public function test_values()
    {
        $instant = new DummyInstant();

        $this->assertSame('dummy', $this->factory->type());
        $this->assertSame('dummy', $instant->type());
        $this->assertSame('dummy', $instant->value());
        $this->assertEquals(0, $instant->compare(new DummyInstant()));
    }

    /**
     *
     */
    public function test_parse()
    {
        $this->assertEquals(new DummyInstant(), $this->factory->parse('foo'));
    }

    /**
     *
     */
    public function test_next()
    {
        $storage = $this->createMock(ReportStorageInterface::class);
        $storage->expects($this->never())->method('instants');

        $this->assertEquals(new DummyInstant(), $this->factory->next($storage));
    }
}
