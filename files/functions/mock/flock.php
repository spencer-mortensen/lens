<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function flock($fp, $operation, &$wouldblock = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
