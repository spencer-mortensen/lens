<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_resolve_include_path($filename)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
