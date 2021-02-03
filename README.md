# Yandex XML поиск для Yii2

- Документация: https://tech.yandex.ru/xml/

## Настройка компонента

```php
$config = [
    'components' => [
        'yandexXml' => [
            'class' => dicr\yandex\xml\YandexXML::class,
            'login' => 'ваш_логин',
            'apiKey' => 'ваш_ключ_api'
        ]
    ]
];
```

## Поиск в Yandex

```php
use dicr\yandex\xml\YandexXML;

/** @var YandexXML $yandexXml получаем компонент */
$yandexXml = Yii::$app->get('yandexXml');

// создаем запрос
$request = $yandexXml->request([
    'query' => 'Мой поисковый запрос'
]);

// выводим результаты поиска
foreach ($request->results as $res) {
    echo 'Позиция: ' . $res['pos'] . "\n";
    echo 'URL: ' . $res['url'] . "\n";
}
```

## Расписание лимитов

```php
use dicr\yandex\xml\YandexXML;

/** @var YandexXML $yandexXml получаем компонент */
$yandexXml = Yii::$app->get('yandexXml');

echo "Расписание лимитов:\n";
foreach ($yandexXml->limitsSchedule as $item) {
    echo date('d.m.Y H:i', $item['from']) . ' - ' . date('H:i', $item['to']) . ': ' . $item['count'] . "\n";
}

echo 'Текущий лимит зап./час: ' . $yandexXml->hourLimit . "\n";
echo 'Текущий лимит зап./сек: ' . $yandexXml->rpsLimit . "\n";
echo 'Задержка между запросами, сек: ' . $yandexXml->requestDelay . "\n";
```
