<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function chroot($directory)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
