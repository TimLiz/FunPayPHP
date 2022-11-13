# FunPayPHP
Добро пожаловать в FunPay PHP! Эта библиотека позволяет удобно и легко создавать ботов для биржи FunPay

## Требования
```
1. PHP или выше 8.1
2. Curl-extension PHP
```

## Установка
**Composer**
```
composer require timliz/funpayphp
```

## Первый бот
```php
<?php
require_once ("vendor/autoload.php");

use run\messageRepository;
use run\run;

//Инициируем бота
$FunPay = new run();

//Добавляем прослушку события MESSAGE
$FunPay->on(Event::message, function (messageRepository $msg) {
    //Мы получили messageRepository от этого события

    //Отвечаем пользователю сообщением
    $msg->reply(\messageBuilder\messageBuilder::fastMessage("Hello world!"));
});

//Запускаем бота
$FunPay->run();
```

Пример выше отвечает Hello world! На сообщения.

## Таймеры
#### Задержки
```php
$FunPay->timers->addTimer(6, function () {
    //Код, который исполнится через 6 секунд
});
```

#### Циклы
```php
$ID = $FunPay->timers->addRepeated(1, function () {
    //Код, который будет исполняться каждую 1 секунду
});
```

## События
В основном примере вы видели пример события message, ниже я перечислю все события:

#### Сообщение
```php
$FunPay->on(event::message, function (\run\messageRepository $msg) {
   //Код, исполняющийся при событии
});
```

#### Оплата
```php
$FunPay->on(event::payment, function (\run\paymentRepository $payment) {
   //Код, исполняющийся при событии
});
```

#### Поднятие лота
```php
$FunPay->on(event::lotRise, function () {
   //Код, исполняющийся при событии
});
```

#### Главный цикл

```php
$FunPay->on(event::loop, function () {
   //Код, исполняющийся при событии
});
````

#### При готовности

```php
$FunPay->on(event::ready, function () {
   //Код, исполняющийся при событии
});
```

## Хранилище

paymentRepository & userRepository

Позволяет записывать данные для долгого хранения

## Кастомный бесконечный цикл

#### Если вы хотите использовать API вместе с чем-то другим, что тоже запускает бесконечный цикл, используйте решение ниже

```php
<?php
require_once("vendor/autoload.php");

use run\run;

//Инициируем бота
$FunPay = new run(array(
    run::SETTINGS_CUSTOM_LOOP => true
));

//Код бота тут


//Не забываем про запуск бота
$FunPay->run();


//Далее вам необходимо привязать метод loop() к циклу другого скрипта,
//В моём примере я просто использую свой бесконечный цикл

while (true) {
    $FunPay->timers->loop();
}
```

## Редактирование лотов

```php
<?php
require_once('vendor/autoload.php');

use run\lot;
use run\run;

//Инициируем бота
$FunPay = new run();

//Прослушиваем событие ready
$FunPay->on(event::ready, function () use ($FunPay) {
    //Получаем лот с id 13914382
    $lot = lot::getLot(13914382);
    
    //Ставим кол-во 10
    $lot->amount = 10;
    
    //Сохранение лота(отправка на сервер)
    $lot->save();
});

$FunPay->run();
```

## Настройки

Если вы хотите, вы можете поменять некоторые настройки,
укажите как массив как первый аргумент для конструктора класса run

```php
$FunPay = new run(array(
    run::SETTINGS_DISABLE_LOT_RISE => true,
    run::SETTINGS_DISABLE_MESSAGE_CHECK => true
));
```

#### Отключить обработку сообщений

`run::SETTINGS_DISABLE_MESSAGE_CHECK`

#### Отключить поднятие лотов

`run::SETTINGS_DISABLE_LOT_RISE`

#### Отключить бесконечный цикл, вам прийдётся вызывать функцию loop() вручную см выше"

`run::SETTINGS_CUSTOM_LOOP`

### Примечание

Авто поднятие и всегда онлайн работают,
Остальные события вы можете найти в файле enums/event.php
По любым вопросам можете писать в Discord(TimLiz#2952) или создавать issue

**Удачи всем**


## Запланировано
() Добавить к paymentRepository метод getLotObjectTry2 который будет получать объект с помощью ссылки на таблицу на странице оплаты, в будующем заменит старый способ получения и будет всегда
