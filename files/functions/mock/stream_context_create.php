<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stream_context_create($options = null, $params = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
