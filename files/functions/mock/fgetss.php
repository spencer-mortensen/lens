<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function fgetss($fp, $length = null, $allowable_tags = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
