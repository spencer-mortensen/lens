<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_is_local($stream)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
