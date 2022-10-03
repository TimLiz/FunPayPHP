<?php

use run\run;

require_once ("classes/run.php");

$FunPay = new run();

$FunPay->on(event::message, function (\run\messageRepository $messageRepository) {
   print_r($messageRepository);
});

$FunPay->run();