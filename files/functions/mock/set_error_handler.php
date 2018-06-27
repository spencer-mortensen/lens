<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function set_error_handler($error_handler, $error_types = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
