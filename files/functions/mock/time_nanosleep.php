<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function time_nanosleep($seconds, $nanoseconds)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
