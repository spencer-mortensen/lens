<?php

namespace _Lens\SpencerMortensen\Parser\Input;

class StringInput implements Input
{
	/** @var string */
	private $input;

	/** @var integer */
	private $position;

	public function __construct($input)
	{
		$this->input = $input;
		$this->position = 0;
	}

	public function getPosition()
	{
		return $this->position;
	}

	public function setPosition($position)
	{
		$this->position = $position;
	}

	public function read($expression, &$output = null)
	{
		if (!$this->match($expression, $match)) {
			return false;
		}

		$this->position += strlen($match[0]);

		if (count($match) === 1) {
			$output = $match[0];
		} elseif (count($match) === 2) {
			$output = $match[1];
		} else {
			$output = $match;
			array_shift($output);
		}

		return true;
	}

	private function match($expression, &$match = null)
	{
		$delimiter = "\x03";
		$flags = 'AXDs';

		$pattern = $delimiter . $expression . $delimiter . $flags;

		return preg_match($pattern, $this->input, $match, null, $this->position) === 1;
	}
}
