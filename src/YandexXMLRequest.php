<?php
/*
 * @copyright Igor A Tarasov <develop@dicr.org>
 * @version 10.10.20 09:04:27
 */

declare(strict_types = 1);
namespace dicr\yandex\xml;

use dicr\validate\ValidateException;
use SimpleXMLElement;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;

use function array_keys;
use function array_merge;
use function ceil;
use function implode;
use function in_array;
use function simplexml_load_string;
use function sprintf;
use function time;
use function usleep;

/**
 * Запрос результатов поиска.
 *
 * @link https://yandex.ru/dev/xml/doc/dg/concepts/get-request.html
 */
class YandexXMLRequest extends Model implements YandexTypes
{
    /**
     * @var string поисковый запрос
     * На запрос наложены следующие ограничения:
     * максимальная длина запроса — 400 символов; максимальное количество слов — 40.
     */
    public $query;

    /**
     * @var ?string язык интерфейса результатов поиска.
     *
     * Язык уведомлений поискового ответа.
     * Влияет на текст, передаваемый в теге found-docs-human, а также в сообщениях об ошибках.
     *
     * Возможные значения зависят от используемого типа поиска:
     * - «русский (yandex.ru)» — «ru» (русский), «uk» (украинский), «be» (белорусский), «kk» (казахский).
     * Если не задан, уведомления передаются на русском языке;
     * - «турецкий (yandex.com.tr)» — поддерживается только значение «tr» (турецкий);
     * - «мировой (yandex.com)» — поддерживается только значение «en» (английский).
     */
    public $lang;

    /**
     * @var ?int регион поиска
     *
     * Поддерживается только для типов поиска «русский» и «турецкий».
     *
     * Идентификатор страны или региона поиска. Определяет правила ранжирования документов.
     * Например, если передать в данном параметре значение «11316» (Новосибирская область),
     * при формировании результатов поиска используется формула, определенная для Новосибирской области.
     */
    public $region;

    /**
     * @var ?string режим фильтрации
     *
     * Правило фильтрации результатов поиска (исключение из результатов поиска документов
     * в соответствии с одним из правил). Если параметр не задан, используется умеренная фильтрация.
     */
    public $filter;

    /**
     * @var ?string режим сортировки.
     * Если параметр не задан, результаты сортируются по релевантности.
     */
    public $sort;

    /** @var ?string порядок сортировки */
    public $order;

    /**
     * @var ?string режим группировки документов в результате.
     *
     * Набор параметров, определяющих правила группировки результатов. Группировка используются для
     * объединения документов одного домена в контейнер. В рамках контейнера документы ранжируются
     * по правилам сортировки, определенным в параметре sortby. Результаты, переданные в контейнере,
     * могут быть использованы для включения в поисковую выдачу нескольких документов одного домена.
     */
    public $group;

    /** @var ?int максимальное кол-во документов в группе */
    public $docs;

    /**
     * @var ?int кол-во пассажей (сниппетов) в документе.
     * Максимальное количество пассажей, которое может быть использовано при формировании сниппета
     * к документу. Пассаж — это фрагмент найденного документа, содержащий слова запроса. Пассажи
     * используются для формирования сниппетов — текстовых аннотаций к найденному документу.
     *
     * Допустимые значения — от 1 до 5. Результат поиска может содержать меньшее количество пассажей,
     * чем значение, указанное в данном параметре.
     *
     * Если параметр не задан, для каждого документа возвращается не более четырех пассажей с текстом запроса.
     */
    public $snippets;

    /** @var ?int кол-во результатов на странице */
    public $limit;

    /**
     * @var ?int номер страницы.
     *
     * Номер запрашиваемой страницы поисковой выдачи. Определяет диапазон позиций документов, возвращаемых по запросу.
     * Нумерация начинается с нуля (первой странице соответствует значение «0»).
     */
    public $page;

    /**
     * @var ?bool показывать капчу.
     * Инициирует проверку пользователя для возможной защиты от роботов.
     */
    public $showCaptcha;

    /** @var YandexXML */
    private $_yandexXml;

