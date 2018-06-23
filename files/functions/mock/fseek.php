<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function fseek($fp, $offset, $whence = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
