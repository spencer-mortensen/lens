<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function fgetss($fp, $length = null, $allowable_tags = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
