<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

class DateTime
{
	public function __construct()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function __call($function, array $arguments)
	{
		return eval(Agent::call($this, $function, $arguments));
	}

	public static function __callStatic($function, array $arguments)
	{
		return eval(Agent::call(__CLASS__, $function, $arguments));
	}

	public function __get($name)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function __set($name, $value)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function __isset($name)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function __unset($name)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function __toString()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function __invoke()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public static function __setState(array $properties)
	{
		return eval(Agent::call(__CLASS__, __FUNCTION__, func_get_args()));
	}

	public function __wakeup()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public static function __set_state()
	{
		return eval(Agent::call(__CLASS__, __FUNCTION__, func_get_args()));
	}

	public static function createFromFormat($format, $time, $object = null)
	{
		return eval(Agent::call(__CLASS__, __FUNCTION__, func_get_args()));
	}

	public static function getLastErrors()
	{
		return eval(Agent::call(__CLASS__, __FUNCTION__, func_get_args()));
	}

	public function format($format)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function modify($modify)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function add($interval)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function sub($interval)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function getTimezone()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function setTimezone($timezone)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function getOffset()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function setTime($hour, $minute, $second = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function setDate($year, $month, $day)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function setISODate($year, $week, $day = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function setTimestamp($unixtimestamp)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function getTimestamp()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function diff($object, $absolute = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}
}
