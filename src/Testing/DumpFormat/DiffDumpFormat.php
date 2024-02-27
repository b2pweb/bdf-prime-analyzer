<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use Bdf\Collection\HashSet;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Analyzer\Storage\Instant\ReportInstantFactory;
use Bdf\Prime\Analyzer\Storage\ReportStorageInterface;
use ReflectionClass;

use function class_exists;
use function json_encode;
use function realpath;
use function str_replace;
use function strlen;
use function substr;

/**
 * Dump only new queries on error
 */
final class DiffDumpFormat implements DumpFormatInterface
{
    /**
     * The last report root path
     */
    private ?string $lastRootPath = null;

    /**
     * The current root path
     */
    private ?string $currentRootPath = null;

    public function __construct(
        /**
         * The report storage which contains the previous report
         */
        private ReportStorageInterface $storage,

        /**
         * The instant system
         */
        private ReportInstantFactory $instantFactory,

        /**
         * Actual dump format, called only on new reports
         *
         * @var DumpFormatInterface[]
         */
        private array $formats,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function dump(array $reports): void
    {
        // Retrieve last reports
        $oldReports = $this->storage->last($this->instantFactory);

        if ($oldReports !== null) {
            $this->loadRootPath($oldReports);

            /** @var \Bdf\Collection\CollectionInterface<Report> $current */
            $current = new HashSet([$this, 'hash']);
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
    public function hash(Report $report): string
    {
        $cleanTrace = $report->stackTrace();

        foreach ($cleanTrace as &$item) {
            unset($item['line']);
            unset($item['args']);
            unset($item['object']);

            // Normalize file name : set path to same root
            if (isset($item['file']) && $this->lastRootPath !== null && $this->currentRootPath !== null) {
                $item['file'] = str_replace($this->lastRootPath, $this->currentRootPath, $item['file']);
            }
        }

        return json_encode([$report->entity(), $cleanTrace]);
    }

    /**
     * Load root path for both current application and last reports
     * This is used to normalize file paths for compare report path
     *
     * @param Report[] $oldReports
     *
     * @throws \ReflectionException
     */
    private function loadRootPath(array $oldReports): void
    {
        foreach ($oldReports as $report) {
            $trace = $report->stackTrace();

            foreach ($trace as $i => $item) {
                /** @psalm-suppress InvalidArrayOffset */
                $previous = $trace[$i - 1] ?? null;

                // Find a valid class to get a comparison point
                /** @psalm-suppress RedundantConditionGivenDocblockType */
                if ($i > 0 && isset($item['class']) && class_exists($item['class']) && isset($previous['file'])) {
                    // Because file and line represent the caller, the called class is on the previous item
                    $reportClassFilename = $previous['file'];
                    // Get file name of the corresponding class on the current runtime
                    $currentClassFilename = realpath((new ReflectionClass($item['class']))->getFileName());

                    // Find size of the suffix (i.e. relative file path)
                    for ($suffixLen = 1; $suffixLen < strlen($currentClassFilename); ++$suffixLen) {
                        if ($currentClassFilename[-$suffixLen] !== $reportClassFilename[-$suffixLen]) {
                            break;
                        }
                    }

                    // Get the real root for last reports and current runtime
                    $this->lastRootPath = substr($reportClassFilename, 0, strlen($reportClassFilename) - $suffixLen + 1);
                    $this->currentRootPath = substr($currentClassFilename, 0, strlen($currentClassFilename) - $suffixLen + 1);

                    return;
                }
            }
        }
    }
}
