<?php

namespace run;

use DOMElement;
use Error;
use event;
use Exception;
use JetBrains\PhpStorm\NoReturn;

/**
 * This class is YOU
 * Here you can get things like you're ID, name, balance, etc.
 */
class user
{
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
     * @var string Avatar URL
     */
    public readonly string $avatarURL;
    /**
     * @var string Session
     */
    public readonly string $session;
    /**
     * @var array Settings
     */
    public readonly array $settings;
    /**
     * @var array Lots
     */
    private array $lots = array();
    /**
     * @var bool System bool, marks are lots defined. Used for autoRise
     */
    private bool $isLotsDefined = false;
    /**
     * @var array|mixed This is system property, used for storage
     */
    private array $storage = array();
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
     * @var string Username
     */
    public string $username;
    /**
     * Updates every 10 minutes
     *
     * @var string|null Money in raw view(ex: Всего 131 отзыв)
     * P.S null if no lots defined
     */
    public string|null $ratingRaw;
    /**
     * Updates every 10 minutes
     *
     * @var int|null Formatted rating(ex: 128)
     * P.S null if not lots defined
     */
    public int|null $rating;
    /**
     * Name to lot id
     *
     * @var array
     */
    private array $nameToLotId;

    public function __construct(array $settings)
    {
        run::$runner->output("Loading user..." . PHP_EOL);

        $session = request::getSession();
        $application = request::getApplication($session);

        $this->settings = $settings;
        $this->csrf = $application["csrf-token"];
        $this->session = $session;
        $this->lang = $application["locale"];
        $this->ID = $application["userId"];
        $this->url = "https://funpay.com/users/" . $this->ID . "/";
        @$this->webpush = [
            "app" => $application["webpush"]["app"]
        ];

        $this->update();

        //Updating
        run::$runner->timers->addRepeated(600, function () {
            $this->update();
        });

        if (!file_exists(__DIR__ . "/../temp/users")) {
            mkdir(__DIR__ . "/../temp/users");
        }

        if (!file_exists(__DIR__ . "/../temp/users/" . $this->ID . ".FunUser")) {
            file_put_contents(__DIR__ . "/../temp/users/" . $this->ID . ".FunUser", "");
        } else {
            $storage = json_decode(file_get_contents(__DIR__ . "/../temp/users/" . $this->ID . ".FunUser"), true);
            if ($storage == null) {
                $storage = array();
            }
            $this->storage = $storage;
        }

        $this->loadLots();
    }

    /**
     * Checks for orders
     * Checks only is mark > 0, you need to check more data also
     *
     * @return bool True on order and false if no orders / error
     */
    public function checkForOrders():bool {
        try {
            $response = request::xhr("runner/", 'objects=%5B%7B%22type%22%3A%22orders_counters%22%2C%22id%22%3A%22' . $this->ID . '%22%2C%22data%22%3Afalse%7D%5D', $this->session, true);

            if ($response["objects"][0]["data"]["seller"] > 0) {
                return true;
            }

        } catch (Exception) {
            return false;
        }

        return false;
    }

    /**
     * Checks golden key for valid.
     *
     * @return bool True if key valid and false if not
     * @throws Exception On request error
     */
    static function validateKey():bool {
        try {
            $answer = request::xhr("lots/raise", http_build_query([
                'game_id' => 141,
                'node_id' => 1154
            ]), "NothingHere", true, true, true);

            if (str_contains($answer, "Необходимо авторизоваться.")) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            throw new Error("Failed to validate key. Request problem. ".$e->getMessage());
        }
    }


    /**
     * Writes data to user storage(safes on bot restart)
     *
     * @param string $key Key
     * @param string|array $data Data to write
     * @param bool $allowOverwrite Allow to overwrite
     * @return bool True on success false on error
     */
    public function storageWrite(string $key, string|array $data, bool $allowOverwrite = false):bool {
        if (isset($this->storage[$key]) && !$allowOverwrite) return false;
        $this->storage[$key] = $data;

        file_put_contents(__DIR__."/../temp/users/".$this->ID.".FunUser", json_encode($this->storage));

        return true;
    }

    /**
     * Removes data from storage
     *
     * @param string $key Key
     * @return bool True on success false on error
     */
    public function storageRemove(string $key):bool {
        unset($this->storage[$key]);

        file_put_contents(__DIR__."/../temp/users/".$this->ID.".FunUser", json_encode($this->storage));

        return true;
    }

