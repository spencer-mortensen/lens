<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function rename($old_name, $new_name, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
