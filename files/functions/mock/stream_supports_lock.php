<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_supports_lock($stream)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
