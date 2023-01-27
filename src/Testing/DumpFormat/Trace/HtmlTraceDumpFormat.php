<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat\Trace;

use Bdf\Prime\Analyzer\Testing\DumpFormat\DumpFormatInterface;

use Doctrine\SqlFormatter\HtmlHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

use function explode;
use function file_get_contents;
use function implode;
use function str_contains;
use function strrchr;
use function strtr;
use function substr;

/**
 * Dump call trace in json format
 */
final class HtmlTraceDumpFormat implements DumpFormatInterface
{
    private SqlFormatter $formatter;

    public function __construct(private string $filename)
    {
        $this->formatter = new SqlFormatter(new HtmlHighlighter());
    }

    /**
     * {@inheritdoc}
     */
    public function dump(array $reports): void
    {
        $trace = new Trace();

        foreach ($reports as $report) {
            $trace->push($report);
        }

        $table = <<<HTML
<table>
    <thead>
        <tr>
            <th>Level</th></th><th>Function</th><th>Calls</th><th>Called entities</th>
        </tr>
    </thead>
    <tbody>
        {$this->renderTrace($trace, $trace->calls(), (int) ($trace->calls() / count($trace->calling())))}
    </tbody>
</table>
HTML;

        file_put_contents(
            $this->filename,
            strtr(
                file_get_contents(__DIR__ . '/../Template/html_trace_dump.html'),
                [
                    '{{body}}' => $table,
                ]
            )
        );
    }

    /**
     * Render a trace row and its children
     *
     * @param Trace $trace Trace to render
     * @param int $totalCalls Total query calls of the parent trace
     * @param int $meanCalls Mean query calls of the parent trace (i.e. total calls / number of children)
     * @param int $level Function call level on the trace tree
     * @param string|null $parentId The parent row id
     *
     * @return string
     */
    private function renderTrace(Trace $trace, int $totalCalls, int $meanCalls, int $level = 0, ?string $parentId = null): string
    {
        $id = spl_object_hash($trace);

        $row = <<<HTML
        <tr id="{$id}" data-parent-id="{$parentId}" data-function-name="{$trace->function()}">
            <td>{$level}</td>
            <td class="function-name" style="--call-level: {$level}">{$this->renderFunctionName($trace->function())}</td>
            <td>
                <details>
                    <summary>{$trace->calls()} <meter max="{$totalCalls}" high="{$meanCalls}" value="{$trace->calls()}"></meter></summary>
                    <pre>{$this->renderQueries($trace->queries())}</pre>
                </details>
            </td>
            <td class="calls-by-entity">{$this->renderCallsByEntity($trace->callsByEntity())}</td>
        </tr>
        HTML;

        if (!$trace->calling()) {
            return $row;
        }

        $meanChildCalls = (int) ($trace->calls() / count($trace->calling()));

        foreach ($trace->calling() as $calling) {
            $row .= $this->renderTrace($calling, $trace->calls(), $meanChildCalls, $level + 1, $id);
        }

        return $row;
    }

    /**
     * Get simple name of a fully qualified class name (i.e. last part of the name)
     *
     * @param string $className Class name
     * @return string
     */
    private function simpleName(string $className): string
    {
        return str_contains($className, '\\') ? substr(strrchr($className, '\\'), 1) : $className;
    }

    /**
     * Render the list of called entities
     *
     * @param array<class-string, int> $callsByEntities
     * @return string
     */
    private function renderCallsByEntity(array $callsByEntities): string
    {
        $out = [];

        foreach ($callsByEntities as $entity => $calls) {
            $out[] = '<span class="class-name" title="' . $entity . '">' . $this->simpleName($entity) . '</span> (x'.$calls.')';
        }

        return implode(', ', $out);
    }

    /**
     * Render method call name using simple name for class
     *
     * @param string $function Function name
     * @return string
     */
    public function renderFunctionName(string $function): string
    {
        if (str_contains($function, '->')) {
            [$class, $method] = explode('->', $function);

            return '<span class="class-name" title="' . $class . '">' . $this->simpleName($class).'</span>-&gt;'.$method;
        }

        if (str_contains($function, '::')) {
            [$class, $method] = explode('::', $function);

            return '<span class="class-name" title="' . $class . '">' . $this->simpleName($class).'</span>::'.$method;
        }

        return $function;
    }

    /**
     * Render SQL queries to highlight them
     *
     * @param list<string> $queries
     *
     * @return string
     */
    public function renderQueries(array $queries): string
    {
        $out = [];

        foreach ($queries as $query) {
            $out[] = $this->formatter->format($query);
        }

        return implode('<br />', $out);
    }
}
