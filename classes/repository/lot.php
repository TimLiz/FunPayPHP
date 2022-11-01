<?php

namespace run;

use DOMDocument;
use DOMElement;
use Error;
use Exception;

/**
 * WARNING! Not every property here, every game has different properties lists, make a pull request with ur props, pls :)
 *
 * @property string $active
 * @property int $node_id
 * @property int $offer_id
 * @property int $price
 * @property int $amount
 * @property string $fields [type], [summary][ru], [summary][en], [desc][ru], [desc][en]
 */
class lot
{
    public string $location = "trade";

    /**
     * Returns lot repository
     *
     * @param int $lotId Lot id
     * @return lot Lot repository
     * @throws Exception On error
     */
    static function getLot(int $lotId): lot
    {
        $answer = request::xhr('lots/offerEdit?offer=' . $lotId, null, run::$runner->user->session, true, false);

        $DOM = new DOMDocument();
        $DOM->loadHTML(mb_convert_encoding($answer["html"], 'HTML-ENTITIES', 'utf-8'));
        $inputs = $DOM->getElementsByTagName("input")->getIterator();
        $selects = $DOM->getElementsByTagName("select")->getIterator();
        $textarea = $DOM->getElementsByTagName("textarea")->getIterator();

        $lot = new lot();

        /**
         * @var $current DOMElement
         */
        while ($current = $inputs->current()) {
            if ($current->attributes->getNamedItem("type")->textContent == "checkbox") {
                if ($current->hasAttribute("checked")) {
                    $lot->active = "on";
                }
            } else {
                $name = $current->attributes->getNamedItem('name')->textContent;

                $lot->$name = $current->attributes->getNamedItem('value')->textContent;
            }

            $inputs->next();
        }

        /**
         * @var $current DOMElement
         */
        while ($current = $textarea->current()) {
            $name = $current->attributes->getNamedItem('name')->textContent;
            $lot->$name = $current->textContent;

            $textarea->next();
        }

        /**
         * @var $current DOMElement
         */
        while ($current = $selects->current()) {
            $name = $current->attributes->getNamedItem('name')->textContent;
            $choices = $current->childNodes->getIterator();

            /**
             * @var $current DOMElement
             */
            while ($current = $choices->current()) {
                try {
                    if (@$current->hasAttribute('selected')) {
                        $lot->$name = $current->getAttribute('value');

                        break;
                    }

                    $choices->next();
                } catch (Error $e) {
                    $choices->next();
                    continue;
                }
            }

            $selects->next();
        }

        /**
         * @var $current DOMElement
         */
        while ($current = $textarea->current()) {
            $name = $current->attributes->getNamedItem('name')->textContent;
            $lot->$name = $current->textContent;

            $textarea->next();
        }

        return $lot;
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        request::xhr('lots/offerSave', http_build_query((array)$this), run::$runner->user->session);
    }
}