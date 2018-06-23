<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function file($filename, $flags = null, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
