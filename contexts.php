<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}

# Check that we are properly initialized
if (!isset($contexts) || is_a($contexts, 'PEAR_Error')) {
    $notification->push(_("Internal error viewing requested page"),
                        'horde.error');
}

if (count($contexts) < 1) {
    $notification->push(_("You do not have permission to access this
system.", 'horde.error'));
    exit();
} elseif (count($contexts) == 1) {
    header("Location: " .
        Horde::applicationUrl("index.php?context=$contexts[0]&section=users"));
    exit();
}

$notification->notify();

# Print the contexts
$toggle = false;
foreach($contexts as $context) {
    include SHOUT_TEMPLATES . "/context/contextline.inc";
    $toggle = !$toggle;
}