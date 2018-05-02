<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function proc_open($command, $descriptorspec, &$pipes, $cwd = null, $env = null, $other_options = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
