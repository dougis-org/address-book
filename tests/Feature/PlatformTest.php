<?php

test('runs on PHP 8.3', function () {
    expect(PHP_VERSION)->toStartWith('8.3.');
});

test('runs on Laravel 13', function () {
    expect(app()->version())->toStartWith('13.');
});
