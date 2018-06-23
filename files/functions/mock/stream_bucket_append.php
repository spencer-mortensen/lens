<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_bucket_append($brigade, $bucket)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
