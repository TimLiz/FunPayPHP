<?php

use run\run;

require_once ("classes/run.php");

$FunPay = new run();

$ID = $FunPay->timers->addRepeated(1, function () {
    //Код, который будет исполняться каждую 1 секунду
});

$FunPay->timers->addTimer(6, function () {
    //Код, который исполнится через 6 секунд
});

$FunPay->on(event::payment, function (\run\paymentRepository $payment) {
   //Код, исполняющийся при событии
});

$FunPay->run();