# Prime Analyzer
[![build](https://github.com/b2pweb/bdf-prime-analyzer/actions/workflows/php.yml/badge.svg)](https://github.com/b2pweb/bdf-prime-analyzer/actions/workflows/php.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/b2pweb/bdf-prime-analyzer/badges/quality-score.png?b=1.0)](https://scrutinizer-ci.com/g/b2pweb/bdf-prime-analyzer/?branch=1.0)
[![Code Coverage](https://scrutinizer-ci.com/g/b2pweb/bdf-prime-analyzer/badges/coverage.png?b=1.0)](https://scrutinizer-ci.com/g/b2pweb/bdf-prime-analyzer/?branch=1.0)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-prime-analyzer.svg)](https://packagist.org/packages/b2pweb/bdf-prime-analyzer)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-prime-analyzer.svg)](https://packagist.org/packages/b2pweb/bdf-prime-analyzer)
[![Type Coverage](https://shepherd.dev/github/b2pweb/bdf-prime-analyzer/coverage.svg)](https://shepherd.dev/github/b2pweb/bdf-prime-analyzer)

Analyse executed queries during runtime to report suspicious queries or optimisations tips.

## Usage

Install using composer

```
composer require --dev b2p/bdf-prime-analyzer
```

Register on unit test case

```php
    public static function createApplication()
    {
        $app = require __DIR__ . '/../../app/application.php';
        // ...
        $app->register(new \Bdf\Prime\Analyzer\PrimeAnalyzerServiceProvider([
            'ignoredPath' => [dirname(__DIR__)],
            'dumpFormats' => [
                new \Bdf\Prime\Analyzer\Testing\DumpFormat\HtmlDumpFormat(__DIR__.'/prime-report.html'),
                new \Bdf\Prime\Analyzer\Testing\DumpFormat\ConsoleDumpFormat(),
            ],
            'ignoredAnalysis' => [],
        ]));
        
        // ...

        return $app;
    }
```

To ignore analysis on a single query, simple add the `@prime-analyzer-ignore [analisys]` tag on the execute line.

```php
$credentials = $device->relation('credentials')->first(); // @prime-analyzer-ignore optimisation sort
```

You can also ignore analysis globally on the entity, using the docblock of the Mapper class.
Unlike the query inline ignore, parameters can be set to ignore only the listed attributes.

```php
/**
 * Mapper for TourOperation
 *
 * @prime-analyzer-ignore sort position
 * @prime-analyzer-ignore not_declared my_raw_attribute
 * @prime-analyzer-ignore index
 */
class TourOperationMapper extends Mapper
{
    // ...
}
```

## Options

Available options to set on the service provider registration :

- **ignoredPath** : List of path to ignore on the query analysis. For example the tests directory.
- **ignoredAnalysis** : List of analysis to ignore. See AnalysisTypes. AnalysisTypes::optimisations() can be used to only report dangerous queries.
- **dumpFormats** :  List of DumpFormatInterface instances. 
    * ConsoleDumpFormat : for dump in console after the end of tests
    * HtmlDumpFormat : for dump into an HTML file
- **register** : Set false to disable reporting

## Analysis

- **like** : Analyse the LIKE filters, and dump values without wildcard _ or %.
- **index** : Dump queries without any indexes. Queries with at least one relation or join are ignored.
- **not_declared** : Dump use of undeclared attributes (i.e. attributes not in mapper).
- **sort** : Dump queries with an ORDER clause on non-indexed attribute. You may ignore those reports if the returned set is small.
- **or** : Dump queries with an OR clause not nested into parenthesis.
- **optimisation** : Dump queries which can be optimised using KeyValueQuery or findById. See limitations for more information.
- **relation_distant_key** : Dump queries using the distant key of a relation instead of the local key. This cause a useless join. Do not handle "through" relations.
- **write** : Dump invalid update or insert queries. Analyse undeclared attributes and values types.
- **n+1** : Dump N+1 or loop queries. To raise an N+1, the queries must be at least called twice with the same stacktrace. Ignore only if the loop is desired (like walker).

## Limitations

- Do not handle raw expressions or join.
- **optimisation** may generate report on relation query, which cannot be fixed, and should be ignored.
- **n+1** may report a false positive in case of load() two relations on same entity (ex: $orderCustomer.load(['lading', 'delivery'])).
- Always verify indexes. The **index** analysis do not replace an **EXPLAIN**.
