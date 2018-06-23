<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function file_put_contents($filename, $data, $flags = null, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
