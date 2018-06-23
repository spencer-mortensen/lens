<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_context_set_option($stream_or_context, $wrappername, $optionname, $value)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
