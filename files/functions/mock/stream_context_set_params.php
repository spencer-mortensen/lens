<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_context_set_params($stream_or_context, $options)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
