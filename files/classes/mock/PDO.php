<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

class PDO
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

	public function prepare($statement, $options = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function beginTransaction()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function commit()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function rollBack()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function inTransaction()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function setAttribute($attribute, $value)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function exec($query)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function query()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function lastInsertId($seqname = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function errorCode()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function errorInfo()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function getAttribute($attribute)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function quote($string, $paramtype = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	final public function __wakeup()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	final public function __sleep()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public static function getAvailableDrivers()
	{
		return eval(Agent::call(__CLASS__, __FUNCTION__, func_get_args()));
	}
}
