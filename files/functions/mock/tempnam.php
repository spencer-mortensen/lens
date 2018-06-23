<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function tempnam($dir, $prefix)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
