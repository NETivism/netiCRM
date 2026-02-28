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
    # php migration
        '@PHP8x5Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'heredoc_indentation' => ['indentation' => 'same_as_start'],
        'list_syntax' => false,
        'visibility_required' => false,
        'no_whitespace_before_comma_in_array' => false,
        'method_argument_space' => false,
        'trailing_comma_in_multiline' => false,
        'whitespace_after_comma_in_array' => false,
        'assign_null_coalescing_to_coalesce_equal' => false, // 7.3 backward compatibility
        'octal_notation' => false, // 8.0 backward compatibility
        'nullable_type_declaration_for_default_null_value' => true, // 7.1+ compatibility, ?string nullable type declaration
    # php migration risky, shouldn't use ruleset
        'pow_to_exponentiation' => true,
        'combine_nested_dirname' => true,
        'implode_call' => true,
        'no_alias_functions' => true,
        'get_class_to_class_keyword' => false, // 7.4 backward compatibility
        'modernize_strpos' => false, // 7.4 backward compatibility
        'no_php4_constructor' => true,
        'no_unneeded_final_method' => true,
        'no_unreachable_default_argument_value' => true,
        'phpdoc_readonly_class_comment_to_keyword' => true,
        'modern_serialization_methods' => true,
    # psr rules
        '@PSR1' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setIndent('  ') // 2 spaces as per your guidelines
    ->setLineEnding("\n");