#!/usr/bin/env php
<?php
if (strpos('@php_dir@', '@php_dir') === 0) {
    set_include_path(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR . get_include_path());var_dump(get_include_path());
}

require_once 'Horde/Autoloader/Default.php';
Components::main();
