<?php

namespace Example;

// Test
$shell = new Shell();
$stdout = $shell->run($command);

// Cause
$command = 'whoami';

// Effect
shell_exec('whoami'); // return 'user';
$stdout = 'myself';

// Effect
$stdout = shell_exec('whoami'); // return 'root';
