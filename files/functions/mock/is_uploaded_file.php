<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function is_uploaded_file($path)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
