<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_select(&$read_streams, &$write_streams, &$except_streams, $tv_sec, $tv_usec = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
