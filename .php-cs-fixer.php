<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->exclude(['Entity'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        'single_quote' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
    ])
    ->setFinder($finder)
;
