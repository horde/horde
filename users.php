<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}

require_once SHOUT_BASE . "/lib/base.php";
require_once SHOUT_BASE . "/lib/Shout.php";

# Verify the current user has access to this context
$context = Util::getFormData("context");

if (!in_array($context, $shout->getContexts())) {
    $notification->push(_("You do not have permission to access this system.",
        'horde.error'));
    exit();
}

print_r($shout->getUsers($context));