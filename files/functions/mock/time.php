<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function time()
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
