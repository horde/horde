<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}

// # Check that we are properly initialized
// if (is_a($contexts, 'PEAR_Error')) {
//     # FIXME change this to a Horde::fatal
//     $notification->push(_("Internal error viewing requested page"),
//                         'horde.error');
//     $notification->notify();
//     require $registry->get('templates', 'horde') . '/common-footer.inc';
//     exit();
// }
// 
// 
// print_r($shout->getUsers($context));