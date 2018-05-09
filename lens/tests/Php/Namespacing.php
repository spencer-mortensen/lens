<?php

namespace Lens_0_0_56\Lens\Php;

// Test
$namespacing = new Namespacing($isFunction, $namespace, $uses);
$relativeFunction = $namespacing->getRelativeFunction($absoluteFunction);

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array();
$absoluteFunction = 'f';

// Output
$relativeFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array();
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array('My' => 'Org');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array('My' => 'Organization');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array('My' => 'Organization\\Project');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\f';

// Input
$isFunction = function ($function) { return $function === 'f'; };
$namespace = 'Example';
$uses = array();
$absoluteFunction = 'f';

// Output
$relativeFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example';
$uses = array();
$absoluteFunction = 'f';

// Output
$relativeFunction = '\\f';

// Input
$isFunction = function ($function) { return $function === 'My\\f'; };
$namespace = 'Example';
$uses = array();
$absoluteFunction = 'My\\f';

// Output
$relativeFunction = '\\My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example';
$uses = array();
$absoluteFunction = 'My\\f';

// Output
$relativeFunction = '\\My\\f';


// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array();
$absoluteFunction = 'f';

// Output
$relativeFunction = '\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array();
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = '\\Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array('My' => 'Org');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = '\\Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array('My' => 'Organization');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array('My' => 'Organization\\Project');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = array();
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = array('My' => 'Org');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = array('My' => 'Organization');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = array('My' => 'Organization\\Project');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = array();
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = array('My' => 'Org');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = array('My' => 'Organization');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = array('My' => 'Organization\\Project');
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\f';



// Test
$namespacing = new Namespacing($isFunction, $namespace, $uses);
$absoluteFunction = $namespacing->getAbsoluteFunction($relativeFunction);

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array();
$relativeFunction = 'f';

// Output
$absoluteFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array();
$relativeFunction = 'Organization\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array('My' => 'Org');
$relativeFunction = 'Organization\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array('My' => 'Organization');
$relativeFunction = 'My\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = array('My' => 'Organization\\Project');
$relativeFunction = 'My\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return false; };
$namespace = 'Example';
$uses = array();
$relativeFunction = 'f';

// Output
$absoluteFunction = 'Example\\f';

// Input
$isFunction = function ($function) { return $function === 'f'; };
$namespace = 'Example';
$uses = array();
$relativeFunction = 'f';

// Output
$absoluteFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example';
$uses = array();
$relativeFunction = '\\f';

// Output
$absoluteFunction = 'f';

// Input
$isFunction = function ($function) { return $function === 'My\\f'; };
$namespace = 'Example';
$uses = array();
$relativeFunction = '\\My\\f';

// Output
$absoluteFunction = 'My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example';
$uses = array();
$relativeFunction = '\\My\\f';

// Output
$absoluteFunction = 'My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array();
$relativeFunction = 'f';

// Output
$absoluteFunction = 'Example\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array();
$relativeFunction = '\\Organization\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array('My' => 'Org');
$relativeFunction = '\\Organization\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array('My' => 'Organization');
$relativeFunction = 'My\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = array('My' => 'Organization\\Project');
$relativeFunction = 'My\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = array();
$relativeFunction = 'Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = array('My' => 'Org');
$relativeFunction = 'Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = array('My' => 'Organization');
$relativeFunction = 'My\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = array('My' => 'Organization\\Project');
$relativeFunction = 'My\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = array();
$relativeFunction = 'f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = array('My' => 'Org');
$relativeFunction = 'f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = array('My' => 'Organization');
$relativeFunction = 'My\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = array('My' => 'Organization\\Project');
$relativeFunction = 'My\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';
