<?php

namespace run;

use Exception;
use messageBuilder\messageBuilder;

class userRepository {
    public string $name;
    public int $ID;
    public bool $answered = true;
    public string $logo;
    private run $runner;

    public function __construct(string $name, int $ID, run $runner) {
        $this->name = $name;
        $this->ID = $ID;
        $this->runner = $runner;

        $profile = request::basic("users/".$this->ID."/", run::$runner->user->session);
        $parser = new parser($profile);

        $logo = $parser->getByClassname("avatar-photo")->item(0)->attributes->item(1)->textContent;
        $logo = explode(": ", $logo)[1];
        $logo = substr($logo, 4, -2);

        if ($logo == "/img/layout/avatar.png") {
            $logo = "https://funpay.com/img/layout/avatar.png";
        }
        $this->logo = $logo;
    }

    public function getViewing(): watchingRepository {
        $respond = request::xhr("runner/",'objects=%5B%7B%22type%22%3A%22c-p-u%22%2C%22id%22%3A%22'.$this->ID.'%22%2C%22data%22%3Afalse%7D%5D',$this->runner->user->session, true);
        $html = $respond["objects"][0]["data"]["html"]["desktop"];
        $DOM = new \DOMDocument();
        @$DOM->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'utf-8'));
        $watchingTile = $DOM->getElementsByTagName("a")->item(0);

        return new watchingRepository($watchingTile->textContent, $watchingTile->attributes[0]->value);
    }

    /**
     * Sends message
     *
     * @param messageBuilder $msg Msg to send
     * @return bool True if sucess
     * @throws Exception
     */
    public function sendMessage(messageBuilder $msg):bool {
        run::$runner->message->send($msg, $this->ID);

        return true;
    }
}