<?php

namespace run;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;

class parser {
    private readonly DOMDocument $html;
    private readonly DOMXPath $finder;
    public readonly DOMNode|null $body;
    public readonly DOMNode|null $head;

    public function __construct(string $html) {
        $this->html = new DOMDocument();
        @$this->html->loadHTML($html);
        $this->finder = new DOMXPath($this->html);
        @$this->body = $this->html->getElementsByTagName("body")->item(0);
        @$this->head = $this->html->getElementsByTagName("head")->item(0);
    }

    /**
     * Gets element by attribute
     *
     * @param string $name Name of element attribute
     * @param string $value Value of element attribute
     * @param bool $returnFirst Should return first found element?
     * @return DOMNodeList|DOMElement
     */
    public function getByAttribute(string $name, string $value, bool $returnFirst = true): DOMNodeList|DOMElement {
        return($this->finder->query("//*[contains(@".$name.", '$value')]"));
    }


    /**
     * Gets element by classname
     *
     * @param string $className Name of element classname
     * @return DOMNodeList
     */
    public function getByClassname(string $className): DOMNodeList {
        return($this->finder->query("//*[contains(@class, '$className')]"));
    }
}