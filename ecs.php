<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return ECSConfig::configure()
    ->withParallel() // make ECS 16x faster
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/src/DependencyInjection/Configuration.php',
        __DIR__ . '/tests/bootstrap.php',
        NotOperatorWithSuccessorSpaceFixer::class,
        ArrayOpenerAndCloserNewlineFixer::class,
        ArrayListItemNewlineFixer::class,
    ])
    ->withSets([SetList::COMMON, SetList::CLEAN_CODE, SetList::DOCBLOCK, SetList::PSR_12])
    ->withRules([
        GlobalNamespaceImportFixer::class,
        DeclareStrictTypesFixer::class,
    ])
    ->withConfiguredRule(ArraySyntaxFixer::class, [
        'syntax' => 'short',
    ])
    ->withConfiguredRule(PhpdocLineSpanFixer::class, [
        'property' => 'single',
    ])
    ->withConfiguredRule(ClassAttributesSeparationFixer::class, [
        'elements' => [
            'method' => 'one',
            'property' => 'only_if_meta',
            'const' => 'only_if_meta',
        ],
    ])
    ->withConfiguredRule(LineLengthFixer::class, [
        LineLengthFixer::LINE_LENGTH => 140,
    ])
    ;
