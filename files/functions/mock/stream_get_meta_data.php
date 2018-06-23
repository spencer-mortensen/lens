<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_get_meta_data($fp)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
