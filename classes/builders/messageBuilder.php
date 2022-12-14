<?php

namespace messageBuilder;

use Exception;
use run\request;
use run\run;

class messageBuilder {
    public string $content;

    /**
     * Adding image to message
     *
     * @return never
     * @throws Exception Always because this function is not done
     * @todo Create image adding
     */
    public function addImage():never {
        throw new Exception("addImage is not finished yet!");
    }

    /**
     * Use this to send message fast
     *
     * @param string $msg Msg to send
     * @return messageBuilder
     */
    static function fastMessage(string $msg):messageBuilder {
        $message = new messageBuilder();
        $message->content = $msg;
        return $message;
    }
}