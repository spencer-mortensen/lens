<?php

namespace Lens;

use Lens_0_0_57\Lens\Tests\Agent;

function parse_ini_file($filename, $process_sections = null, $scanner_mode = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
