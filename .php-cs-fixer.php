<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(
        [
            'vendor',
            'tests/Engine/EngineTests/EngineTestData',
        ]
    )
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config
    ->setRules(
        [
            '@PSR12' => true,
            'single_quote' => true,
            'no_trailing_comma_in_singleline' => true,
            'array_indentation' => true,
            'phpdoc_indent' => true,
            'no_superfluous_phpdoc_tags' => false,
            'phpdoc_no_empty_return' => false,
            'new_with_parentheses' => false,
        ]
    )
    ->setFinder($finder);
