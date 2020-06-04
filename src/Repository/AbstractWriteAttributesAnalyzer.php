<?php

namespace Bdf\Prime\Analyzer\Repository;

use Bdf\Collection\Stream\StreamInterface;
use Bdf\Collection\Stream\Streams;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Types\TypeInterface;

/**
 * Analyze the values to write (i.e. insert or update)
 */
abstract class AbstractWriteAttributesAnalyzer implements RepositoryQueryErrorAnalyzerInterface
{
    /**
     * Extract the values
     *
     * @param CompilableClause $query
     *
     * @return array
     */
    abstract protected function values(CompilableClause $query): array;

    /**
     * {@inheritdoc}
     */
    final public function analyze(RepositoryInterface $repository, CompilableClause $query, array $parameters = []): array
    {
        $values = $this->values($query);

        if (empty($values)) {
            return [];
        }

        $metadata = $repository->metadata();

        return $this->analyseUndeclared($metadata, $values)
            ->concat($this->analyseType($metadata, $values))
            ->toArray(false)
        ;
    }

    /**
     * {@inheritdoc}
     */
    final public function type(): string
    {
        return 'write';
    }

    /**
     * Analyze undeclared attributes
     *
     * @param Metadata $metadata
     * @param array $values
     *
     * @return StreamInterface
     */
    private function analyseUndeclared(Metadata $metadata, array $values): StreamInterface
    {
        return Streams::wrap($values)
            ->map(function ($value, $key) { return $key; })
            ->filter(function ($attribute) use($metadata) { return !$metadata->attributeExists($attribute); })
            ->map(function (string $attribute) { return 'Write on undeclared attribute "'.$attribute.'".'; })
        ;
    }

    /**
     * Analyse if the values match with the corresponding type
     *
     * @param Metadata $metadata
     * @param array $values
     *
     * @return StreamInterface
     */
    private function analyseType(Metadata $metadata, array $values): StreamInterface
    {
        return Streams::wrap($values)
            ->filter(function ($value) { return $value !== null; }) // ignore null
            ->filter(function ($value, $attribute) use($metadata) {
                return isset($metadata->attributes[$attribute]) && !$this->checkType($metadata->attributes[$attribute]['type'], $value);
            })
            ->map(function ($value, $attribute) { return 'Bad value "'.print_r($value, true).'" for "'.$attribute.'".'; })
        ;
    }

    private function checkType(string $typename, $value): bool
    {
        switch ($typename) {
            case TypeInterface::TINYINT:
                return (string) $value === (string) (int) $value && $value >= -128 && $value <= 255;

            case TypeInterface::SMALLINT:
                return (string) $value === (string) (int) $value && $value >= -32768 && $value <= 65535;

            case TypeInterface::INTEGER:
                return (string) $value === (string) (int) $value;

            case TypeInterface::BIGINT:
                return is_numeric($value);

            case TypeInterface::DECIMAL:
            case TypeInterface::FLOAT:
            case TypeInterface::DOUBLE:
                return (string) $value === (string) (float) $value;

            case TypeInterface::TARRAY:
                return is_array($value) && $value === array_filter($value, function ($v) { return is_scalar($v); });

            case TypeInterface::BOOLEAN:
                return in_array($value, [0, 1, true, false, '0', '1'], true);

            case TypeInterface::DATETIME:
                return $value instanceof \DateTime || @\DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false;

            default:
                return true;
        }
    }
}
