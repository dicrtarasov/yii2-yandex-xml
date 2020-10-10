<?php
/*
 * @copyright Igor A Tarasov <develop@dicr.org>
 * @version 10.10.20 09:07:41
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::$app->get('yandexXml');
    }

    /**
     *
     * @throws Exception
     * @noinspection PhpUndefinedFieldInspection
     */
    public function testSend() : void
    {
        $yandexXml = self::yandexXml();

        $xmlRequest = $yandexXml->xmlRequest([
            'query' => 'dicr'
        ]);

        $xml = $xmlRequest->send();
        self::assertEquals('dicr', $xml->request->query);
        self::assertGreaterThan(1, count($xml->response->results->grouping->group));

        echo $xml->response->results->grouping->{'found-docs-human'} . "\n";
        //var_dump($xml);
    }
}
