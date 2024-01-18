<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Arguments\Rector\MethodCall\RemoveMethodCallParamRector;
use Rector\Arguments\Rector\ClassMethod\ReplaceArgumentDefaultValueRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->autoloadPaths([
        __DIR__ . '/vendor/squizlabs/php_codesniffer/autoload.php',
        __DIR__ . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php',
        __DIR__ . '/vendor/php-stubs/php-stubs/acf-pro-stubs/acf-pro-stubs.php',
    ]);

    $rectorConfig->paths([
        __DIR__ . '/',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor'
    ]);

    $rectorConfig->sets([
        SetList::PHP_74,
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::NAMING,
        SetList::TYPE_DECLARATION
    ]);

    $rectorConfig->skip([
        CallableThisArrayToAnonymousFunctionRector::class,
        StaticClosureRector::class,
        ClosureToArrowFunctionRector::class
    ]);

    $rectorConfig->rule(RemoveMethodCallParamRector::class);
    $rectorConfig->rule(ReplaceArgumentDefaultValueRector::class);
};
