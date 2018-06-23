<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function stream_copy_to_stream($source, $dest, $maxlen = null, $pos = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
