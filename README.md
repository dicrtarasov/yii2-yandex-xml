# Yandex XML поиск для Yii2

- Документация: https://tech.yandex.ru/xml/

## Настройка компонента

```php
'components' => [
    'yandexXml' => [
        'class' => dicr\yandex\xml\YandexXML::class,
        'login' => 'ваш_логин',
        'apiKey' => 'ваш_ключ_api'
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
