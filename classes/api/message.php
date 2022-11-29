<?php
namespace run;

use Exception;
use messageBuilder\messageBuilder;

class message {
    private run $parent;

    public function __construct(run $parent) {
        run::$runner->output("Loading messages...".PHP_EOL);
        $this->parent = $parent;
    }

    /**
     * Sends messages
     *
     * @param messageBuilder $message Message builder object
     * @param int $id Destination user ID
     * @throws Exception On error
     */
    public function send(messageBuilder $message, int $id):bool|null {
        if (run::$runner->user->ID < $id) {
            $node = "users-".run::$runner->user->ID."-".$id;
        } else {
            $node = "users-".$id."-".run::$runner->user->ID;
        }

        //We need to require chat_bookmarks here, because FunPay needs it to send messages to account with reviews, without reviews - no,
        //IDK why, do not ask me
        $answer = request::xhr("runner/", 'objects=[{"type":"chat_bookmarks","id":"'.run::$runner->user->ID.'","data":false}]&request={"action":"chat_message","data":{"node":"'.$node.'","content":"'.$message->content.'"}}&csrf_token='.run::$runner->user->csrf, run::$runner->user->session, true);

        if (isset($answer["msg"]) && $answer["error"]) {
            throw new Exception("Message send error: ".$answer["msg"]);
        }

        return true;
    }

    /**
     * Cheks for messages
     *
     * @return messageRepository|bool False on error/no messages
     */
    public function checkForMsg():messageRepository|bool {
        try {
            $response = request::xhr("runner/", 'objects=%5B%7B%22type%22%3A%22chat_bookmarks%22%2C%22id%22%3A%22'.$this->parent->user->ID.'%22%2C%22data%22%3Afalse%7D%5D', $this->parent->user->session, true);

            $html = $response["objects"][0]["data"]["html"];
            $parser = new parser(mb_convert_encoding($html, 'HTML-ENTITIES', 'utf-8'));
            $html = $parser->getByClassname("contact-list");

            if ($response["objects"][0]["data"]["counter"] > 0) {
                $msg = new messageRepository($html);
                if ($msg->isMessage) {
                    return $msg;
                } else {
                    return false;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}