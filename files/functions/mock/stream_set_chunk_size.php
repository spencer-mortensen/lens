<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_set_chunk_size($fp, $chunk_size)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
