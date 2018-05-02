<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stream_context_set_option($stream_or_context, $wrappername, $optionname, $value)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
