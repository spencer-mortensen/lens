<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function file_get_contents($filename, $flags = null, $context = null, $offset = null, $maxlen = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
