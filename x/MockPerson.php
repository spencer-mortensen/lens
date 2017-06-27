<?php

namespace TestPhp\Mock;

class Person extends \Person
{
	public function __construct()
	{
		parent::__construct();

		$callable = array($this, __FUNCTION__);
		$arguments = func_get_args();

		return \TestPhp\Agent::call($callable, $arguments);
	}

	public function getName()
	{
		$callable = array($this, __FUNCTION__);
		$arguments = func_get_args();

		return \TestPhp\Agent::call($callable, $arguments);
	}

	public function setName($name)
	{
		$callable = array($this, __FUNCTION__);
		$arguments = func_get_args();

		return \TestPhp\Agent::call($callable, $arguments);
	}
}
