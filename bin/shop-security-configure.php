<?php

/**
 * Idempotent security hardening setup, run via WP-CLI:
 *
 *   wp eval-file bin/shop-security-configure.php
 *
 * Asserts Wordfence's own safe-by-default settings for a fresh activation
 * (firewall, login security, strong/breached-password enforcement for
 * admins) rather than leaving them to chance if a future Wordfence version
 * ever changes its defaults. Values match wfConfig.php's own "out of the
 * box" defaults for a new install — this doesn't change existing behavior,
 * it just makes it explicit and reproducible.
 *
 * Deliberately NOT touched here: Wordfence's WAF "Extended Protection" mode
 * (which needs a manual auto_prepend_file/.user.ini change that's risky to
 * script blindly across unknown hosting environments) and any IP/rule
 * allowlisting. Review the Wordfence dashboard after a traffic-learning
 * period before opting into Extended Protection.
 *
 * File editing in wp-admin is already disabled unconditionally — see
 * DISALLOW_FILE_EDIT in config/application.php — so there's nothing to do
 * for that here.
 */

if (! defined('WP_CLI')) {
    WP_CLI::error('This must be run via WP-CLI.');
}

if (! class_exists('wfConfig')) {
    WP_CLI::error('Wordfence must be an active plugin before running this script (wp plugin activate wordfence).');
}

$security_settings = [
    // Web application firewall.
    'firewallEnabled' => true,
    'disableWAFIPBlocking' => false,
    'autoBlockScanners' => true,

    // Login security / brute-force protection.
    'loginSecurityEnabled' => true,
    'loginSec_maxFailures' => 20,
    'loginSec_countFailMins' => 240,
    'loginSec_lockoutMins' => 240,
    'loginSec_maskLoginErrors' => true,
    'loginSec_blockAdminReg' => true,

    // Password policy: administrators and publishers must use a strong
    // password, and their passwords are checked against known breach lists.
    'loginSec_strongPasswds_enabled' => true,
    'loginSec_strongPasswds' => 'pubs',
    'loginSec_breachPasswds_enabled' => true,
    'loginSec_breachPasswds' => 'admins',

    // Malware/vulnerability scanning.
    'scansEnabled_core' => true,
    'scansEnabled_coreUnknown' => true,
    'scansEnabled_malware' => true,
    'scansEnabled_fileContents' => true,
    'scansEnabled_suspiciousOptions' => true,
    'scansEnabled_passwds' => true,
];

foreach ($security_settings as $key => $value) {
    $current = wfConfig::get($key);

    // wfConfig stores booleans as '1'/'' strings; compare loosely so this
    // doesn't log a "changed" line on every run for values already correct.
    if ($current == $value) {
        continue;
    }

    wfConfig::set($key, $value);
    WP_CLI::log("Set {$key} = " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
}

WP_CLI::success('Wordfence configured: firewall, login security, and admin/publisher password policy enforced.');
