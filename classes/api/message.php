<?php
namespace run;

use Exception;
use messageBuilder\messageBuilder;

class message {
    private run $parent;

    public function __construct(run $parent) {
        echo "Loading messages...".PHP_EOL;
        $this->parent = $parent;
    }

    /**
     * Message send function
     *
     * @param messageBuilder $message Message builder object
     * @param int $id Destination user ID
     * @throws Exception On error
     */
    public function send(messageBuilder $message, int $id):bool|null {
        $answer = request::xhr("runner/", 'request=%7B%22action%22%3A%22chat_message%22%2C%22data%22%3A%7B%22node%22%3A%22users-'.$this->parent->user->ID.'-'.$id.'%22%2C%22last_message%22%3A1%2C%22content%22%3A%22'.$message->content.'%22%7D%7D&csrf_token='.run::$runner->user->csrf, run::$runner->user->session, true);

        if (isset($answer["msg"]) && $answer["error"]) {
            throw new Exception("Message send error: ".$answer["msg"]);
        }

        return true;
    }

    /**
     * @return messageRepository|bool False on error/no messages
     */
    public function checkForMsg():messageRepository|bool {
        try {
            $response = request::xhr("runner/", 'objects=%5B%7B%22type%22%3A%22chat_bookmarks%22%2C%22id%22%3A%22'.$this->parent->user->ID.'%22%2C%22data%22%3Afalse%7D%5D', $this->parent->user->session, true);

            $html = $response["objects"][0]["data"]["html"];
            $parser = new parser(mb_convert_encoding($html, 'HTML-ENTITIES', 'utf-8'));
            $html = $parser->getByClassname("contact-list");

            if ($response["objects"][0]["data"]["counter"] > 0) {
                return new messageRepository($html, $this->parent);
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}