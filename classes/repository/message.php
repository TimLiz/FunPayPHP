<?php

namespace run;

use DOMElement;
use DOMNodeList;
use messageBuilder\messageBuilder;

class messageRepository {
    public readonly userRepository $author;
    public readonly string $message;
    public readonly string $date;
    public readonly string $node;
    public readonly string $nodeID;
    private readonly run $runner;

    public function __construct(DOMNodeList $DOM, run $runner) {
        $msgDiv = $DOM->item(0);

        /**
         * @var DOMElement $msgDiv
         */
        $user = $msgDiv->childNodes[1];

        $this->runner = $runner;
        $this->message = $user->childNodes[5]->textContent;
        $this->date = $user->childNodes[7]->textContent;
        $this->node = $user->attributes[0]->textContent;

        $nodeExplode = explode("=", $this->node);
        $this->nodeID = $nodeExplode[1];

        $chats = request::basic("chat/?node=".$this->nodeID, $this->runner->user->session);
        $parser = new parser($chats);
        $CPU = $parser->getByAttribute("data-type", "c-p-u");

        $this->author = $this->runner->getUser($CPU->item(0)->attributes[2]->textContent, $user->childNodes[3]->textContent);
    }

    public function reply(messageBuilder $msg): void {
        $this->author->sendMessage($msg);
    }
}