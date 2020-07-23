<?php
$header = <<<'EOF'
This file is part of WpAlgolia plugin.
(c) Antoine Girard for Mill3 Studio <antoine@mill3.studio>
@version 0.0.7
EOF;
return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'combine_consecutive_unsets' => true,
        'header_comment' => array('header' => $header, 'comment_type' => 'PHPDoc'),
        'array_syntax' => array('syntax' => 'long'),
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'psr4' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'binary_operator_spaces' => array(
            'align_double_arrow' => true,
        ),
    ))
    ->setFinder(
        PhpCsFixer\Finder::create()
        ->in(__DIR__)
        ->exclude('libs')
        ->notPath('algolia.php')
    )
;
