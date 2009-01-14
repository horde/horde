<?php


// Local route overrides
if (file_exists(dirname(__FILE__) . '/routes.local.php')) {
    include dirname(__FILE__) . '/routes.local.php';
}
