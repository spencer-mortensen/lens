<?php

class Person
{
	private $name;

	public function __construct()
	{
		$this->name = null;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;

		return true;
	}
}
