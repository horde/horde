<?php
/**
 * Passwd Hooks configuration file.
 *
 * THE HOOKS PROVIDED IN THIS FILE ARE EXAMPLES ONLY.  DO NOT ENABLE THEM
 * BLINDLY IF YOU DO NOT KNOW WHAT YOU ARE DOING.  YOU HAVE TO CUSTOMIZE THEM
 * TO MATCH YOUR SPECIFIC NEEDS AND SYSTEM ENVIRONMENT.
 *
 * For more information please see the horde/config/hooks.php.dist file.
 *
 * $Horde: passwd/config/hooks.php.dist,v 1.1.2.2 2009/06/12 08:43:47 jan Exp $
 */

// Here is an example _passwd_hook_username function to translate what the
// user enters, in the username box, into what the backend expects. If we want
// to add @example.com to the end of the username then enable the hook and use
// this function.

// if (!function_exists('_passwd_hook_username')) {
//     function _passwd_hook_username($userid, &$driver)
//     {
//         return $userid . '@example.com';
//     }
// }

// Here is another, more involed example of _passwd_hook_username.  This one
// demonstrates how to return a different $userid based on the driver type.  It
// also demonstrates how to do this using the composite driver.

//if (!function_exists('_passwd_hook_username')) {
//    function _passwd_hook_username($userid, &$driver)
//     {
//        if (is_a($driver, 'Passwd_Driver_http')) {
//            return $userid . '@example.com';
//        } elseif (is_a($driver, 'Passwd_Driver_composite')) {
//            foreach ($driver->_params['drivers'] as $backend => $config) {
//                if ($backend == 'http') {
//                    $driver->_params['drivers']['http']['params']['be_username'] = $userid . '@example.com';
//                    break;
//                }
//            }
            // Return the userid unmodified by default.
//            return $userid;
//        } else {
//           return $userid;
//        }
//     }
//}

// Here is an example _passwd_hook_default_username function to set the
// username the passwd module sees when resetting passwords based on userid
// and realm.  The default is to take a username of user@domain.tld and change
// it to user.  If we want to leave it untouched, enable the hook and use this
// function.

// if (!function_exists('_passwd_hook_default_username')) {
//     function _passwd_hook_default_username($userid)
//     {
//         return $userid;
//     }
// }

// Here is an example _passwd_hook_userdn function that you can use to provide
// your ldap server with a userdn so that you do not have to perform anonymous
// binds. The function takes Auth::getAuth() as a parameter

// if (!function_exists('_passwd_hook_userdn')) {
//     function _passwd_hook_userdn($auth)
//     {
//         return 'uid=' . $auth . ',o=example.com';
//     }
// }

// if (!function_exists('_passwd_password_changed')) {
//     function _passwd_password_changed($user, $oldpassword, $newpassword)
//     {
//         Horde::logMessage(sprintf('User %s has changed his password.', $user), __FILE__, __LINE__, PEAR_LOG_NOTICE);
//     }
// }
