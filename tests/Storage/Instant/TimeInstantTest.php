<?php

namespace Storage\Instant;

use Bdf\Prime\Analyzer\Storage\Instant\TimeInstant;
use Bdf\Prime\Analyzer\Storage\Instant\TimeInstantFactory;
use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class TimeInstantTest
 */
class TimeInstantTest extends TestCase
{
    /**
     * @var TimeInstantFactory
     */
    private $factory;

    /**
     *
     */
    protected function setUp()
    {
        $this->factory = new TimeInstantFactory();
    }

    /**
     *
     */
    public function test_values()
    {
        $instant = new TimeInstant($d = new \DateTime('2020-10-21 15:58:02'));

        $this->assertSame('time', $this->factory->type());
        $this->assertSame('time', $instant->type());
        $this->assertSame('2020-10-21T15:58:02+02:00', $instant->value());
        $this->assertEquals(-1, $instant->compare(new TimeInstant(new \DateTime('2020-10-22 18:00:00'))));
        $this->assertEquals(1, $instant->compare(new TimeInstant(new \DateTime('2020-04-22 18:00:00'))));
        $this->assertEquals(0, $instant->compare(new TimeInstant(new \DateTime('2020-10-21 15:58:02'))));
    }

    /**
     *
     */
    public function test_parse()
    {
        $this->assertEquals(new TimeInstant(new \DateTime('2020-10-22 18:00:00')), $this->factory->parse('2020-10-22T18:00:00+02:00'));
    }

    /**
     *
     */
    public function test_next()
    {
        $storage = $this->createMock(ReportStorageInterface::class);
        $storage->expects($this->never())->method('instants');

        $this->assertEqualsWithDelta(new TimeInstant(new \DateTime()), $this->factory->next($storage), 0.1);
    }
}
