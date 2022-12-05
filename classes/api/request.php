<?php

namespace run;

use Exception;

abstract class request
{
    /**
     * This is basic XHR request to FunPay API
     *
     * @var string $api URL(Without https://funpay.com/)
     * @var string $data Data to send
     * @var string $session User session
     * @var bool $return Should function return response or not
     * @var bool $post Is it POST request? False if GET
     * @var bool $returnRawWithoutParse Should function return raw response without parsing? This also ignore errors
     *
     * @throws Exception
     * @return string|bool|array String if $returnRawWithoutParse is true, bool if $return is false and request was sucess, array if $return is true and sucess
     */
    static public function xhr(string $api, string $data, string $session, bool $return = false, bool $post = true, bool $returnRawWithoutParse = false): bool|array|string
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
        $answerRaw = curl_exec($ch);

        if (str_contains($answerRaw, '<!-- a padding to disable MSIE and Chrome friendly error page -->')) {
            run::$runner->output('Rate limit! Waiting...' . PHP_EOL);
            sleep(3);
            return request::xhr($api, $data, $session, $return, $post);
        }

        if ($returnRawWithoutParse) {
            return $answerRaw;
        }

        $answer = json_decode($answerRaw, true);
        if ($answer == null) {
            throw new Exception("XHR parse error... Request was to " . $api . "          |       Answer got: " . $answerRaw);
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
    static public function basic(string $url, string $session): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://funpay.com/' . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_COOKIE, "golden_key=" . run::$goldenKey . "; PHPSESSID=" . $session);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $exec = curl_exec($ch);
        if (str_contains($exec, "<!-- a padding to disable MSIE and Chrome friendly error page -->")) {
            run::$runner->output("Rate limit! Waiting..." . PHP_EOL);
            sleep(3);
            return request::basic($url, $session);
        }

        return $exec;
    }

    static public function getApplication(string $session):array
    {
        require_once(__DIR__ . "/../html/parser.php");

        $ch = curl_init("https://funpay.com/chat");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIE, "golden_key=" . run::$goldenKey . "; PHPSESSID=" . $session);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $return = curl_exec($ch);

        if (str_contains($return, '<!-- a padding to disable MSIE and Chrome friendly error page -->')) {
            run::$runner->output('Rate limit! Waiting...' . PHP_EOL);
            sleep(3);
            return request::getApplication($session);
        }

        $parser = new parser($return);

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        return json_decode($parser->body->attributes->item(0)->value, true);
    }

    static public function getSession():string
    {
        $ch = curl_init("https://funpay.com/gggggggggggggggg");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIE, run::$goldenKey);
        $return = curl_exec($ch);

        if (str_contains($return, '<!-- a padding to disable MSIE and Chrome friendly error page -->')) {
            run::$runner->output('Rate limit! Waiting...' . PHP_EOL);
            sleep(3);
            return request::getSession();
        }

        preg_match_all(pattern: '/^Set-Cookie:\s*([^;]*)/mi',
            subject: $return, matches: $match_found);

        $cookies = array();
        foreach ($match_found[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        return $cookies["PHPSESSID"];
    }
}