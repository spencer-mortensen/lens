<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_filter_prepend($stream, $filtername, $read_write = null, $filterparams = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
