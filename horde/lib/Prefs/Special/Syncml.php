<?php
/**
 * Special prefs handling for the 'syncmlmanagement' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Horde
 */
class Horde_Prefs_Special_Syncml implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $page_output, $prefs, $registry;

        $page_output->addScriptFile('syncmlprefs.js', 'horde');
        $devices = Horde_SyncMl_Backend::factory('Horde')->getUserAnchors($registry->getAuth());

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $partners = array();
        $format = $prefs->getValue('date_format') . ' %H:%M';

        foreach ($devices as $device) {
            $partners[] = array(
                'anchor'   => htmlspecialchars($device['syncml_clientanchor']),
                'db'       => htmlspecialchars($device['syncml_db']),
                'deviceid' => $device['syncml_syncpartner'],
                'rawdb'    => $device['syncml_db'],
                'device'   => htmlspecialchars($device['syncml_syncpartner']),
                'time'     => strftime($format, $device['syncml_serveranchor'])
            );
        }
        $t->set('devices', $partners);

        return $t->fetch(HORDE_TEMPLATES . '/prefs/syncml.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $notification, $registry;

        $auth = $registry->getAuth();
        $backend = Horde_SyncMl_Backend::factory('Horde');

        if ($ui->vars->removedb && $ui->vars->removedevice) {
            try {
                $backend->removeAnchor($auth, $ui->vars->removedevice, $ui->vars->removedb);
                $backend->removeMaps($auth, $ui->vars->removedevice, $ui->vars->removedb);
                $notification->push(sprintf(_("Deleted synchronization session for device \"%s\" and database \"%s\"."), $ui->vars->deviceid, $ui->vars->db), 'horde.success');
            } catch (Horde_Exception $e) {
                $notification->push(_("Error deleting synchronization session:") . ' ' . $e->getMessage(), 'horde.error');
            }
        } elseif ($ui->vars->deleteall) {
            try {
                $backend->removeAnchor($auth);
                $backend->removeMaps($auth);
                $notification->push(_("All synchronization sessions deleted."), 'horde.success');
            } catch (Horde_Exception $e) {
                $notification->push(_("Error deleting synchronization sessions:") . ' ' . $e->getMessage(), 'horde.error');
            }
        }

        return false;
    }

}
