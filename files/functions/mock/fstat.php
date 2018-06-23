<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function fstat($fp)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
