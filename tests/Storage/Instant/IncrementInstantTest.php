<?php

namespace Storage\Instant;

use Bdf\Prime\Analyzer\Storage\Instant\IncrementInstant;
use Bdf\Prime\Analyzer\Storage\Instant\IncrementInstantFactory;
use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class IncrementInstantTest
 */
class IncrementInstantTest extends TestCase
{
    /**
     * @var IncrementInstantFactory
     */
    private $factory;

    /**
     *
     */
    protected function setUp()
    {
        $this->factory = new IncrementInstantFactory();
    }

    /**
     *
     */
    public function test_values()
    {
        $instant = new IncrementInstant(5);

        $this->assertSame('increment', $this->factory->type());
        $this->assertSame('increment', $instant->type());
        $this->assertSame('5', $instant->value());
        $this->assertEquals(-1, $instant->compare(new IncrementInstant(10)));
        $this->assertEquals(1, $instant->compare(new IncrementInstant(3)));
        $this->assertEquals(0, $instant->compare(new IncrementInstant(5)));
    }

    /**
     *
     */
    public function test_parse()
    {
        $this->assertEquals(new IncrementInstant(3), $this->factory->parse('3'));
    }

    /**
     *
     */
    public function test_next()
    {
        $storage = $this->createMock(ReportStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('instants')
            ->with($this->factory)
            ->willReturn([new IncrementInstant(2), new IncrementInstant(3)])
        ;

        $this->assertEquals(new IncrementInstant(4), $this->factory->next($storage));
    }
}
