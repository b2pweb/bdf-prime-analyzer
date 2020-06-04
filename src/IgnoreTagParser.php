<?php

namespace Bdf\Prime\Analyzer;

/**
 * Handle parsing of the ignore tag
 */
final class IgnoreTagParser
{
    const TAG = '@prime-analyzer-ignore';

    /**
     * Parse a line to find the ignore tag and return the arguments
     *
     * @param string $line
     *
     * @return string[] List of arguments
     */
    public static function parseLine(string $line): array
    {
        $tagPos = strpos($line, self::TAG);

        if ($tagPos === false) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(' ', substr($line, $tagPos + strlen(self::TAG))))));
    }

    /**
     * Parse a docblock
     *
     * @param string $docblock
     *
     * @return string[][]
     */
    public static function parseDocBlock(string $docblock): array
    {
        $lines = [];

        foreach (explode("\n", $docblock) as $line) {
            $tag = self::parseLine($line);

            if (!empty($tag)) {
                $lines[] = $tag;
            }
        }

        return $lines;
    }
}
