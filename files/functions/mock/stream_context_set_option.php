<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_context_set_option($stream_or_context, $wrappername, $optionname, $value)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
