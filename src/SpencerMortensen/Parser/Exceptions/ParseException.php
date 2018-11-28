<?php

namespace _Lens\SpencerMortensen\Parser\Exceptions;

use Exception;

class ParseException extends Exception
{
	/** @var int */
	private $position;

	/** @var string|null */
	private $expectation;

	public function __construct($position, $expected)
	{
		$this->position = $position;
		$this->expectation = $expected;
	}

	public function getPosition()
	{
		return $this->position;
	}

	public function getExpectation()
	{
		return $this->expectation;
	}
}
