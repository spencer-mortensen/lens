<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function scandir($dir, $sorting_order = null, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
