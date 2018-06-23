<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_context_create($options = null, $params = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
