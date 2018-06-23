<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function copy($source_file, $destination_file, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
