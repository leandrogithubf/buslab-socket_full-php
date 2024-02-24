<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        '@PHP80Migration' => true,
        'no_unused_imports' => true,
        'concat_space' => [
          'spacing' => 'one'
        ],
        'yoda_style' => false,
        'no_alias_language_construct_call' => false,
        'phpdoc_align' => false,
    ])
    ->setFinder($finder)
;
