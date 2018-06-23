<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_filter_remove($stream_filter)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
