<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function file_get_contents($filename, $flags = null, $context = null, $offset = null, $maxlen = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
