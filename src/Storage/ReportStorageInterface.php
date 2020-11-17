<?php

namespace Bdf\Prime\Analyzer\Storage;

use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Analyzer\Storage\Instant\ReportInstant;
use Bdf\Prime\Analyzer\Storage\Instant\ReportInstantFactory;

/**
 * Storage for prime analyzer reports
 */
interface ReportStorageInterface
{
    /**
     * Save reports
     *
     * @param ReportInstant $instant The report instant
     * @param Report[] $reports Reports to save
     */
    public function push(ReportInstant $instant, array $reports): void;

    /**
     * Get reports for the given instant
     *
     * @param ReportInstant $instant
     *
     * @return Report[]
     * @throws \InvalidArgumentException
     */
    public function get(ReportInstant $instant): array;

    /**
     * Get list of all saved instants handled by the instant factory
     *
     * @template I as ReportInstant
     * @param ReportInstantFactory<I> $instantFactory
     *
     * @return ReportInstant[]
     * @psalm-return I[]
     */
    public function instants(ReportInstantFactory $instantFactory): array;

    /**
     * Get the last report
     *
     * @param ReportInstantFactory $instantFactory
     *
     * @return Report[]|null The reports or null if there is no reports
     */
    public function last(ReportInstantFactory $instantFactory): ?array;
}
