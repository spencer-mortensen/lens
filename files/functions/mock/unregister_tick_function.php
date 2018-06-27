<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function unregister_tick_function($function_name)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
