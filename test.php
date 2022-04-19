<?php

require "PromiseState.php";
require "Promise.php";
require "../coroutine-sdk/Schedule.php";
require "../coroutine-sdk/Task.php";
require "../coroutine-sdk/Assist.php";

function getPromise()
{
    return new Promise(function($resolve, $reject) {});
}
$promise = null;
function gen()
{
    global $promise;
    $promise = getPromise();
    yield $promise->resolve('start');
}

$schedule = new Schedule();
$schedule->newTask(gen());
$schedule->run();
$promise2 = $promise->then(function($value) {
    return 222;
}, function ($reason) {
    var_dump($reason);
});

$promise2->then(function($value) {
    var_dump($value);
}, function ($reason) {
    var_dump($reason);
});



