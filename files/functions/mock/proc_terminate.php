<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function proc_terminate($process, $signal = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
