<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stream_set_chunk_size($fp, $chunk_size)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
