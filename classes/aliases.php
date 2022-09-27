<?php

namespace run;

abstract class aliases {
    public function on(int $event, callable $function): void {
        $this->events->on($event, $function);
    }
}