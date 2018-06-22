<?php

namespace Example;

use RangeException;

class Math
{
    public function divide($m, $n)
    {
        if ($n === 0) {
            throw new RangeException();
        }

        return $m / $n;
    }
}
