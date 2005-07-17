<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}
$users = $shout->getUsers($context);
ksort($users);
require SHOUT_TEMPLATES . "/users/userlist.inc";