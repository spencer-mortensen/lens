<?php

namespace Example\My;

class Name
{
	/** @var string */
	private $first;

	/** @var string */
	private $last;

	public function __construct($first, $last)
	{
		$this->first = $first;
		$this->last = $last;
	}

	public function getFullName()
	{
		return "{$this->first} {$this->last}";
	}
}
