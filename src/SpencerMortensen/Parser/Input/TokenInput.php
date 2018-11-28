<?php

namespace _Lens\SpencerMortensen\Parser\Input;

class TokenInput implements Input
{
	/** @var array */
	private $tokens;

	/** @var int */
	private $position;

	public function __construct(array $tokens)
	{
		$this->tokens = $tokens;
		$this->position = 0;
	}

	public function read($targetType, &$output = null)
	{
		if (!isset($this->tokens[$this->position])) {
			return false;
		}

		$token = $this->tokens[$this->position];
		$type = key($token);

		if ($type !== $targetType) {
			return false;
		}

		$output = $token;
		++$this->position;
		return true;
	}

	public function getTokens()
	{
		return $this->tokens;
	}

	public function getPosition()
	{
		return $this->position;
	}

	public function setPosition($position)
	{
		$this->position = $position;
	}
}
