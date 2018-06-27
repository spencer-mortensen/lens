<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function restore_exception_handler()
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
