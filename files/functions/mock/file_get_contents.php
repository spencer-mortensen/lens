<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function file_get_contents($filename, $flags = null, $context = null, $offset = null, $maxlen = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
