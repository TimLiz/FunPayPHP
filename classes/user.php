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
    /**
     * @var array Lots
     */
    private array $lots = array();
    private bool $isLotsDefined = false;
    private int $lotsLastRise = 0;
    private int $lastUpdate = 0;
    /**
     * Updates every 10 minutes
     *
     * @var string Money in raw view(ex: 684 ₽)
     */
    public string $moneyRaw;
    /**
     * Updates every 10 minutes
     *
     * @var int Formatted money(ex: 684)
     */
    public int $money;
    /**
     * @var string URL of user profile(ex: https://funpay.com/users/1110493/)
     */
    public string $url;
    /**
     * Updates every 10 minutes
     *
     * @var string Money in raw view(ex: Всего 131 отзыв)
     */
    public string $ratingRaw;
    /**
     * Updates every 10 minutes
     *
     * @var int Formatted rating(ex: 128)
     */
    public int $rating;

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
        $this->url = "https://funpay.com/users/".$this->ID."/";
        @$this->webpush = [
            "app" => $application["webpush"]["app"]
        ];

        $this->update();
    }

    /**
     * Checks for orders
     *
     * @return bool True on order and false if no orders / error
     */
    public function checkForOrders():bool {
        try {
            $response = request::xhr("runner/", 'objects=%5B%7B%22type%22%3A%22orders_counters%22%2C%22id%22%3A%22' . $this->ID . '%22%2C%22data%22%3Afalse%7D%5D', $this->session, true);

            if ($response["objects"][0]["data"]["seller"] > 0) {
                return true;
            }

        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * @return bool True on success, False on error/timer still waiting
     */
    public function update(): bool {
        if (time() - $this->lastUpdate > 600) {
            $settingsPage = request::basic(substr($this->url, 19), $this->session);
            try {
                $parser = new parser($settingsPage);
                //Getting money with ₽
                $this->moneyRaw = $parser->getByClassname("badge-balance")->item(0)->textContent;

                //Getting money without ₽
                $this->money = trim($this->moneyRaw, "₽");

                //Getting raw rating
                $this->ratingRaw = $parser->getByClassname("rating-full-count")->item(0)->textContent;

                //Getting formatted rating
                $this->rating = explode(" ", $this->ratingRaw)[1];

                return true;
            } catch (Exception $e) {
                return false;
            }
        } else {
            return false;
        }
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