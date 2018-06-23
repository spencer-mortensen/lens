<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

class PDOStatement
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

	public function execute($bound_input_params = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function fetch($how = null, $orientation = null, $offset = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function bindParam($paramno, &$param, $type = null, $maxlen = null, $driverdata = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function bindValue($paramno, $param, $type = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function rowCount()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function fetchColumn($column_number = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function fetchAll($how = null, $class_name = null, $ctor_args = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function fetchObject($class_name = null, $ctor_args = null)
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

	public function setAttribute($attribute, $value)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function getAttribute($attribute)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function columnCount()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function getColumnMeta($column)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function setFetchMode($mode, $params = null)
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function nextRowset()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function closeCursor()
	{
		return eval(Agent::call($this, __FUNCTION__, func_get_args()));
	}

	public function debugDumpParams()
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
}
