<?php

test('pre-push cancels after exporting and the next push sends the generated commit', function () {
    $project = sys_get_temp_dir() . '/patudo-pre-push-' . bin2hex(random_bytes(6));
    $remote = $project . '-remote.git';

    $run = function (string $directory, string $command): array {
        exec('cd ' . escapeshellarg($directory) . ' && ' . $command . ' 2>&1', $output, $status);

        return [$status, implode("\n", $output)];
    };

    $removeDirectory = function (string $directory) use (&$removeDirectory): void {
        if (! is_dir($directory)) {
            return;
        }

        foreach (array_diff(scandir($directory), ['.', '..']) as $entry) {
            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    };

    mkdir($project . '/scripts', 0777, true);
    mkdir($project . '/database/seeders', 0777, true);

    try {
        [$status] = $run(dirname($project), 'git init --bare --initial-branch=main ' . escapeshellarg($remote));
        expect($status)->toBe(0);

        [$status] = $run($project, 'git init --initial-branch=main');
        expect($status)->toBe(0);

        file_put_contents($project . '/database/seeders/ExportedDataSeeder.php', "<?php // previous data\n");
        file_put_contents(
            $project . '/artisan',
            "<?php\nfile_put_contents(__DIR__ . '/database/seeders/ExportedDataSeeder.php', \"<?php // latest data\\n\");\n"
        );
        copy(dirname(__DIR__, 2) . '/scripts/pre-push', $project . '/scripts/pre-push');
        chmod($project . '/scripts/pre-push', 0755);

        foreach (['user.name' => 'Hook Test', 'user.email' => 'hook-test@example.invalid'] as $key => $value) {
            [$status] = $run($project, 'git config ' . escapeshellarg($key) . ' ' . escapeshellarg($value));
            expect($status)->toBe(0);
        }

        [$status] = $run($project, 'git add database/seeders/ExportedDataSeeder.php artisan');
        expect($status)->toBe(0);
        [$status] = $run($project, 'git commit -m initial');
        expect($status)->toBe(0);
        [$status] = $run($project, 'git remote add origin ' . escapeshellarg($remote));
        expect($status)->toBe(0);
        [$status] = $run($project, 'git push -u origin main');
        expect($status)->toBe(0);
        [$initialRemoteCommit] = $run($project, 'git --git-dir=' . escapeshellarg($remote) . ' rev-parse refs/heads/main');

        [$status] = $run($project, 'git config core.hooksPath scripts');
        expect($status)->toBe(0);

        [$firstPushStatus] = $run($project, 'git push origin main');
        expect($firstPushStatus)->not->toBe(0);
        [$generatedCommit] = $run($project, 'git rev-parse HEAD');
        [$remoteCommitAfterFirstPush] = $run($project, 'git --git-dir=' . escapeshellarg($remote) . ' rev-parse refs/heads/main');
        expect($remoteCommitAfterFirstPush)->toBe($initialRemoteCommit);

        [$secondPushStatus] = $run($project, 'git push origin main');
        expect($secondPushStatus)->toBe(0);
        [$remoteCommitAfterSecondPush] = $run($project, 'git --git-dir=' . escapeshellarg($remote) . ' rev-parse refs/heads/main');
        expect($remoteCommitAfterSecondPush)->toBe($generatedCommit);
    } finally {
        $removeDirectory($project);
        $removeDirectory($remote);
    }
});