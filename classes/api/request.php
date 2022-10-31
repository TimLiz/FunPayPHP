<?php

namespace run;

use Exception;

abstract class request
{
    /**
     * @throws Exception
     */
    static public function xhr(string $api, string $data = null, string $session = null, bool $return = false, bool $post = true): bool|array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://funpay.com/' . $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, $post);
        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json, text/javascript, */*; q=0.01',
            'content-type: application/x-www-form-urlencoded; charset=UTF-8',
            'cookie: golden_key=' . run::$goldenKey . '; PHPSESSID=' . $session,
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36',
            'x-requested-with: XMLHttpRequest'
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $answer = json_decode(curl_exec($ch), true);
        if ($answer == null) {
            throw new Exception("XHR parse error...");
        }

        if (isset($answer["error"]) && $answer["error"]) {
            $error = $answer["msg"] ?? $answer["error"];
            throw new Exception("FunPay error: " . $error);
        }
        if ($return) {
            return $answer;
        }

        return true;
    }

    /**
     * Basic http GET request
     *
     * @param string $url URL
     * @param string $session Session
     * @return string Data
     */
    static public function basic(string $url, string $session): string {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://funpay.com/'.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_COOKIE, "golden_key=".run::$goldenKey."; PHPSESSID=".$session);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        return(curl_exec($ch));
    }

    static public function getApplication(string $session):array {
        require_once (__DIR__."/../html/parser.php");

        $ch = curl_init("https://funpay.com/chat");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIE, "golden_key=".run::$goldenKey."; PHPSESSID=".$session);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $return = curl_exec($ch);
        $parser = new parser($return);

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        return json_decode($parser->body->attributes->item(0)->value, true);
    }

    static public function getSession():string {
        $ch = curl_init("https://funpay.com/gggggggggggggggg");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIE, run::$goldenKey);
        $return = curl_exec($ch);

        preg_match_all(pattern: '/^Set-Cookie:\s*([^;]*)/mi',
            subject: $return, matches: $match_found);

        $cookies = array();
        foreach($match_found[1] as $item) {
            parse_str($item,  $cookie);
            $cookies = array_merge($cookies,  $cookie);
        }

        return $cookies["PHPSESSID"];
    }
}