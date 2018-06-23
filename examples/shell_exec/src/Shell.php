<?php

namespace Example;

class Shell
{
	public function run($command)
	{
		return \shell_exec($command);
	}
}
