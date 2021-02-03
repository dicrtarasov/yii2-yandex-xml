<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license Apache-2.0
 * @version 03.02.21 21:17:47
 */

declare(strict_types = 1);
namespace dicr\tests;

use dicr\yandex\xml\YandexXML;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

use function count;

/**
 * Class YandexXMLRequestTest
 */
class YandexXMLRequestTest extends TestCase
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
     *
     * @throws Exception
     */
    public function testSend() : void
    {
        $yandexXml = self::yandexXml();

        $xmlRequest = $yandexXml->request([
            'query' => 'dicr'
        ]);

        $xml = $xmlRequest->send();
        self::assertEquals('dicr', $xml->request->query);
        self::assertGreaterThan(1, count($xml->response->results->grouping->group));

        echo $xml->response->results->grouping->{'found-docs-human'} . "\n";
    }
}
