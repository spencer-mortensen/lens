<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_wrapper_unregister($protocol)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
