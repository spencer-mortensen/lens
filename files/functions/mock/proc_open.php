<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function proc_open($command, $descriptorspec, &$pipes, $cwd = null, $env = null, $other_options = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
