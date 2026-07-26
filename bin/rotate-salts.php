<?php

/**
 * Rotates the WordPress authentication keys/salts in a .env file.
 *
 * Manual, on-demand operation — deliberately NOT part of the reproducible
 * composer-install + seed chain (bin/woocommerce-setup.sh never calls this).
 * Rotating these values immediately invalidates every logged-in session,
 * "remember me" cookie, and password-reset link, so run it deliberately
 * (e.g. after a suspected compromise, or per a periodic rotation policy),
 * not as a side effect of routine setup.
 *
 * Usage (from the project root):
 *   php bin/rotate-salts.php            # rotates .env
 *   php bin/rotate-salts.php .env.local # rotates a specific file
 *
 * Needs no WordPress bootstrap — these are pre-database bootstrap secrets
 * (see config/application.php), so this is plain PHP + filesystem access.
 */

const SALT_KEYS = [
    'AUTH_KEY',
    'SECURE_AUTH_KEY',
    'LOGGED_IN_KEY',
    'NONCE_KEY',
    'AUTH_SALT',
    'SECURE_AUTH_SALT',
    'LOGGED_IN_SALT',
    'NONCE_SALT',
];

/**
 * Excludes the single quote and backslash so the result is always safe to
 * embed as a single-quoted .env value with no escaping — WordPress's own
 * salts (see https://api.wordpress.org/secret-key/1.1/salt/) draw from a
 * similar-sized punctuation set and are just as strong for this purpose.
 */
const SALT_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 !#$%&()*+,-./:;<=>?@[]^_{|}~';

function generate_salt(int $length = 64): string
{
    $alphabetLength = strlen(SALT_ALPHABET);
    $salt = '';

    for ($i = 0; $i < $length; $i++) {
        $salt .= SALT_ALPHABET[random_int(0, $alphabetLength - 1)];
    }

    return $salt;
}

$targetFile = $argv[1] ?? dirname(__DIR__) . '/.env';

if (! file_exists($targetFile)) {
    fwrite(STDERR, "No such file: {$targetFile}\n");
    exit(1);
}

$contents = file_get_contents($targetFile);

foreach (SALT_KEYS as $key) {
    $newValue = "{$key}='" . generate_salt() . "'";
    $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

    if (preg_match($pattern, $contents)) {
        $contents = preg_replace($pattern, $newValue, $contents, 1);
    } else {
        $contents = rtrim($contents, "\n") . "\n{$newValue}\n";
    }
}

file_put_contents($targetFile, $contents);

fwrite(STDOUT, 'Rotated ' . count(SALT_KEYS) . " keys/salts in {$targetFile}.\n");
fwrite(STDOUT, "Every logged-in session, \"remember me\" cookie, and pending password-reset link is now invalid.\n");
