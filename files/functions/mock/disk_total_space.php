<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function disk_total_space($path)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
