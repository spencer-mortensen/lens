<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function is_writable($filename)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
