<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['packages', 'l10n'])
    ->name('*.php')
    ->name('*.inc')
    ->name('*.install')
    ->name('*.module');

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PHP73Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'heredoc_indentation' => ['indentation' => 'same_as_start'],
        'list_syntax' => false,
        'visibility_required' => false,
        'no_whitespace_before_comma_in_array' => false,
        'method_argument_space' => false,
        'trailing_comma_in_multiline' => false,
        'whitespace_after_comma_in_array' => false,
    ])
    ->setFinder($finder)
    ->setIndent('  ') // 2 spaces as per your guidelines
    ->setLineEnding("\n");