    /**
     * Конструктор
     *
     * @param YandexXML $yandexXml
     * @param array $config
     */
    public function __construct(YandexXML $yandexXml, array $config = [])
    {
        $this->_yandexXml = $yandexXml;

        parent::__construct($config);
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels() : array
    {
        return array_merge(parent::attributeLabels(), [
            'query' => 'Поисковый запрос',
            'lang' => 'Язык',
            'region' => 'Регион поиска',
            'filter' => 'Фильтрация',
            'sort' => 'Сортировка',
            'order' => 'Порядок',
            'group' => 'Группировка',
            'docs' => 'Документов в группе',
            'snippets' => 'Сниппетов',
            'limit' => 'Результатов',
            'page' => 'Страница',
            'showCaptcha' => 'Показывать капчу'
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function attributeHints() : array
    {
        return [
            'lang' => 'Язык результатов, по-умолчанию зависит от сервера',
            'region' => 'По-умолчанию по геолокации',
            'filter' => 'По-умолчанию умеренная',
            'sort' => 'По-умолчанию по релевантности',
            'order' => 'Только при сортировке по времени. По-умолчанию новые вначале',
            'group' => 'По-умолчанию Яндекс группирует результаты по доменам',
            'docs' => 'Макс. кол-во документов в группе',
            'snippets' => 'Макс. кол-во сниппетов в документе',
            'limit' => sprintf('По-умолчанию %d', self::LIMIT_DEFAULT),
            'page' => sprintf('Номер страницы, начиная с %d', self::PAGE_MIN),
            'showCaptcha' => 'Показывать капчу для проверки'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function rules() : array
    {
        return [
            ['query', 'trim'],
            ['query', 'required'],
            ['query', 'string', 'max' => self::QUERY_MAX],

            ['lang', 'trim'],
            ['lang', 'default'],
            ['lang', function ($attribute) {
                if ($this->_yandexXml->server === self::SERVER_RU) {
                    $range = [self::LANG_RU, self::LANG_UK, self::LANG_BE, self::LANG_KK];
                } elseif ($this->_yandexXml->server === self::SERVER_TR) {
                    $range = [self::LANG_TR];
                } else {
                    $range = [self::LANG_EN];
                }

                if (! in_array($this->lang, $range, true)) {
                    $this->addError($attribute, 'Для текущего типа поиска ' . $this->_yandexXml->server .
                        ' доступны следующие языки: ' . implode(',', $range) . '.'
                    );
                }
            }, 'skipOnEmpty' => true],

            ['region', 'trim'],
            ['region', 'default'],
            ['region', 'integer', 'min' => 1],
            ['region', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],
            ['region', function ($attribute) {
                if (! in_array($this->_yandexXml->server, [self::SERVER_RU, self::SERVER_TR], true)) {
                    $this->addError($attribute, 'Для текущего сервера поиска "' .
                        $this->_yandexXml->server . '" этот параметр недоступен.');
                }
            }, 'skipOnEmpty' => true],

            ['filter', 'trim'],
            ['filter', 'default'],
            ['filter', 'in', 'range' => array_keys(self::FILTERS)],

            ['sort', 'trim'],
            ['sort', 'default'],
            ['sort', 'in', 'range' => array_keys(self::SORTS)],

            ['order', 'trim'],
            ['order', 'default'],
            ['order', 'in', 'range' => array_keys(self::ORDERS)],

            ['group', 'trim'],
            ['group', 'default'],
            ['group', 'in', 'range' => array_keys(self::GROUPS)],

            ['docs', 'default'],
            ['docs', 'integer', 'min' => self::DOCS_MIN, 'max' => self::DOCS_MAX],
            ['docs', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            ['snippets', 'default'],
            ['snippets', 'integer', 'min' => self::SNIPPETS_MIN, 'max' => self::SNIPPETS_MAX],
            ['snippets', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            ['limit', 'default'],
            ['limit', 'integer', 'min' => self::LIMIT_MIN, 'max' => self::LIMIT_MAX],
            ['limit', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            ['page', 'default'],
            ['page', 'integer', 'min' => self::PAGE_MIN],
            ['page', 'filter', 'filter' => 'intval', 'skipOnEmpty' => true],

            ['showCaptcha', 'default'],
            ['showCaptcha', 'boolean'],
            ['showCaptcha', 'filter', 'filter' => 'boolval', 'skipOnEmpty' => true]
        ];
    }

    /**
     * Создает параметры запроса из значений аттрибутов.
     *
     * @return string[]
     * @throws InvalidConfigException
     */
    private function params() : array
    {
        if (empty($this->_yandexXml->login)) {
            throw new InvalidConfigException('login');
        }

        if (empty($this->_yandexXml->apiKey)) {
            throw new InvalidConfigException('apiKey');
        }

        $query = [
            'user' => $this->_yandexXml->login,
            'key' => $this->_yandexXml->apiKey,
            'query' => $this->query,
            'l10n' => $this->lang,
            'lr' => $this->region,
            'filter' => $this->filter,
            'maxpassages' => $this->snippets,
            'page' => $this->page,

        ];

        if ($this->sort !== null || $this->order !== null) {
            $query['sortby'] = $this->sort ?? self::SORT_DEFAULT;

            if ($query['sortby'] === self::SORT_TM && $this->order !== null) {
                $query['sortby'] .= '.order=' . $this->order;
            }
        }

        if ($this->group !== null || $this->docs !== null || $this->limit !== null) {
            $query['groupby'] = implode('.', [
                sprintf('attr=%s', ($this->group ?? self::GROUP_DEFAULT) === self::GROUP_DEEP ? 'd' : '""'),
                sprintf('mode=%s', $this->group ?: self::GROUP_DEFAULT),
                sprintf('groups-on-page=%d', $this->limit ?: self::LIMIT_DEFAULT),
                sprintf('docs-in-group=%d', $this->group === self::GROUP_DEEP ? $this->docs : self::DOCS_MIN)
            ]);
        }

        if (! empty($this->showCaptcha)) {
            $query['showmecaptcha'] = 'yes';
        }

        return $query;
    }

    /**
     * Получить/сохранить время последнего запроса.
     *
     * @param bool $update обновить текущее
     * @return ?int
     */
    private function lastRequestTimestamp(bool $update = false) : ?int
    {
        $timestamp = null;

        if ($update) {
            $timestamp = time();
            $this->_yandexXml->cache->set(__METHOD__, $timestamp);
        } else {
            $ret = $this->_yandexXml->cache->get(__METHOD__);
            if ($ret !== false) {
                $timestamp = (int)$ret;
            }
        }

        return $timestamp;
    }

    /**
     * Обрабатывает запрос
     *
     * @throws Exception
     * @noinspection PhpUndefinedFieldInspection
     */
    public function send() : SimpleXMLElement
    {
        if (! $this->validate()) {
            throw new ValidateException($this);
        }

        // создаем HTTP-запрос
        $request = $this->_yandexXml->httpClient->get('/search/xml', $this->params());
        $cacheKey = $request->toString();

        // получаем ответ из кеша
        $content = $this->_yandexXml->cache->get($cacheKey);
        if ($content === false) {
            // пауза перед запросом
            $lastRequestTimestamp = $this->lastRequestTimestamp();
            if ($lastRequestTimestamp !== null) {
                $requiredDelay = $this->_yandexXml->requestDelay;
                if ($requiredDelay > 0) {
                    $currentDelay = time() - $lastRequestTimestamp;
                    if ($currentDelay < $requiredDelay) {
                        $delay = $requiredDelay - $currentDelay;
                        Yii::debug('Пауза ' . $delay . ' секунд', __METHOD__);
                        usleep((int)ceil($delay * 1000000));
                    }
                }
            }

            // отправляем запрос
            Yii::debug('Запрос: ' . $request->toString(), __METHOD__);
            $response = $request->send();
            Yii::debug('Ответ: ' . $response->toString(), __METHOD__);

            if (! $response->isOk) {
                throw new Exception('Ошибка HTTP: ' . $response->statusCode);
            }

            // сохраняем время последнего запроса
            $this->lastRequestTimestamp(true);

            // сохраняем ответ в кеше
            $content = $response->content;
            $this->_yandexXml->cache->set(
                $cacheKey, $content, $this->_yandexXml->cacheResultsDuration
            );
        }

        // парсим XML
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            throw new Exception('Ошибка XML: ' . $content);
        }

        if (isset($xml->response->error)) {
            throw new Exception('Ошибка: ' . $xml->response->error);
        }

        return $xml;
    }
}
