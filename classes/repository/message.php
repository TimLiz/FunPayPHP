<?php

namespace run;

use DOMElement;
use DOMNodeList;
use Exception;
use messageBuilder\messageBuilder;

class messageRepository {
    /**
     * @var userRepository Message author
     */
    public readonly userRepository $author;
    /**
     * @var string Message content
     * @deprecated
     * WARNING! Do not use it in new projects, it will be removed soon
     */
    public readonly string $message;
    /**
     * @var string Message content
     */
    public readonly string $content;
    /**
     * @var string Message creation date
     */
    public readonly string $date;
    /**
     * @var string Message node
     */
    public readonly string $node;
    /**
     * @var string Message nodeID
     */
    public readonly string $nodeID;
    /**
     * @var bool Is user message or system message
     */
    public bool $isMessage;

    public function __construct(DOMNodeList $DOM) {
        $msgDiv = $DOM->item(0);
        /*** @var DOMElement $msgDiv */
        $user = $msgDiv->childNodes[1];

        $this->message = $user->childNodes[5]->textContent;
        $this->content = $user->childNodes[5]->textContent;
        $this->date = $user->childNodes[7]->textContent;
        $this->node = $user->attributes[0]->textContent;

        $nodeExplode = explode("=", $this->node);
        $this->nodeID = $nodeExplode[1];

        $chats = request::basic("chat/?node=" . $this->nodeID, run::$runner->user->session);

        $parser = new parser($chats);

        $messages = $parser->getByClassname("message-head");

        $last = $messages->item($messages->length - 1);

        if ($last->childNodes->item(1)->attributes->item(0)->textContent == "chat-message") {
            $this->isMessage = true;
            $link = $last->childNodes->item(1)->childNodes->item(1)->childNodes->item(0)->attributes->item(0)->textContent;
            $this->author = run::$runner->getUser(explode("/", $link)[4]);
        } elseif ($last->childNodes->item(3)->attributes->item(0)->textContent == "chat-message") {
            $this->isMessage = true;
            $link = $last->childNodes->item(3)->childNodes->item(1)->childNodes->item(0)->attributes->item(0)->textContent;
            $this->author = run::$runner->getUser(explode("/", $link)[4]);
        } else {
            $this->isMessage = false;
        }
    }

    /**
     * Replies to the message
     *
     * @throws Exception
     */
    public function reply(messageBuilder $msg): void {
        $this->author->sendMessage($msg);
    }
}