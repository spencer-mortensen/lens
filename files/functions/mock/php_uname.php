<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function php_uname($mode = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
