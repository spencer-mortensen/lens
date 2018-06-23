<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function opendir($path, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
