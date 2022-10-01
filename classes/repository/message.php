<?php

namespace run;

use DOMElement;
use DOMNodeList;
use Exception;
use messageBuilder\messageBuilder;

class messageRepository {
    public readonly userRepository $author;
    public readonly string $message;
    public readonly string $date;
    public readonly string $node;
    public readonly string $nodeID;
    public bool $isMessage;
    private readonly run $runner;

    public function __construct(DOMNodeList $DOM, run $runner) {
        $msgDiv = $DOM->item(0);
        /*** @var DOMElement $msgDiv */
        $user = $msgDiv->childNodes[1];
        $this->runner = $runner;
        $this->message = $user->childNodes[5]->textContent;
        $this->date = $user->childNodes[7]->textContent;
        $this->node = $user->attributes[0]->textContent;
        $nodeExplode = explode("=", $this->node);
        $this->nodeID = $nodeExplode[1];
        $chats = request::basic("chat/?node=" . $this->nodeID, $this->runner->user->session);
        $parser = new parser($chats);
        $messages = $parser->getByClassname("message-head");
        $last = $messages->item($messages->length - 1);
        if ($last->childNodes->item(1)->attributes->item(0)->textContent == "chat-message") {
            $this->isMessage = true;
            $link = $last->childNodes->item(1)->childNodes->item(1)->childNodes->item(0)->attributes->item(0)->textContent;
            $this->author = $this->runner->getUser(explode("/", $link)[4]);
        } else {
            $this->isMessage = false;
        }
    }

    /**
     * @throws Exception
     */
    public function reply(messageBuilder $msg): void {
        $this->author->sendMessage($msg);
    }
}