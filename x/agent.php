<?php

namespace TestPhp;

require 'autoload.php';
require 'Name.php';
require 'Person.php';

use Name;
use Person;

// RECORDING
$person = new Person();

$callable = array($person, 'setName');
$arguments = array(new Name('Spencer', 'Mortensen'));
$result = array(0, true);

Agent::call($callable, $arguments);

$calls = Agent::getCalls();
$script = Agent::getScript();

echo "calls: ", json_encode($calls), "\n";
echo "script: ", json_encode($script), "\n";

/*
Agent::call($callable, $arguments);

$calls = Agent::getCalls();
$script = Agent::getScript();



RECORD:

Agent::call($callable, $arguments, $result);

// $callable = array($object, $method);
// $object = array($id, $class, $state);
// $result = array($type, $value);
$script = array(
	0 => array($callable, $arguments, $result)
);

$objects = array(
	$id => $object
);


REPLAY:

Agent::call($callable, $arguments);

Use the actual object to get the actual $class, $id, and $variableName (if there is one)
If this $id has been translated before:
	use the existing translation
If there is a corresponding $variableName, and both the $variableName and $class match the script:
	use that variable (storing the translation for future reference)
Otherwise:
	deserialize the scripted value, and use this as the translation (storing the translation for future reference)
*/
