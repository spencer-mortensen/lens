<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function proc_close($process)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
