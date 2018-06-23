<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function microtime($get_as_float = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
