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

#### Ваше сообщение
**Внимание: _Это событие не надёжно, не советую его использовать!_**
```php
$FunPay->on(event::youreMessage, function (\run\messageRepository $msg) {
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
```

### Примечание
Авто поднятие и всегда онлайн работают,
Остальные события вы можете найти в файле events.php
По любым вопросам можете писать в дс TimLiz#2952 или создавать issue

**Удачи всем**
