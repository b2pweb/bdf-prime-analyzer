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
    protected function setUp(): void
    {
        Prime::configure([
            'connection' => [
                'config' => [
                    'test' => [
                        'adapter' => 'sqlite',
                        'memory' => true
                    ],
                    'other' => [
                        'adapter' => 'sqlite',
                        'memory' => true
                    ],
                ]
            ],
            'types' => [
                'searchable_array' => ArrayType::class,
                new JsonType(),
                new ArrayObjectType(),
                new ObjectType(),
                new ArrayType(),
                'date_utc' => new DateTimeType('date_utc', 'Y-m-d H:i:s', \DateTimeImmutable::class, new \DateTimeZone('UTC')),
                TypeInterface::TIMESTAMP => TimestampType::class,
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

        Model::configure(function() { return Prime::service(); });
        $this->prime = Prime::service();
        $this->testPack = TestPack::pack();
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->testPack->destroy();
        Prime::configure(null);
        Model::configure(null);
    }
}
