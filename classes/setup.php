<?php

namespace run;

class setup {
    static public function run():void {
        //Making empty screen
        echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';

        //Title
        echo "ooooooooooo                         oooooooooo                             oooooooooo  ooooo ooooo oooooooooo  \n 888    88  oooo  oooo  oo oooooo    888    888   ooooooo   oooo   oooo     888    888  888   888   888    888 \n 888ooo8     888   888   888   888   888oooo88    ooooo888   888   888      888oooo88   888ooo888   888oooo88  \n 888         888   888   888   888   888        888    888    888 888       888         888   888   888        \no888o         888o88 8o o888o o888o o888o        88ooo88 8o     8888       o888o       o888o o888o o888o       \n                                                             o8o888                                            \n\n";

        echo "Конфигурации для FunPay PHP!".PHP_EOL;
        $goldenKey = null;
        while ($goldenKey == null | $goldenKey == false) {
            $goldenKey = readline("Введите goldenKey: ");
        }

        file_put_contents(__DIR__."/../config.json", json_encode(array(
            'key' => $goldenKey
        )));

        readline("Конфигурация сохранена! Нажмите ВВОД для запуска бота, или CTRL+C для выхода.");
    }
}