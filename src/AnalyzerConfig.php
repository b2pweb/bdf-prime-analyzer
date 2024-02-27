<?php

namespace Bdf\Prime\Analyzer;

use function realpath;

/**
 * Store configuration for {@see AnalyzerService}
 */
final class AnalyzerConfig
{
    /**
     * Analysis that should raise an exception
     * Map the analysis name to a boolean flag
     *
     * @var array<string, bool>
     */
    private array $errorAnalysis = [];

    public function __construct(
        /**
         * Paths to ignore
         * This path must be absolute
         *
         * @var string[]
         */
        private array $ignoredPath = [],

        /**
         * List of analysis to ignore
         *
         * @var string[]
         *
         * @see AnalysisTypes
         */
        private array $ignoredAnalysis = [],

        /**
         * Analysis that should raise an exception instead of a simple report
         *
         * @param list<string> $errorAnalysis
         */
        array $errorAnalysis = [],
    ) {
        foreach ($errorAnalysis as $analysis) {
            $this->errorAnalysis[$analysis] = true;
        }
    }

    /**
     * Check if the given path is ignored
     * If the path does not exist, this method will return false
     *
     * @param string $path File path
     *
     * @return bool
     */
    public function isIgnoredPath(string $path): bool
    {
        $path = realpath($path);

        if ($path === false) {
            return false;
        }

        foreach ($this->ignoredPath as $ignoredPath) {
            $ignoredPath = realpath($ignoredPath);

            if ($ignoredPath !== false && str_starts_with($path, $ignoredPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of analysis to ignore
     *
     * @return string[]
     * @see AnalysisTypes
     */
    public function ignoredAnalysis(): array
    {
        return $this->ignoredAnalysis;
    }

    /**
     * Add a new path to ignore
     *
     * @param string $path
     */
    public function addIgnoredPath(string $path): void
    {
        $this->ignoredPath[$path] = $path;
    }

    /**
     * Ignore an analysis
     *
     * @param string $analysis
     */
    public function addIgnoredAnalysis(string $analysis): void
    {
        $this->ignoredAnalysis[$analysis] = $analysis;
    }

    /**
     * Configure the given analysis to raise an exception
     *
     * @param string $analysis Analysis type
     * @return void
     *
     * @see AnalysisTypes
     */
    public function addErrorAnalysis(string $analysis): void
    {
        $this->errorAnalysis[$analysis] = true;
    }

    /**
     * Check if the given analysis should raise an exception
     *
     * @param string $analysis The analysis type
     *
     * @return bool
     * @see AnalysisTypes
     */
    public function isErrorAnalysis(string $analysis): bool
    {
        return !empty($this->errorAnalysis[$analysis]);
    }
}
