<?php

namespace smoke;

use Castor\Attribute\AsTask;

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

#[AsTask(description: 'Check that the compiled binary can use SQLite')]
function sqlite(): void
{
    $pdo = new \PDO('sqlite::memory:');

    echo 'SQLite ', $pdo->query('SELECT sqlite_version()')->fetchColumn(), \PHP_EOL;
}
