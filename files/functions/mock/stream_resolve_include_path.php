<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stream_resolve_include_path($filename)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
