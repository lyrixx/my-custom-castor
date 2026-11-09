<?php

namespace binary;

use Castor\Attribute\AsTask;

use function Castor\capture;
use function Castor\context;
use function Castor\fs;
use function Castor\http_download;
use function Castor\io;
use function Castor\run;

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

const BINARY_PATH = __DIR__ . '/build/castor.linux-amd64';
const PHAR_PATH = __DIR__ . '/build/castor.linux-amd64.phar';
// The list of tools/static/castor.php in jolicode/castor, plus pdo and pdo_sqlite.
const PHP_EXTENSIONS = 'mbstring,phar,posix,tokenizer,pcntl,curl,filter,openssl,sodium,ctype,zip,bz2,iconv,dom,pdo,pdo_sqlite';
const REPOSITORY = 'lyrixx/my-custom-castor';
const UPSTREAM_REPOSITORY = 'jolicode/castor';

#[AsTask(description: 'Compile a Castor release with pdo_sqlite, and check the binary')]
function build(string $version): void
{
    fs()->mkdir(\dirname(PHAR_PATH));

    http_download(\sprintf('https://github.com/%s/releases/download/%s/castor.linux-amd64.phar', UPSTREAM_REPOSITORY, $version), PHAR_PATH);

    run([
        'castor',
        'compile',
        PHAR_PATH,
        '--binary-path=' . BINARY_PATH,
        '--os=linux',
        '--arch=x86_64',
        '--php-extensions=' . PHP_EXTENSIONS,
    ], context: context()->withTimeout(0));

    run([BINARY_PATH, '--version']);
    run([BINARY_PATH, '--castor-file=' . __DIR__ . '/tests/smoke.php', 'smoke:sqlite']);
}

#[AsTask(description: 'Compile and publish the latest Castor release, unless it is already published')]
function publish(): void
{
    $version = capture(['gh', 'release', 'view', '--repo=' . UPSTREAM_REPOSITORY, '--json=tagName', '--jq=.tagName']);

    if (isReleased($version)) {
        io()->note(\sprintf('Castor %s is already published.', $version));

        return;
    }

    build($version);

    run([
        'gh',
        'release',
        'create',
        $version,
        BINARY_PATH,
        '--repo=' . REPOSITORY,
        '--title=' . $version,
        \sprintf('--notes=Built from https://github.com/%s/releases/tag/%s, with pdo_sqlite.', UPSTREAM_REPOSITORY, $version),
    ]);
}

// GitHub disables the schedule of a public repository after 60 days without activity.
#[AsTask(description: 'Push an empty commit when the repository has been quiet for 45 days')]
function keepScheduleAlive(): void
{
    $lastCommitTimestamp = (int) capture(['git', 'log', '-1', '--format=%ct']);

    if ($lastCommitTimestamp > new \DateTimeImmutable('-45 days')->getTimestamp()) {
        return;
    }

    run([
        'git',
        '-c',
        'user.name=github-actions[bot]',
        '-c',
        'user.email=41898282+github-actions[bot]@users.noreply.github.com',
        'commit',
        '--allow-empty',
        '--message=Keep the schedule alive',
    ]);
    run(['git', 'push']);
}

function isReleased(string $version): bool
{
    return run(
        ['gh', 'release', 'view', $version, '--repo=' . REPOSITORY],
        context: context()->withAllowFailure()->withQuiet(),
    )->isSuccessful();
}
