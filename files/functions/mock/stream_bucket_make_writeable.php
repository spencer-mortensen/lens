<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_bucket_make_writeable($brigade)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
