<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function exec($command, &$output = null, &$return_value = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
