<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use Bdf\Prime\Analyzer\Storage\Instant\ReportInstantFactory;
use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;

/**
 * Store the report into storage
 * This dump can be used with diff dump to show diff with previous run
 */
final class StorageDumpFormat implements DumpFormatInterface
{
    /**
     * @var ReportStorageInterface
     */
    private $storage;

    /**
     * @var ReportInstantFactory
     */
    private $instantFactory;


    /**
     * StorageDumpFormat constructor.
     * @param ReportStorageInterface $storage
     * @param ReportInstantFactory $instantFactory
     */
    public function __construct(ReportStorageInterface $storage, ReportInstantFactory $instantFactory)
    {
        $this->storage = $storage;
        $this->instantFactory = $instantFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function dump(array $reports): void
    {
        $instant = $this->instantFactory->next($this->storage);
        $this->storage->push($instant, $reports);
    }
}
