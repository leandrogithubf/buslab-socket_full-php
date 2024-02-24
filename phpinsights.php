<?php

declare(strict_types=1);

return [
    'preset' => 'symfony',
    'exclude' => [
        // default
        'vendor',
        'bower_components',
        'node_modules',
        // symfony
        'var/',
        'translations/',
        'config/',
        'public/',
        'src/Kernel.php',
        // phpinsigth
        'phpinsights.php',
        // topnode
        'src/Migrations',
        // topnode - configuration files (identation problem)
        'src/Topnode/AuthBundle/DependencyInjection/Configuration.php',
        'src/Topnode/BaseBundle/DependencyInjection/Configuration.php',
        'src/Topnode/FileBundle/DependencyInjection/Configuration.php',
        // topnode - maker
        'src/Topnode/BaseBundle/Resources/skeleton',
        'src/Topnode/BaseBundle/Maker/MakeTopCrud.php',
        'src/Topnode/BaseBundle/Maker/MakeTopEntity.php',
        'src/Topnode/BaseBundle/Utils/SourceManipulation/',
    ],
    'add' => [],
    'remove' => [
        \PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class,
        \SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff::class,
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff::class,
        \NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff::class,
        \SlevomatCodingStandard\Sniffs\Classes\ModernClassNameReferenceSniff::class,
        \SlevomatCodingStandard\Sniffs\ControlStructures\LanguageConstructWithParenthesesSniff::class,
        \NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits::class,
        \SlevomatCodingStandard\Sniffs\Classes\SuperfluousTraitNamingSniff::class,
        \SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff::class,
        \SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff::class,
        \SlevomatCodingStandard\Sniffs\Classes\SuperfluousAbstractClassNamingSniff::class,
        \NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses::class,
        \PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer::class,
        \SlevomatCodingStandard\Sniffs\PHP\UselessParenthesesSniff::class,
        \PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer::class,
    ],
    'config' => [
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 160,
            'ignoreComments' => false,
        ],
        \NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh::class => [
            'maxComplexity' => 15,
        ],
        \SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff::class => [
            'maxLinesLength' => 45,
        ],
    ],
    'requirements' => [
        'min-quality' => 70,
        'min-complexity' => 70,
        'min-architecture' => 70,
        'min-style' => 70,
        'disable-security-check' => false,
    ],
    'threads' => null,
];
