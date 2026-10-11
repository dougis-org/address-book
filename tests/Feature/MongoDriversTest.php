<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

test('the mongodb cache store round-trips values', function () {
    $cache = Cache::store('mongodb');

    $cache->put('greeting', 'hello', 60);

    expect($cache->get('greeting'))->toBe('hello');
    expect(DB::connection('mongodb')->getCollection('cache')->countDocuments())->toBe(1);
});

test('the mongodb cache store supports atomic locks', function () {
    $lock = Cache::store('mongodb')->lock('import', 10);

    expect($lock->get())->toBeTrue();
    expect(Cache::store('mongodb')->lock('import', 10)->get())->toBeFalse();

    $lock->release();
});

test('the mongodb queue accepts a job', function () {
    config(['queue.default' => 'mongodb']);

    Queue::connection('mongodb')->push(fn () => null);

    expect(DB::connection('mongodb')->getCollection('jobs')->countDocuments())->toBe(1);
});

test('failed jobs are stored in MongoDB', function () {
    $uuid = (string) Str::uuid();

    app('queue.failer')->log('mongodb', 'default', json_encode(['uuid' => $uuid, 'displayName' => 'X']), new Exception('boom'));

    expect(DB::connection('mongodb')->getCollection('failed_jobs')->countDocuments(['uuid' => $uuid]))->toBe(1);
});
