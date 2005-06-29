<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}

require_once SHOUT_BASE . "/lib/base.php";
require_once SHOUT_BASE . "/lib/Shout.php";

# Get list of available contexts from the driver
$contexts = $shout->getContexts();
if (is_a($contexts, 'PEAR_Error')) {
    $notification->push(_("Internal error viewing requested page"),
                        'horde.error');
}

if (count($contexts) < 1) {
    $notification->push(_("You do not have permission to access this
system.", 'horde.error'));
    exit();
} elseif (count($contexts) == 1) {
    header("Location: " .
        Horde::applicationUrl("users.php?context=$contexts[0]"));
    exit();
}

# Print the contexts
foreach($contexts as $context) {
    print "$context<br>\n";
}