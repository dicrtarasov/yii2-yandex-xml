<?php
/*
 * @copyright Igor A Tarasov <develop@dicr.org>
 * @version 10.10.20 08:17:44
 */

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

/**  */
define('YII_ENV', 'dev');

/** bool */
define('YII_DEBUG', true);

require_once(dirname(__DIR__) . '/vendor/autoload.php');
require_once(dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php');

/** @noinspection SpellCheckingInspection */
new yii\console\Application([
    'id' => 'test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'cache' => yii\caching\FileCache::class,
        'log' => [
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', 'info', 'trace', 'profile']
                ]
            ]
        ],
        'urlManager' => ['hostInfo' => 'https://dicr.org'],
        'yandexXml' => [
            'class' => dicr\yandex\xml\YandexXML::class,
            'login' => 'dicr',
            'apiKey' => '03.151090929:3aeb9deea3ff79c4e9c50a733856b643'
        ]
    ],
    'bootstrap' => ['log']
]);
