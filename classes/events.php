<?php
namespace run;

use event;

require_once(__DIR__."/event.php");
require_once (__DIR__."/event.php");

class events {
    public readonly object $parent;

    private array $onMessage = array();
    private array $onPayment = array();
    private array $onYoureMessage = array();
    private array $onLotRise = array();
    private array $onLoop = array();

    public function __construct($parent) {
        echo "Loading events...".PHP_EOL;
        $this->parent = $parent;
    }

    /**
     * @param int $event Events type
     * @param callable $function On event
     * @return void
     */
    public function on(int $event, callable $function): void {
        switch ($event) {
            case event::message:
                array_unshift($this->onMessage, $function);
                break;
            case event::payment:
                array_unshift($this->onPayment, $function);
                break;
            case event::lotRise:
                array_unshift($this->onLotRise, $function);
                break;
            case event::loop:
                array_unshift($this->onLoop, $function);
                break;
            default:
        }
    }

    public function fireEvent(int $event, ...$args): void {
        switch ($event) {
            case event::message:
                foreach ($this->onMessage as $item) {
                    call_user_func($item, ...$args);
                }
                break;
            case event::payment:
                foreach ($this->onPayment as $item) {
                    call_user_func($item, ...$args);
                }
                break;
            case event::lotRise:
                foreach ($this->onLotRise as $item) {
                    call_user_func($item, ...$args);
                }
                break;
            case event::loop:
                foreach ($this->onLoop as $item) {
                    call_user_func($item, ...$args);
                }
                break;
            default:
        }
    }
}