<?php

namespace run;

class timer {
    private array $timers = array();
    private array $repeats = array();

    /**
     * Adds delayed function
     *
     * @param int $seconds Execute after
     * @param callable $function Do
     * @param bool $shouldWait Should it wait for API initialise? Default true
     * @return void
     */
    function addTimer(int $seconds, callable $function, bool $shouldWait = true):void {
        array_unshift($this->timers, array(
            'until' => time() + $seconds,
            'do' => $function,
            'shouldWait' => $shouldWait,
            'initialised' => time(),
            'delay' => $seconds
        ));
    }

    /**
     * Adds repeated function
     *
     * @param int $seconds Delay between every function call
     * @param callable $function D
     * @param bool $shouldWait Should it wait for API initialise? Default true
     * @return string Repeated id
     */
    function addRepeated(int $seconds, callable $function, bool $shouldWait = true):string {
        $id = uniqid("Repeated_");

        $this->repeats[$id] = array(
            'every' => $seconds,
            'next' => time()+$seconds,
            'do' => $function,
            'shouldWait' => $shouldWait
        );

        return $id;
    }

    /**
     * Remove repeated function
     *
     * @param string $id ID of repeated function
     * @return bool True on success, false on error
     */
    function removeRepeated(string $id):bool {
        if (!isset($this->repeats[$id])) {
            return false;
        }
        unset($this->repeats[$id]);
        return true;
    }

    function loop():void {
        foreach ($this->timers as $key => $timer) {
            if ($timer["until"] <= time()) {
                if (!run::$runner->isReady and $timer["shouldWait"]) {
                    $this->timers[$key]["until"] = time() + $timer["delay"];
                    continue;
                }

                call_user_func($timer["do"]);
                unset($this->timers[$key]);
            }
        }

        foreach ($this->repeats as $key => $repeat) {
            if ($repeat["next"] <= time()) {
                if (!run::$runner->isReady and $repeat["shouldWait"]) {
                    $this->repeats[$key]["next"] = time() + $repeat["every"];
                    continue;
                }

                $this->repeats[$key]["next"] = $repeat["next"]+$repeat["every"];
                call_user_func($repeat["do"]);
            }
        }
    }
}