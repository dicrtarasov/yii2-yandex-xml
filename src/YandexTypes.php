<?php
/*
 * @copyright Igor A Tarasov <develop@dicr.org>
 * @version 10.10.20 09:10:36
 */

/**
 * @author Igor A Tarasov <develop@dicr.org>
 * @version 06.07.20 22:10:02
 */

declare(strict_types = 1);

namespace dicr\yandex\xml;

/**
 * Yandex Constants.
 */
interface YandexTypes
{
    /** @var int максимальная длина поискового запроса */
    public const QUERY_MAX = 400;

    /** @var string */
    public const SERVER_RU = 'yandex.ru';

    /** @var string */
    public const SERVER_TR = 'yandex.com.tr';

    /** @var string */
    public const SERVER_WD = 'yandex.com';

    /** @var string */
    public const SERVER_DEFAULT = self::SERVER_RU;

    /** @var string[] поисковые сервера */
    public const SERVERS = [
        self::SERVER_RU => 'русский',
        self::SERVER_TR => 'турецкий',
        self::SERVER_WD => 'мировой'
    ];

    /** @var string */
    public const LANG_RU = 'ru';

    /** @var string */
    public const LANG_UK = 'uk';

    /** @var string */
    public const LANG_BE = 'be';

    /** @var string */
    public const LANG_KK = 'kk';

    /** @var string */
    public const LANG_TR = 'tr';

    /** @var string */
    public const LANG_EN = 'en';

    /** @var string */
    public const LANG_DEFAULT = self::LANG_RU;

    /** @var string[] языки интерфейса */
    public const LANGS = [
        self::LANG_RU => 'русский',
        self::LANG_UK => 'украинский',
        self::LANG_BE => 'белорусский',
        self::LANG_KK => 'казахский',
        self::LANG_TR => 'турецкий',
        self::LANG_EN => 'английский'
    ];

    /**
     * @var string фильтрация отключена. В выдачу включаются любые документы, вне зависимости от содержимого;
     */
    public const FILTER_NONE = 'none';

    /**
     * @var string умеренная фильтрация. Из выдачи исключаются документы, относящиеся к категории «для взрослых»,
     * если запрос явно не направлен на поиск подобных ресурсов;
     */
    public const FILTER_MODERATE = 'moderate';

    /**
     * @var string семейный фильтр. Вне зависимости от поискового запроса из выдачи исключаются документы,
     * относящиеся к категории «для взрослых», а также содержащие ненормативную лексику.
     */
    public const FILTER_STRICT = 'strict';

    /** @var string */
    public const FILTER_DEFAULT = self::FILTER_MODERATE;

    /** @var string[] типы фильтров контента */
    public const FILTERS = [
        self::FILTER_NONE => 'нет',
        self::FILTER_MODERATE => 'умеренный',
        self::FILTER_STRICT => 'семейный'
    ];

    /** @var string */
    public const SORT_RLV = 'rlv';

    /** @var string */
    public const SORT_TM = 'tm';

    /** @var string */
    public const SORT_DEFAULT = self::SORT_RLV;

    /** @var string[] типы сортировки результатов */
    public const SORTS = [
        self::SORT_RLV => 'по релевантности',
        self::SORT_TM => 'по времени изменения'
    ];

    /** @var string старые в начале */
    public const ORDER_ASC = 'ascending';

    /** @var string по убыванию времени изменения (свежие вначале) */
    public const ORDER_DESC = 'descending';

    /** @var string */
    public const ORDER_DEFAULT = self::ORDER_DESC;

    /** @var string[] */
    public const ORDERS = [
        self::ORDER_DESC => 'сначала старые',
        self::ORDER_ASC => 'сначала новые'
    ];

    /** @var string не группировать документы одного домена */
    public const GROUP_FLAT = 'flat';

    /** @var string группировать документы по домену */
    public const GROUP_DEEP = 'deep';

    /** @var string */
    public const GROUP_DEFAULT = self::GROUP_DEEP;

    /** @var string[] группировка результатов */
    public const GROUPS = [
        self::GROUP_FLAT => 'без группировки',
        self::GROUP_DEEP => 'по доменам'
    ];

    /** @var int */
    public const DOCS_MIN = 1;

    /** @var int */
    public const DOCS_MAX = 3;

    /** @var int Кол-во документов в группе */
    public const DOCS_DEFAULT = self::DOCS_MIN;

    /** @var int */
    public const SNIPPETS_MIN = 1;

    /** @var int */
    public const SNIPPETS_MAX = 5;

    /** @var int кол-во пассажей (сниппетов) в документе */
    public const SNIPPETS_DEFAULT = self::SNIPPETS_MIN;

    /** @var int */
    public const LIMIT_MIN = 1;

    /** @var int */
    public const LIMIT_MAX = 100;

    /** @var int кол-во результатов на странице */
    public const LIMIT_DEFAULT = 50;

    /** Номер страницы */
    public const PAGE_MIN = 0;

    /** @var int */
    public const PAGE_DEFAULT = self::PAGE_MIN;

    /** Показывать капчу */
    public const SHOW_CAPTCHA_DEFAULT = false;
}
