<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_wrapper_register($protocol, $classname, $flags = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
