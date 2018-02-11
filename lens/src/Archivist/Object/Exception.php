<?php

namespace Example\Archivist\Object;

class Exception extends \Exception
{
	/** @var mixed */
	private $data;

	public function __construct($message, $code, $data)
	{
		parent::__construct($message, $code);

		$this->data = $data;
	}
}
