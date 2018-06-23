<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function get_current_user()
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
