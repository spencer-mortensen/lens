<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_get_contents($source, $maxlen = null, $offset = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
