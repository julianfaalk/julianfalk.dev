<?php
// Lightweight .env loader for local configuration

function loadEnvFile($path)
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Remove optional surrounding quotes
        $valueLength = strlen($value);
        if ($valueLength >= 2) {
            $firstChar = $value[0];
            $lastChar = $value[$valueLength - 1];
            if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if ($key === '') {
            continue;
        }

        // Only set if not already present in the environment
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    $loaded = true;
}
