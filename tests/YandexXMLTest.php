<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license Apache-2.0
 * @version 03.02.21 21:18:17
 */

declare(strict_types = 1);
namespace dicr\tests;

use dicr\yandex\xml\YandexXML;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Class YandexXMLTest
 */
class YandexXMLTest extends TestCase
{
    /**
     * @return YandexXML
     * @throws InvalidConfigException
     */
    private static function yandexXml() : YandexXML
    {
        return Yii::$app->get('yandexXml');
    }

    /**
     * @throws Exception
     * @noinspection PhpMethodMayBeStaticInspection
     * @noinspection PhpUnitMissingTargetForTestInspection
     */
    public function testLimits() : void
    {
        $yandexXml = self::yandexXml();

        $schedule = $yandexXml->limitsSchedule;
        self::assertIsArray($schedule);
        self::assertNotEmpty($schedule);

        echo 'Часовой лимит: ' . $yandexXml->hourLimit . "\n";
        echo 'Запросов в секунду: ' . $yandexXml->rpsLimit . "\n";
        echo 'Задержка запросов: ' . $yandexXml->requestDelay . " сек\n";
    }
}
