<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function system($command, &$return_value = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
