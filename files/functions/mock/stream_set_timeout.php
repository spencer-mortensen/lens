<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_set_timeout($stream, $seconds, $microseconds = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
