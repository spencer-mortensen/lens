<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function fclose($fp)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
