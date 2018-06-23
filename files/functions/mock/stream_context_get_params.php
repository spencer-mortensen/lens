<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_context_get_params($stream_or_context)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
