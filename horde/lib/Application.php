<?php
/**
 * Horde application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @package Horde
 */

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once __DIR__ . '/core.php';

class Horde_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = '5.0.0beta1';

    /**
     */
    public $features = array(
        'smartmobileView' => true
    );

    /**
     */
    public function logout()
    {
        // Destroy any session-only temp files (since Horde_Core 1.7.0).
        foreach ($GLOBALS['session']->get('horde', 'gc_tempfiles', Horde_Session::TYPE_ARRAY) as $file) {
            @unlink($file);
        }
    }

    /**
     */
    public function perms()
    {
        $permissions = array(
            'max_blocks' => array(
                'title' => _("Maximum Number of Portal Blocks"),
                'type' => 'int'
            ),
            'administration' => array(
                'title' => _("Administration"),
            )
        );

        if (!empty($GLOBALS['conf']['activesync']['enabled'])) {
            $this->_addActiveSyncPerms($permissions);
        }

        try {
            foreach ($GLOBALS['registry']->callByPackage('horde', 'admin_list') as $perm_key => $perm_details) {
                $permissions['administration:' . $perm_key] = array('title' => Horde::stripAccessKey($perm_details['name']));
            }
        } catch (Horde_Exception $e) {/*what to do if this fails?*/}

        return $permissions;
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_blocks':
                $allowed = max($allowed);
                break;
            }
        }
        return $allowed;
    }

    /**
     */
    public function configSpecialValues($what)
    {
        switch ($what) {
        case 'apps':
            $apps = Horde_Array::valuesToKeys($GLOBALS['registry']->listApps(array('active')));
            asort($apps);
            return $apps;

        case 'languages':
            return array_map(create_function('$val', 'return preg_replace(array("/&#x([0-9a-f]{4});/ie", "/(&[^;]+;)/e"), array("Horde_String::convertCharset(pack(\"H*\", \"$1\"), \"ucs-2\", \"UTF-8\")", "Horde_String::convertCharset(html_entity_decode(\"$1\", ENT_COMPAT, \"iso-8859-1\"), \"iso-8859-1\", \"UTF-8\")"), $val);'), $GLOBALS['registry']->nlsconfig->languages);

        case 'blocks':
            return $GLOBALS['injector']->getInstance('Horde_Core_Factory_BlockCollection')->create()->getBlocksList();
        }
    }

    /**
     */
    public function removeUserData($user)
    {
        $error = false;

        /* Remove user from all groups */
        $groups = $GLOBALS['injector']->getInstance('Horde_Group');
        try {
            $allGroups = $groups->listGroups($user);
            foreach (array_keys($allGroups) as $id) {
                $groups->removeUser($id, $user);
            }
        } catch (Horde_Group_Exception $e) {
            Horde::logMessage($e, 'NOTICE');
            $error = true;
        }

        /* Remove the user from all application permissions */
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        try {
            $tree = $perms->getTree();
        } catch (Horde_Perms_Exception $e) {
            Horde::logMessage($e, 'NOTICE');
            $error = true;
            $tree = array();
        }

        foreach (array_keys($tree) as $id) {
            try {
                $perm = $perms->getPermissionById($id);
                if ($perms->getPermissions($perm, $user)) {
                    // The Horde_Perms::ALL is used if this is a matrix perm,
                    // otherwise it's ignored in the method and the entry is
                    // totally removed.
                    $perm->removeUserPermission($user, Horde_Perms::ALL, true);
                }
            } catch (Horde_Perms_Exception $e) {
                Horde::logMessage($e, 'NOTICE');
                $error = true;
            }
        }

        if ($error) {
            throw new Horde_Exception(sprintf(_("There was an error removing global data for %s. Details have been logged."), $user));
        }
    }

    protected function _addActiveSyncPerms(&$permissions)
    {
        $prefix = 'activesync:provisioning:';

        $permissions['activesync'] = array(
            'title' => _("ActiveSync"),
            'type' => 'boolean'
        );

        $permissions['activesync:provisioning'] = array(
            'title' => _("Provisioning"),
            'type' => 'enum',
            'params' => array(array(
                false => '',
                'false' => _("Never"),
                'true' => _("Force"),
                'allow' => _("Allow"),
            ))
        );

        $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_PIN] = array(
            'title' => _("Require PIN"),
            'type' => 'boolean'
        );

        $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_MINLENGTH] = array(
            'title' => _("Minimum PIN length"),
            'type' => 'int'
        );

        $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_COMPLEXITY] = array(
            'title' => _("Password Complexity"),
            'type' => 'enum',
            'params' => array(array(
                false => '',
                0 => _("Allow only numeric"),
                1 => _("Allow alphanumeric"),
                2 => _("Allow any"))
            )
        );

        $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_AEFVALUE] = array(
            'title' => _("Minutes of inactivity before device should lock"),
            'type' => 'int'
        );

        $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_MAXFAILEDATTEMPTS] = array(
            'title' => _("Failed unlock attempts before device is wiped"),
            'type' => 'int'
        );

        $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_CODEFREQ] = array(
            'title' => _("Codeword frequency"),
            'type' => 'int'
        );

        // EAS 12.0 and above.
        if ($GLOBALS['conf']['activesync']['version'] == Horde_ActiveSync::VERSION_TWELVE) {
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ATC] = array(
                'title' => _("Attachment Download"),
                'type' => 'boolean'
            );

            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_MAXATCSIZE] = array(
                'title' => _("Maximum attachment size"),
                'type' => 'int'
            );

            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ENCRYPTION] = array(
                'title' => _("SD card encryption"),
                'type' => 'boolean'
            );
        }

        if ($GLOBALS['conf']['activesync']['version'] > Horde_ActiveSync::VERSION_TWELVE) {
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ALLOW_SDCARD] = array(
                'title' => _("SD card"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ALLOW_CAMERA] = array(
                'title' => _("Camera"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ALLOW_SMS] = array(
                'title' => _("SMS Text messages"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ALLOW_WIFI] = array(
                'title' => _("Wifi"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ALLOW_BLUETOOTH] = array(
                'title' => _("Bluetooth"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ALLOW_POPIMAP] = array(
                'title' => _("POP/IMAP Email accounts"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ALLOW_BROWSER] = array(
                'title' => _("Web browser"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_REQUIRE_SMIME_ENCRYPTED] = array(
                'title' => _("Require S/MIME Encryption"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_REQUIRE_SMIME_SIGNED] = array(
                'title' => _("Require S/MIME Signature"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_DEVICE_ENCRYPTION] = array(
                'title' => _("Device encryption"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ALLOW_HTML] = array(
                'title' => _("HTML Email"),
                'type' => 'boolean'
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_MAX_EMAIL_AGE] = array(
                'title' => _("Maximum Email age"),
                'type' => 'enum',
                'params' => array(array(
                    false => '',
                    0 => _("Sync all"),
                    1 => _("1 Day"),
                    2 => _("3 Days"),
                    3 => _("1 Week"),
                    4 => _("2 Weeks"),
                    5 => _("1 Month"))
                )
            );
            $permissions[$prefix . Horde_ActiveSync_Policies::POLICY_ROAMING_NOPUSH] = array(
                'title' => _("No push while roaming"),
                'type' => 'boolean'
            );
        }
    }

}
