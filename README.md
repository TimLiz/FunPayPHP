# FunPayPHP
Добро пожаловать в FunPay PHP! Эта библиотека позволяет удобно и легко создавать ботов для биржи FunPay

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

    //Создаём сообщение
    $message = new messageBuilder\messageBuilder();
    $message->content = "Hello world!";

    //Отвечаем пользователю сообщением
    $msg->reply($message);
});

//Запускаем бота
$FunPay->run();
```

Пример выше отвечает Hello world! на сообщения.

**Удачи всем**
