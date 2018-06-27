<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function set_exception_handler($exception_handler)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
