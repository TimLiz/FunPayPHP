<?php

namespace run;

class watchingRepository {
    public readonly string $link;
    public readonly string $name;

    public function __construct(string $name, string $link) {
        $this->name = $name;
        $this->link = $link;
    }
}