<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_context_get_default($options = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
