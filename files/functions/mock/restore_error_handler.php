<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function restore_error_handler()
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
