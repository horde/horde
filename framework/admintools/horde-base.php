<?php
// Use the HORDE_BASE environment variable if it's set.
if ((($base = getenv('HORDE_BASE')) ||
     (!empty($_ENV['HORDE_BASE']) && $base = $_ENV['HORDE_BASE'])) &&
    is_dir($base) && is_readable($base)) {
    $horde_base = $base;
} elseif (is_file(getcwd() . '/lib/Application.php')) {
    $horde_base = getcwd();
} else {
    $horde_base = dirname(dirname(dirname(__FILE__)));
}

require_once $horde_base . '/lib/Application.php';
