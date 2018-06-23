<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_copy_to_stream($source, $dest, $maxlen = null, $pos = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
