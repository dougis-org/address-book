<?php

test('does not depend on the paid flux-pro package', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composer['require'] ?? [])->not->toHaveKey('livewire/flux-pro');
    expect($composer['require-dev'] ?? [])->not->toHaveKey('livewire/flux-pro');
});

test('composer pins the platform PHP to 8.3', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composer['require']['php'])->toBe('^8.3');
    expect($composer['config']['platform']['php'] ?? null)->toBe('8.3.0');
});
