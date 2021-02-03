<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license Apache-2.0
 * @version 03.02.21 21:17:28
 */

declare(strict_types = 1);
namespace dicr\yandex\xml;

use dicr\http\HttpCompressionBehavior;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\di\Instance;
use yii\httpclient\Client;

use function array_filter;
use function array_merge;
use function ceil;
use function date;
use function is_array;
use function simplexml_load_string;
use function strtotime;
use function time;
use function usleep;

/**
 * Компонент поиска Yandex XML API.
 *
 * @property-read Client $httpClient
 *
 * @property-read array $limitsSchedule расписание лимитов на текущие сутки. Каждый элемент имеет структуру:
 * int $from - timestamp начала периода
 * int $to - timestamp конца периода
 * int $count - кол-во запросов
 *
 * @property-read int $hourLimit лимит запросов в час для текущего периода
 * @property-read float $rpsLimit текущий лимит запросов в секунду
 * @property-read float $requestDelay текущая задержка между запросами в секунду
 *
 * @link https://tech.yandex.ru/xml/
 */
class YandexXML extends Component implements YandexTypes
{
    /** @var string */
    public $server = self::SERVER_DEFAULT;

    /** @var string логин Яндекс */
    public $login;

    /** @var string ключ API */
    public $apiKey;

    /** @var int время кеширования результатов поиска */
    public $cacheResultsDuration = 86400;

    /** @var array конфиг http-клиента */
    public $httpClientConfig = [
        'as compression' => HttpCompressionBehavior::class
    ];

    /** @var array конфиг запроса по-умолчанию */
    public $xmlRequestConfig = [];

    /** @var CacheInterface кэш */
    public $cache = 'cache';

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
    }

    /** @var Client */
    private $_httpClient;

    /**
     * HTTP-клиент.
     *
     * @return Client
     * @throws InvalidConfigException
     */
    public function getHttpClient(): Client
    {
        if (! isset($this->_httpClient)) {
            $this->_httpClient = Yii::createObject(array_merge([
                'class' => Client::class,
            ], $this->httpClientConfig));
        }

        // динамически устанавливаем базовый URL при каждом запросе
        $this->_httpClient->baseUrl = 'https://' . $this->server;

        return $this->_httpClient;
    }

    /**
     * Расписание лимитов на сегодня.
     *
     * @return array
     * @throws Exception
     */
    public function getLimitsSchedule(): array
    {
        // получаем расписание из кэша
        $schedule = $this->cache->get(__METHOD__);
        if (! is_array($schedule)) {
            // логин и ключ проверяем в момент запроса, позволяя менять его динамически
            if (empty($this->login)) {
                throw new InvalidConfigException('login');
            }

            if (empty($this->apiKey)) {
                throw new InvalidConfigException('apiKey');
            }

            $request = $this->httpClient->get('/search/xml', [
                'action' => 'limits-info',
                'user' => $this->login,
                'key' => $this->apiKey
            ]);

            Yii::debug('Запрос: ' . $request->toString(), __METHOD__);
            $response = $request->send();
            Yii::debug('Ответ: ' . $response->toString(), __METHOD__);

            if (! $response->isOk) {
                throw new Exception('HTTP-error: ' . $response->statusCode);
            }

            $xml = simplexml_load_string($response->content);
            if ($xml === false) {
                throw new Exception('Ошибка XML: ' . $response->content);
            }

            if (isset($xml->response->error)) {
                throw new Exception('Ошибка: ' . $xml->response->error);
            }

            $schedule = [];
            $endTime = null;

            foreach ($xml->response->limits->{'time-interval'} as $timeInterval) {
                $from = (int)strtotime((string)$timeInterval['from']);
                $to = (int)strtotime((string)$timeInterval['to']);

                $schedule[] = [
                    'from' => $from,
                    'to' => $to,
                    'count' => (int)$timeInterval
                ];

                if ($endTime === null || $to > $endTime) {
                    $endTime = $to;
                }
            }

            // сохраняем в кеше до последнего времени расписания
            if ($endTime !== null) {
                $this->cache->set(__METHOD__, $schedule, $endTime - time() - 1);
                Yii::debug('Расписание кэшировано до: ' . date('d.m.Y H:i:s', $endTime), __METHOD__);
            }
        }

        return $schedule;
    }

    /**
     * Лимит запросов на текущий час.
     *
     * @return int
     * @throws Exception
     */
    public function getHourLimit(): int
    {
        $schedule = $this->limitsSchedule;
        $time = time();
        foreach ($schedule as $period) {
            if ($period['from'] <= $time && $period['to'] >= $time) {
                return $period['count'];
            }
        }

        throw new Exception('Не найдено время в текущем расписании');
    }

    /**
     * Рассчитывает RPS (лимит запросов в секунду).
     * Ограничения действуют только для русского сервера.
     *
     * RPS = HourLimit / 2000
     *
     * @link https://yandex.ru/dev/xml/doc/dg/concepts/rps-limits.html
     * @return float
     */
    public function getRpsLimit(): float
    {
        // если текущий сервер не .ru, то ограничений в секунду нет
        return $this->server === YandexTypes::SERVER_RU ? $this->hourLimit / 2000 : 0;
    }

    /**
     * Рассчитывает задержку между запросами
     *
     * @return float задержка в секундах
     */
    public function getRequestDelay(): float
    {
        $rps = $this->rpsLimit;

        return $rps > 0 ? 1.0 / $rps : 0;
    }

    /**
     * Получить/сохранить данные модуля.
     *
     * @param ?array $data
     * @return array
     */
    private function moduleData(?array $data = null): array
    {
        $key = [__METHOD__, $this->apiKey];
        $currentData = Yii::$app->cache->get($key) ?: [];

        if ($data !== null) {
            $currentData = array_filter(
                array_merge($currentData, $data),
                static fn($val): bool => $val !== null
            );

            Yii::$app->cache->set($key, $currentData);
        }

        return $currentData;
    }

    /**
     * Пауза между запросами.
     */
    public function pause(): void
    {
        $requestDelay = $this->requestDelay;
        if ($requestDelay > 0) {
            $moduleData = $this->moduleData();
            $lastRequestTimestamp = (int)($moduleData['lastRequestTimestamp'] ?? 0);
            if ($lastRequestTimestamp > 0) {
                $pause = time() - $lastRequestTimestamp;
                if ($pause < $requestDelay) {
                    $pause = $requestDelay - $pause;
                    Yii::debug('Пауза: ' . $pause . ' сек', __METHOD__);
                    usleep((int)ceil($pause * 1000000));
                }
            }
        }

        $this->moduleData(['lastRequestTimestamp' => time()]);
    }

    /**
     * Создает XML-запрос на поиск.
     *
     * @param array $config
     * @return YandexXMLRequest
     * @throws InvalidConfigException
     */
    public function request(array $config = []): YandexXMLRequest
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::createObject(
            array_merge(['class' => YandexXMLRequest::class], $this->xmlRequestConfig, $config),
            [$this]
        );
    }
}
