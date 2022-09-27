<?php

namespace run;

use Exception;

class user {
    /**
     * @var string CSRF token(sometimes used for requests)
     */
    public readonly string $csrf;
    /**
     * @var string User language
     */
    public readonly string $lang;
    /**
     * @var array Something not really used
     */
    public readonly array $webpush;
    /**
     * @var int User ID
     */
    public readonly int $ID;
    /**
     * @var string Session
     */
    public readonly string $session;
    /**
     * @var array Settings
     */
    public readonly array $settings;
    private readonly run $runner;
    private array $lots = array();
    private bool $isLotsDefined = false;
    private int $lotsLastRise = 0;

    public function __construct(array $settings, run $runner) {
        echo "Loading user...".PHP_EOL;
        $session = request::getSession();
        $application = request::getApplication($session);

        $this->settings = $settings;
        $this->runner = $runner;
        $this->csrf = $application["csrf-token"];
        $this->session = $session;
        $this->lang = $application["locale"];
        $this->ID = $application["userId"];
        @$this->webpush = [
            "app" => $application["webpush"]["app"]
        ];
    }

    public function checkForOrders():bool {
        $response = request::xhr("runner/", 'objects=%5B%7B%22type%22%3A%22orders_counters%22%2C%22id%22%3A%22'.$this->ID.'%22%2C%22data%22%3Afalse%7D%5D', $this->session, true);
        if ($response["objects"][0]["data"]["seller"] > 0) {
            return true;
        }

        return false;
    }

    public function rise():bool {
        if (time() - $this->lotsLastRise < 300) {
            return false;
        }

        if (!$this->isLotsDefined) {
            $this->defineOffersToRise();
        }

        foreach ($this->lots as $lot) {
            try {
                request::xhr("lots/raise", "game_id=" . $lot["game"] . "&node_id=" . $lot["lot"], run::$runner->user->session);
                run::$runner->events->fireEvent(\event::lotRise);
            } catch (Exception $e) {
                //We don't need anything here
            }
        }

        $this->lotsLastRise = time();
        return true;
    }

    private function defineOffersToRise():void {
        $userPage = request::basic("users/".$this->ID."/", $this->session);
        $parser = new parser($userPage);
        $offers = $parser->getByClassname("offer-list-title-button");
        $offerNow = $offers->length - 1;

        while ($offers->length - 1 >= $offerNow) {
            $lotID = explode("/", $offers->item($offerNow)->childNodes->item(1)->attributes->item(0)->textContent)[4];
            $lotPage = request::basic("lots/".$lotID."/trade", $this->session);
            $parser = new parser($lotPage);
            $data = $parser->getByClassname("js-lot-raise")->item(0);
            $gameID = $data->attributes->item(2)->textContent;

            array_unshift($this->lots, array(
                'game' => $gameID,
                'lot' => $lotID
            ));

            $offerNow++;
        }

        $this->isLotsDefined = true;
    }
}