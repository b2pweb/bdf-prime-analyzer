<?php

namespace Bdf\Prime\Analyzer;

/**
 * Set of analysis types names
 */
final class AnalysisTypes
{
    const LIKE = 'like';
    const INDEX = 'index';
    const NOT_DECLARED = 'not_declared';
    const SORT = 'sort';
    const OR = 'or';
    const OPTIMISATION = 'optimisation';
    const RELATION_DISTANT_KEY = 'relation_distant_key';
    const WRITE = 'write';
    const N_PLUS_1 = 'n+1';

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
