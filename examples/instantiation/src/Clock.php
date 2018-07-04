<?php

namespace Example;

use DateTime;

class Clock
{
	public function getTime()
	{
		$time = new DateTime();
		$time->format('g:i a');
	}
}
