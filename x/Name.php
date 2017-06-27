<?php

class Name
{
	private $first;

	private $last;

	public function __construct($first, $last)
	{
		$this->first = $first;
		$this->last = $last;
	}

	public function getName()
	{
		return "{$this->first} {$this->last}";
	}
}
