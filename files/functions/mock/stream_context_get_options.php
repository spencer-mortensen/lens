<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stream_context_get_options($stream_or_context)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
