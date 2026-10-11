<?php

use Symfony\Component\Process\Process;

test('.env is not tracked by git', function () {
    $git = new Process(['git', 'ls-files', '--', '.env'], base_path());
    $git->run();

    if (! $git->isSuccessful()) {
        $this->markTestSkipped('Not inside a usable git checkout.');
    }

    expect(trim($git->getOutput()))->toBe('');
});

test('.env.example contains placeholders only', function () {
    $path = base_path('.env.example');

    expect(file_exists($path))->toBeTrue();

    $placeholders = ['', 'null', 'password', 'secret', 'changeme'];

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $value = strtolower(trim($value, " \t\"'"));

        if (preg_match('/(PASSWORD|SECRET|TOKEN|_KEY)$/', $key)) {
            expect(in_array($value, $placeholders, true))->toBeTrue("{$key} must be a placeholder");
        }
    }
});
