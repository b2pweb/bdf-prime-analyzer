<?php

namespace AnalyzerTest;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Prime;
use Bdf\Prime\Serializer\PaginatorNormalizer;
use Bdf\Prime\Serializer\PrimeCollectionNormalizer;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\Types\ArrayObjectType;
use Bdf\Prime\Types\ArrayType;
use Bdf\Prime\Types\DateTimeType;
use Bdf\Prime\Types\JsonType;
use Bdf\Prime\Types\ObjectType;
use Bdf\Prime\Types\TimestampType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Serializer\Normalizer\ObjectNormalizer;
use Bdf\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class AnalyzerTestCase
 */
class AnalyzerTestCase extends TestCase
{
    /**
     * @var ServiceLocator
     */
    protected $prime;

    /**
     * @var TestPack
     */
    protected $testPack;

    /**
     *
     */
    protected function setUp()
    {
        Prime::configure([
            'connection' => [
                'config' => [
                    'test' => [
                        'adapter' => 'sqlite',
                        'memory' => true
                    ],
                ]
            ],
        ]);

        $serializer = SerializerBuilder::create()
            ->build();

        $serializer->getLoader()
            ->addNormalizer(new PrimeCollectionNormalizer(Prime::service()))
            ->addNormalizer(new PaginatorNormalizer())
            ->addNormalizer(new ObjectNormalizer())
        ;

        Prime::service()->setSerializer($serializer);
        Prime::service()->types()->register(ArrayType::class, 'searchable_array');
        Prime::service()->types()->register(new JsonType());
        Prime::service()->types()->register(new ArrayObjectType());
        Prime::service()->types()->register(new ObjectType());
        Prime::service()->types()->register(new ArrayType());
        Prime::service()->types()->register(new DateTimeType('date_utc', 'Y-m-d H:i:s', \DateTimeImmutable::class, new \DateTimeZone('UTC')), 'date_utc');
        Prime::service()->types()->register(TimestampType::class, TypeInterface::TIMESTAMP);

        Model::configure(function() { return Prime::service(); });
        $this->prime = Prime::service();
        $this->testPack = TestPack::pack();
    }

    /**
     *
     */
    protected function tearDown()
    {
        $this->testPack->destroy();
        Prime::configure(null);
        Model::configure(null);
    }
}
