<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function register_tick_function($function_name, $parameters = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
