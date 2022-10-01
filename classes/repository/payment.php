<?php

namespace run;

use DOMNode;
use Exception;

class paymentRepository {
    /**
     * @var string $ID ID of payment(ex: #W5DCZ2RM)
     */
    public readonly string $ID;
    /**
     * @var string $nameRaw Raw name of payment(ex: 🔓 | Jailbreak Common safe(5k), Предметы, 2 шт.)
     */
    public readonly string $nameRaw;
    /**
     * @var string $name Name of payment(ex: 🔓 | Jailbreak Common safe(5k)) Tip: This won't work if you're using ", " in you're item name, be carefully!
     */
    public string $name;
    /**
     * @var string $category Category of payment(ex: Предметы) Tip: This won't work if you're using ", " in you're item name, be carefully!
     */
    public string $category;
    /**
     * @var string|null $amount Amount items paid for(ex: 2 шт) Tip: This won't work if you're using ", " in you're item name, be carefully!
     */
    public string|null $amount;
    /**
     * @var string|null $username Username of buyer Tip: This won't work if you're using ", " in you're item name, be carefully!
     */
    public string|null $username;
    /**
     * @var string Raw Data Under Name(ex: Roblox, Услуги)
     */
    public string $underNameRaw;
    /**
     * @var string Game name(ex: Roblox)
     */
    public string $game;
    /**
     * @var string Game category(ex: Услуги)
     */
    public string $gameCategory;
    /**
     * @var string payment page URL
     */
    public string $paymentURL;

    public userRepository $user;
    public function __construct(DOMNode $payment) {
        $this->ID = $payment->childNodes->item(3)->textContent;
        $this->nameRaw = $payment->childNodes->item(5)->childNodes->item(1)->textContent;
        $this->underNameRaw = $payment->childNodes->item(5)->childNodes->item(1)->textContent;
        $this->paymentURL = $payment->attributes->item(0)->textContent;

        $nameRawExploded = explode(", ", $this->nameRaw);
        $this->name = $nameRawExploded[0];
        $this->category = $nameRawExploded[1];
        @$this->amount = $nameRawExploded[2];
        @$this->username = $nameRawExploded[3];

        $underNameExploded = explode(", ", $this->underNameRaw);
        $this->game = $underNameExploded[0];
        $this->gameCategory = $underNameExploded[0];

        $userRaw = $payment->childNodes->item(7)->childNodes->item(1);
        $userRaw = $userRaw->childNodes->item(3)->childNodes->item(1)->childNodes->item(1);
        $link = $userRaw->attributes->item(2)->textContent;

        $ID = explode("/", $link)[4];

        if (!isset(run::$runner->users[$this->ID])) {
            $this->user = new userRepository($ID, run::$runner);
        } else {
            $this->user = run::$runner->users[$this->ID];
        }

        file_put_contents(__DIR__."/../../temp/payments/".$this->ID.".FunPayment", "");
    }

    /**
     * Saves temp data
     *
     * @param string $name Name of value
     * @param string $value Value
     * @param bool $rewrite Should function rewrite value
     * @return bool True on success
     * @throws Exception On error
     */
    public function saveTempData(string $name, string $value, bool $rewrite = false):bool {
        $file = json_decode(file_get_contents(__DIR__."/../../temp/payments/".$this->ID.".FunPayment"), true);
        if ($file == null) {
            $file = array();
        }
        if (isset($file[$name]) && !$rewrite) {
            throw new Exception("Value is already set!");
        }
        $file[$name] = $value;
        file_put_contents(__DIR__."/../../temp/payments/".$this->ID.".FunPayment", json_encode($file));
        return true;
    }

    /**
     * Reads temp data
     *
     * @param string $name Name of value
     * @return string String on success
     * @throws Exception On error
     */
    public function readTempData(string $name):string {
        $file = json_decode(file_get_contents(__DIR__."/../../temp/payments/".$this->ID.".FunPayment"), true);
        if (!isset($file[$name])) {
            throw new Exception("No value set!");
        }
        return $file[$name];
    }

    /**
     * @return bool Returns True on success
     * @throws Exception On error
     */
    public function refund():bool {
        request::xhr("orders/refund", "csrf_token=".run::$runner->user->csrf."&id=".substr($this->ID, 1),run::$runner->user->session);
        return true;
    }

    static public function new():false|paymentRepository {
        $sales = request::basic("orders/trade", run::$runner->user->session);

        $parser = new parser($sales);
        $info = $parser->getByClassname("info");
        $repeat = $info->length - 1;
        while ($repeat >= 0) {
            $current = $info->item($repeat);
            if (!file_exists(__DIR__."/../../temp/payments/".$current->childNodes[3]->textContent.".FunPayment")) {
                return new paymentRepository($current);
            }

            $repeat--;
        }

        return false;
    }
}