<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use Bdf\Collection\HashSet;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Analyzer\Storage\Instant\ReportInstantFactory;
use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;

/**
 * Dump only new queries on error
 */
final class DiffDumpFormat implements DumpFormatInterface
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
     * @var DumpFormatInterface[]
     */
    private $formats;

    /**
     * DiffDumpFormat constructor.
     *
     * @param ReportStorageInterface $storage The report storage
     * @param ReportInstantFactory $instantFactory The instant system
     * @param DumpFormatInterface[] $formats The diff dump format
     */
    public function __construct(ReportStorageInterface $storage, ReportInstantFactory $instantFactory, array $formats)
    {
        $this->storage = $storage;
        $this->instantFactory = $instantFactory;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function dump(array $reports): void
    {
        // Retrieve last reports
        $oldReports = $this->storage->last($this->instantFactory);

        if ($oldReports !== null) {
            $current = new HashSet([self::class, 'hash']);
            $current->addAll($reports);

            // Remove old reports from current ones
            foreach ($oldReports as $oldReport) {
                $current->remove($oldReport);
            }

            $reports = $current->toArray();
        }

        foreach ($this->formats as $format) {
            $format->dump($reports);
        }
    }

    /**
     * Compute the hash for the given report :
     *
     * Remove all data of the stack trace that can be modified between two run of analyzer
     * To ensure that only new queries will be reported (and not moved ones)
     *
     * @param Report $report
     * @return string
     */
    public static function hash(Report $report): string
    {
        $cleanTrace = $report->stackTrace();

        foreach ($cleanTrace as &$item) {
            unset($item['line']);
            unset($item['args']);
            unset($item['object']);
        }

        return json_encode([$report->entity(), $cleanTrace]);
    }
}
