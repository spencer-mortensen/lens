<?php

namespace Lens_0_0_56\Lens\Php;

// Test
$namespacing = new Namespacing($isFunction, $namespace, $uses);
$relativeFunction = $namespacing->getRelativeFunction($absoluteFunction);

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = [];
$absoluteFunction = 'f';

// Output
$relativeFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = [];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = ['My' => 'Org'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = ['My' => 'Organization'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = ['My' => 'Organization\\Project'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\f';

// Input
$isFunction = function ($function) { return $function === 'f'; };
$namespace = 'Example';
$uses = [];
$absoluteFunction = 'f';

// Output
$relativeFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example';
$uses = [];
$absoluteFunction = 'f';

// Output
$relativeFunction = '\\f';

// Input
$isFunction = function ($function) { return $function === 'My\\f'; };
$namespace = 'Example';
$uses = [];
$absoluteFunction = 'My\\f';

// Output
$relativeFunction = '\\My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example';
$uses = [];
$absoluteFunction = 'My\\f';

// Output
$relativeFunction = '\\My\\f';


// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = [];
$absoluteFunction = 'f';

// Output
$relativeFunction = '\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = [];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = '\\Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = ['My' => 'Org'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = '\\Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = ['My' => 'Organization'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = ['My' => 'Organization\\Project'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = [];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = ['My' => 'Org'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = ['My' => 'Organization'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = ['My' => 'Organization\\Project'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = [];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = ['My' => 'Org'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = ['My' => 'Organization'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = ['My' => 'Organization\\Project'];
$absoluteFunction = 'Organization\\Project\\f';

// Output
$relativeFunction = 'My\\f';



// Test
$namespacing = new Namespacing($isFunction, $namespace, $uses);
$absoluteFunction = $namespacing->getAbsoluteFunction($relativeFunction);

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = [];
$relativeFunction = 'f';

// Output
$absoluteFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = [];
$relativeFunction = 'Organization\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = ['My' => 'Org'];
$relativeFunction = 'Organization\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = ['My' => 'Organization'];
$relativeFunction = 'My\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = null;
$uses = ['My' => 'Organization\\Project'];
$relativeFunction = 'My\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return false; };
$namespace = 'Example';
$uses = [];
$relativeFunction = 'f';

// Output
$absoluteFunction = 'Example\\f';

// Input
$isFunction = function ($function) { return $function === 'f'; };
$namespace = 'Example';
$uses = [];
$relativeFunction = 'f';

// Output
$absoluteFunction = 'f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example';
$uses = [];
$relativeFunction = '\\f';

// Output
$absoluteFunction = 'f';

// Input
$isFunction = function ($function) { return $function === 'My\\f'; };
$namespace = 'Example';
$uses = [];
$relativeFunction = '\\My\\f';

// Output
$absoluteFunction = 'My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example';
$uses = [];
$relativeFunction = '\\My\\f';

// Output
$absoluteFunction = 'My\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = [];
$relativeFunction = 'f';

// Output
$absoluteFunction = 'Example\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = [];
$relativeFunction = '\\Organization\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = ['My' => 'Org'];
$relativeFunction = '\\Organization\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = ['My' => 'Organization'];
$relativeFunction = 'My\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Example\\Project';
$uses = ['My' => 'Organization\\Project'];
$relativeFunction = 'My\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = [];
$relativeFunction = 'Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = ['My' => 'Org'];
$relativeFunction = 'Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = ['My' => 'Organization'];
$relativeFunction = 'My\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization';
$uses = ['My' => 'Organization\\Project'];
$relativeFunction = 'My\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = [];
$relativeFunction = 'f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = ['My' => 'Org'];
$relativeFunction = 'f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = ['My' => 'Organization'];
$relativeFunction = 'My\\Project\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';

// Input
$isFunction = function () { return true; };
$namespace = 'Organization\\Project';
$uses = ['My' => 'Organization\\Project'];
$relativeFunction = 'My\\f';

// Output
$absoluteFunction = 'Organization\\Project\\f';
