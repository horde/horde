<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname($_SELF['PHP_SELF']));
}

require_once SHOUT_BASE . "/lib/base.php";

# instantiate driver

# Get list of available contexts from the driver
$contexts = $shout->getContexts();
if (is_a($contexts, 'PEAR_Error')) {
    $notification->push(_("Internal error viewing requested page"),
                        'horde.error');
}
# Print the contexts
foreach($contexts as $context) {
    print "$context<br>\n";
}