<?php

namespace Example;

use DateTime;

class Clock
{
	public function getTime()
	{
		$time = new DateTime();
		return $time->format('g:i a');
	}
}
