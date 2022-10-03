<?php

namespace run;

abstract class aliases {
    /**
     * Adds event listener
     *
     * @param \event $event Event naem
     * @param callable $function Calls on event
     * @return void
     */
    public function on(\event $event, callable $function): void {
        $this->events->on($event, $function);
    }
}