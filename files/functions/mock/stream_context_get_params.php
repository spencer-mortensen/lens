<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_context_get_params($stream_or_context)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
