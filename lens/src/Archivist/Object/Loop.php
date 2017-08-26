<?php

namespace Example\Archivist\Object;

class Loop
{
	/** @var Loop */
	private $loop;

	public function __construct()
	{
		$this->loop = $this;
	}
}
