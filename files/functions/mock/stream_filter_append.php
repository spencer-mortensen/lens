<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_filter_append($stream, $filtername, $read_write = null, $filterparams = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
