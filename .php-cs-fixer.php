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
        '@PSR2' => true,
        'braces_position' => [
          'allow_single_line_anonymous_functions' => true,
          'allow_single_line_empty_anonymous_classes' => true,
          'anonymous_classes_opening_brace' => 'same_line',
          'anonymous_functions_opening_brace' => 'same_line',
          'classes_opening_brace' => 'same_line',
          'control_structures_opening_brace' => 'same_line',
          'functions_opening_brace' => 'same_line',
        ],
        'class_definition' => [
          'inline_constructor_arguments' => true,
          'multi_line_extends_each_single_line' => false,
          'single_item_single_line' => true,
          'single_line' => true,
          'space_before_parenthesis' => true,
        ],
        'constant_case' => [
          'case' => 'upper',
        ],
        'control_structure_continuation_position' => [
          'position' => 'next_line',
        ],
        'function_declaration' => [
          'closure_fn_spacing' => 'one',
          'closure_function_spacing' => 'one',
          'trailing_comma_single_line' => false,
        ],
        'method_argument_space' => [
          'after_heredoc' => true,
          'attribute_placement' => 'ignore',
          'keep_multiple_spaces_after_comma'=> false,
          'on_multiline' => 'ensure_fully_multiline',
        ],
        'modifier_keywords' => [
          'elements' => ['const', 'method', 'property'],
        ],
        'single_class_element_per_statement' => [
          'elements' => ['property'],
        ],
        'single_space_around_construct' => [
          'constructs_followed_by_a_single_space' => ['abstract', 'as', 'case', 'catch', 'class', 'do', 'else', 'elseif', 'final', 'for', 'foreach', 'function', 'if', 'interface', 'namespace', 'private', 'protected', 'public', 'static', 'switch', 'trait', 'try', 'use_lambda', 'while'],
          'constructs_preceded_by_a_single_space' => ['as', 'else', 'elseif', 'use_lambda'],
        ],
        'spaces_inside_parentheses' => [
          'space' => 'none',
        ],
        # some psr12 rules
        'binary_operator_spaces' => [
          'default' => 'at_least_single_space',
        ],
        'compact_nullable_type_declaration' => true,
        'lowercase_cast' => true,
        'lowercase_static_reference' => true,
        'new_with_parentheses' => [
          'anonymous_class' => true,
          'named_class' => true,
        ],
        'no_extra_blank_lines' => true,
        'no_whitespace_in_blank_line' => true,
        'return_type_declaration' => [
          'space_before' => 'none',
        ],
        'short_scalar_cast' => true,
        'ternary_operator_spaces' => true,
        'unary_operator_spaces' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setIndent('  ') // 2 spaces as per your guidelines
    ->setLineEnding("\n");
