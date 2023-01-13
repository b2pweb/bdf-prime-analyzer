<?php

namespace Bdf\Prime\Analyzer\Metadata\Attribute;

use Attribute;
use Bdf\Prime\Analyzer\AnalysisTypes;

/**
 * Ignore some analysis
 * Add this attribute on a class, method to define the scope.
 *
 * When added on a Mapper class, the attribute will be applied on all queries of the repository.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION)]
final class IgnoreAnalysis
{
    /**
     * @var list<string>
     */
    private array $analysis;

    /**
     * @param string ...$analysis Analysis types to ignore
     * @see AnalysisTypes
     * @no-named-arguments
     */
    public function __construct(string... $analysis)
    {
        $this->analysis = $analysis;
    }

    /**
     * List of ignored analysis report
     *
     * @return list<string>
     * @see AnalysisTypes For list of available analysis types
     */
    public function analysis(): array
    {
        return $this->analysis;
    }
}
