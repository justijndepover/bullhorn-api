<?php

require __DIR__ . '/vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->notPath([
        'bootstrap/*',
        'storage/*',
        'vendor',
    ])
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/tests',
        __DIR__ . '/database',
    ])
    ->name('*.php')
    ->notName('*.blade.php');

return \Justijndepover\PHPCheck\Rules::all($finder);
