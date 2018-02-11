<?php

namespace Example;

class Greeter
{
    public function greet(Person $person)
    {
        $name = $person->getName();
        $day = date('l', time());

        return "Hello {$name}, it's {$day} today!";
    }
}
