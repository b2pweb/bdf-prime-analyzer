<?php

namespace Bdf\Prime\Analyzer\Testing\DumpFormat;

use Bdf\Collection\Stream\Collector\Joining;
use Bdf\Collection\Stream\Streams;

/**
 * Dump the report in an HTML file
 */
final class HtmlDumpFormat implements DumpFormatInterface
{
    /**
     * @var string
     */
    private $filename;

    /**
     * HtmlDumpFormat constructor.
     *
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * {@inheritdoc}
     */
    public function dump(array $reports): void
    {
        $file = fopen($this->filename, 'w');

        $date = new \DateTime();
        fwrite($file, <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>Prime analyser report {$date->format(\DateTime::ATOM)}</title>
        <meta charset='utf-8'>
    </head>
    <body>
        <h1>Prime analyser report {$date->format(\DateTime::ATOM)}</h1>

HTML
);

        if (empty($reports)) {
            fwrite($file, 'No prime reports');
        } else {
            $count = count($reports);
            fwrite($file, "Prime reports ({$count}) : ".PHP_EOL);

            foreach ($reports as $report) {
                fwrite($file, "<fieldset><legend>{$report->file()}:{$report->line()}");

                if ($report->entity()) {
                    fwrite($file, ' on '.$report->entity());
                }

                fwrite($file, ' (called '.$report->calls().' times)</legend>');

                $code = Streams::wrap(file($report->file()))
                    ->skip(max($report->line() - 3, 0))
                    ->limit(7)
                    ->collect(new Joining())
                ;

                $code = highlight_string('<?php'.PHP_EOL.$code, true);
                $code = str_replace('&lt;?php<br />', '', $code);

                fwrite($file, $code);


                fwrite($file, '<ul>');

                foreach ($report->errors() as $error) {
                    fwrite($file, '<li>'.$error.'</li>');
                }

                fwrite($file, '</ul>');
                fwrite($file, '<pre>'.
                    Streams::wrap($report->stackTrace())
                        ->map(function (array $trace, $key) {
                            $out = '#'.$key.' ';

                            if (isset($trace['file'])) {
                                $out .= $trace['file'].':'.$trace['line'];
                            } else {
                                $out .= '[internal]';
                            }

                            $out .= ': ';

                            if (isset($trace['class'])) {
                                $out .= $trace['class'].$trace['type'];
                            }

                            $out .= $trace['function'].'()';

                            return $out;
                        })
                        ->collect(new Joining(PHP_EOL))
                .'</pre>');
                fwrite($file, '</fieldset>');
            }
        }

        fwrite($file, '</body></html>');
    }
}
