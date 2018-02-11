<?php

namespace Example;

use RangeException;

class Math
{
    public function add($m, $n)
    {
        return $m + $n;
    }

    public function divide($m, $n)
    {
		if ($n === 0) {
			throw new RangeException();
		}

        return $m / $n;
    }
}
