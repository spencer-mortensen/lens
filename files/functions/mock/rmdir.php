<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function rmdir($dirname, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
