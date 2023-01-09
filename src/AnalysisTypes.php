<?php

namespace Bdf\Prime\Analyzer;

/**
 * Set of analysis types names
 */
final class AnalysisTypes
{
    public const LIKE = 'like';
    public const INDEX = 'index';
    public const NOT_DECLARED = 'not_declared';
    public const SORT = 'sort';
    public const OR = 'or';
    public const OPTIMISATION = 'optimisation';
    public const RELATION_DISTANT_KEY = 'relation_distant_key';
    public const WRITE = 'write';
    public const N_PLUS_1 = 'n+1';

    /**
     * Get all the optimisation analysis types
     *
     * @return string[]
     */
    public static function optimisations(): array
    {
        return [self::INDEX, self::SORT, self::OPTIMISATION, self::N_PLUS_1];
    }
}
