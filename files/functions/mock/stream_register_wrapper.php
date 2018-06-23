<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_register_wrapper($protocol, $classname, $flags = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
