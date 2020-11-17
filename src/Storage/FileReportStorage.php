<?php

namespace Bdf\Prime\Analyzer\Storage;

use Bdf\Collection\Stream\Streams;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Analyzer\Storage\Instant\ReportInstant;
use Bdf\Prime\Analyzer\Storage\Instant\ReportInstantFactory;
use FilesystemIterator;
use InvalidArgumentException;
use SplFileInfo;

/**
 * Store reports into a serialized PHP file
 */
final class FileReportStorage implements ReportStorageInterface
{
    /**
     * @var string
     */
    private $baseDirectory;

    /**
     * FileReportStorage constructor.
     *
     * @param string $baseDirectory The storage directory
     */
    public function __construct(string $baseDirectory)
    {
        $this->baseDirectory = $baseDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public function push(ReportInstant $instant, array $reports): void
    {
        $filename = $this->filename($instant);

        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }

        file_put_contents($filename, serialize($reports));
        chmod($filename, 0666);
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReportInstant $instant): array
    {
        $filename = $this->filename($instant);

        if (!is_file($filename)) {
            throw new InvalidArgumentException('Report not found');
        }

        return unserialize(file_get_contents($filename), ['allowed_classes' => [Report::class]]);
    }

    /**
     * {@inheritdoc}
     */
    public function instants(ReportInstantFactory $instantFactory): array
    {
        $directory = $this->baseDirectory.DIRECTORY_SEPARATOR.$instantFactory->type();

        if (!is_dir($directory)) {
            return [];
        }

        return Streams::wrap(new FilesystemIterator($directory))
            ->map(function (SplFileInfo $info) { return $info->getBasename('.report'); })
            ->map(function (string $value) use ($instantFactory) { return $instantFactory->parse($value); })
            ->sort(function (ReportInstant $a, ReportInstant $b) { /** @psalm-suppress ArgumentTypeCoercion */ return $a->compare($b); })
            ->toArray(false)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function last(ReportInstantFactory $instantFactory): ?array
    {
        $lastInstant = $this->instants($instantFactory);
        $lastInstant = end($lastInstant);

        if (!$lastInstant) {
            return null;
        }

        return $this->get($lastInstant);
    }

    /**
     * Get the reports filename
     *
     * @param ReportInstant $instant
     *
     * @return string
     */
    private function filename(ReportInstant $instant): string
    {
        return $this->baseDirectory.DIRECTORY_SEPARATOR.$instant->type().DIRECTORY_SEPARATOR.$instant->value().'.report';
    }
}