    /**
     * @return bool True on success, False on error/timer still waiting
     */
    public function update(): bool {
        $settingsPage = request::basic(substr($this->url, 19), $this->session);
        try {
            $parser = new parser($settingsPage);
            //Getting money with ₽
            @$money = $parser->getByClassname("badge-balance")->item(0)->textContent;
            if ($money == null) $money = "0₽";
            $this->moneyRaw = $money;

            //Getting money without ₽
            $this->money = trim($this->moneyRaw, "₽");

            //Getting raw rating
            @$this->ratingRaw = $parser->getByClassname("rating-full-count")->item(0)->textContent;

            //Getting formatted rating
            @$this->rating = explode(" ", $this->ratingRaw)[1];

            if (!isset($this->username) || !isset($this->avatarURL)) {
                $profile = $parser->getByClassname('profile');
                $this->username = $profile->item(0)->childNodes->item(3)->childNodes->item(1)->childNodes->item(0)->textContent;

                $style = $parser->getByClassname("avatar-photo")->item(0)->attributes->getNamedItem("style")->textContent;
                $this->avatarURL = explode("(", explode(")", $style)[0])[1];
            }

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function rise():bool {
        if (!$this->isLotsDefined) {
            $this->defineOffersToRise();
        }

        foreach ($this->lots as $key => $lot) {
            try {
                if (count($lot) == 1) {
                    request::xhr("lots/raise", "game_id=" . $key . "&node_id=" . $lot[0], run::$runner->user->session);
                } else {
                    $array = array(
                        'game_id' => $key,
                        'node_id' => $lot[0],
                    );

                    $string = http_build_query($array);

                    foreach ($lot as $item) {
                        $string .= "&node_ids%5B%5D=" . $item;
                    }

                    request::xhr("lots/raise", $string, run::$runner->user->session);
                }
                run::$runner->events->fireEvent(event::lotRise);
            } catch (Exception) {
                //We don't need anything here
            }
        }
        return true;
    }

    /**
     * @TODO Make lot rise compatible with loadLots()
     */
    #[NoReturn] private function defineOffersToRise(): void
    {
        $userPage = request::basic("users/" . $this->ID . "/", $this->session);
        $parser = new parser($userPage);
        $offers = $parser->getByClassname("offer-list-title-button");
        $offerNow = 0;

        while ($offers->length - 1 >= $offerNow) {
            $lotID = explode("/", $offers->item($offerNow)->childNodes->item(1)->attributes->item(0)->textContent)[4];
            $lotPage = request::basic("lots/" . $lotID . "/trade", $this->session);
            $parser = new parser($lotPage);
            $data = $parser->getByClassname("js-lot-raise");
            if ($data->length == 0) {
                if ($offers->length - 1 >= $offerNow) {
                    $offerNow++;
                    continue;
                }
            }
            $data = $data->item(0);
            $gameID = $data->attributes->item(2)->textContent;

            array_unshift($this->lots, array(
                'game' => $gameID,
                'lot' => $lotID
            ));

            $offerNow++;
        }

        $finished = array();
        foreach ($this->lots as $lot) {
            if (!isset($finished[$lot["game"]])) {
                $finished[$lot["game"]] = array();
            }

            array_unshift($finished[$lot["game"]], $lot["lot"]);
        }

        $this->lots = $finished;

        $this->isLotsDefined = true;
    }

    public function getLotByName(string $name): lot|null
    {
        if (isset($this->nameToLotId[$name])) {
            return lot::getLot($this->nameToLotId[$name]);
        }

        return null;
    }

    private function loadLots(): void
    {
        $profile = request::basic(sprintf("users/%d/", $this->ID), $this->session);
        $parser = new parser($profile);
        $lots = $parser->getByClassname("tc-item");
        $iterator = $lots->getIterator();

        /**
         * @var DOMElement $current
         */
        while ($current = $iterator->current()) {
            $link = $current->getAttribute("href");
            $id = explode("=", explode("?", explode("/", $link)[4])[1])[1] . PHP_EOL;
            $name = explode(", ", $current->childNodes->item(1)->childNodes->item(1)->textContent)[0];

            $this->nameToLotId[$name] = $id;

            $iterator->next();
        }
    }
}