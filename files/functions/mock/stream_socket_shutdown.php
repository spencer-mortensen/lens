<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_socket_shutdown($stream, $how)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
