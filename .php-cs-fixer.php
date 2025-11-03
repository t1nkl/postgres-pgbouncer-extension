<?php

$finder = Symfony\Component\Finder\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude([
        'vendor',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,

        // Base hygiene
        'declare_strict_types' => false,
        'blank_line_after_opening_tag' => true,
        'encoding' => true,
        'full_opening_tag' => true,
        'indentation_type' => true,
        'line_ending' => true,

        // Modern syntax
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],
        'compact_nullable_type_declaration' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'native_type_declaration_casing' => true,
        'native_function_casing' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],

        // Spacing & alignment
        'array_indentation' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'method_argument_space' => true,
        'cast_spaces' => ['space' => 'single'],
        'concat_space' => ['spacing' => 'one'],
        'control_structure_continuation_position' => true,
        'spaces_inside_parentheses' => false,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays'],
            'after_heredoc' => true,
        ],
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'single_space_around_construct' => true,

        // Braces & positioning (new names)
        'braces_position' => true,
        'declare_equal_normalize' => true,
        'declare_parentheses' => true,

        // Cleanliness & readability
        'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
        'multiline_comment_opening_closing' => true,
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'clean_namespace' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_after_function_name' => true,
        'no_spaces_around_offset' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'object_operator_without_whitespace' => true,

        // Imports / namespaces
        'ordered_imports' => ['sort_algorithm' => 'alpha', 'imports_order' => ['class', 'function', 'const']],
        'no_unused_imports' => true,
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,

        // PHPDoc strictness
        'phpdoc_indent' => true,
        'phpdoc_line_span' => ['const' => 'single', 'method' => 'multi', 'property' => 'single'],
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => ['replacements' => ['type' => 'var']],
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'alpha'],
        'no_superfluous_phpdoc_tags' => ['remove_inheritdoc' => true, 'allow_mixed' => true],

        // Class organization
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
            ],
        ],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
            ],
            'sort_algorithm' => 'none',
        ],
        'modifier_keywords' => ['elements' => ['property', 'method', 'const']],

        // Control structures
        'elseif' => true,
        'no_alternative_syntax' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'switch_continue_to_break' => true,
        'no_unneeded_braces' => true,
        'no_unneeded_control_parentheses' => true,
        'no_superfluous_elseif' => true,

        // Misc
        'heredoc_to_nowdoc' => true,
        'include' => true,
        'lowercase_cast' => true,
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'return_type_declaration' => true,
        'short_scalar_cast' => true,
        'single_blank_line_at_eof' => true,
        'standardize_not_equals' => true,
        'statement_indentation' => true,

        // Risky (recommended)
        'logical_operators' => true,
        'no_alias_functions' => true,
        'strict_param' => true,
        'void_return' => true,
        'modernize_strpos' => true,
        'no_unreachable_default_argument_value' => true,

        'strict_comparison' => true,
        'date_time_immutable' => true, // prefer DateTimeImmutable over DateTime when safe
        'get_class_to_class_keyword' => true, // get_class($this) -> self::class
        'set_type_to_cast' => true, // settype($v, 'int') -> (int) $v
        'regular_callable_call' => true, // call_user_func('foo', $x) -> foo($x)

        'no_null_property_initialization' => true, // don’t initialize typed nullable props to null redundantly

        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_useless_concat_operator' => true,
        'psr_autoloading' => true,
    ])
    ->setLineEnding("\n")
    ->setFinder($finder);
