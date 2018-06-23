<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_context_set_params($stream_or_context, $options)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
