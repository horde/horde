<?php
/**
 * Special prefs handling for the 'activesyncmanagement' preference.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL
 * @package  Horde
 */
class Horde_Prefs_Special_Activesync implements Horde_Core_Prefs_Ui_Special
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
        global $conf, $injector, $page_output, $prefs, $registry;

        if (empty($conf['activesync']['enabled'])) {
            return _("ActiveSync not activated.");
        }
        $stateMachine = $injector->getInstance('Horde_ActiveSyncState');
        $devices = $stateMachine->listDevices($registry->getAuth());

        $view = new Horde_View(array(
            'templatePath' => HORDE_TEMPLATES . '/prefs'
        ));

        $selfurl = $ui->selfUrl();
        $view->reset = $selfurl->copy()->add('reset', 1);
        $devs = array();
        $js = array();
        foreach ($devices as $key => $device) {
            $dev = $stateMachine->loadDeviceInfo($device['device_id'], $registry->getAuth());
            $js[$dev->id] = array(
                'id' => $dev->id,
                'user' => $dev->user
            );
            $devs[] = $dev;
        }
        $page_output->addScriptFile('activesyncprefs.js', 'horde');
        $page_output->addInlineJsVars(array(
            'HordeActiveSyncPrefs.devices' => $js
        ));
        $view->devices = $devs;

        return $view->render('activesync');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $notification, $registry;

        $auth = $registry->getAuth();
        $stateMachine = $injector->getInstance('Horde_ActiveSyncState');
        $stateMachine->setLogger($injector->getInstance('Horde_Log_Logger'));

        try {
            if ($ui->vars->wipeid) {
                $stateMachine->loadDeviceInfo($ui->vars->wipeid, $auth);
                $stateMachine->setDeviceRWStatus($ui->vars->wipeid, Horde_ActiveSync::RWSTATUS_PENDING);
                $notification->push(sprintf(_("A remote wipe for device id %s has been initiated. The device will be wiped during the next synchronisation."), $ui->vars->wipe));
            } elseif ($ui->vars->cancelwipe) {
                $stateMachine->loadDeviceInfo($ui->vars->cancelwipe, $auth);
                $stateMachine->setDeviceRWStatus($ui->vars->cancelwipe, Horde_ActiveSync::RWSTATUS_OK);
                $notification->push(sprintf(_("The Remote Wipe for device id %s has been cancelled."), $ui->vars->wipe));
            } elseif ($ui->vars->reset) {
                $devices = $stateMachine->listDevices($auth);
                foreach ($devices as $device) {
                    $stateMachine->removeState(array(
                        'devId' => $device['device_id'],
                        'user' => $auth
                    ));
                }
                $notification->push(_("All state removed for your ActiveSync devices. They will resynchronize next time they connect to the server."));
            } elseif ($ui->vars->removedevice) {
                $stateMachine->removeState(array(
                    'devId' => $ui->vars->removedevice,
                    'user' => $auth
                ));
                $notification->push(sprintf(_("The state for device id %s has been reset. It will resynchronize next time it connects to the server."), $ui->vars->removedevice));
            }
        } catch (Horde_ActiveSync_Exception $e) {
            $notification->push(_("There was an error communicating with the ActiveSync server: %s"), $e->getMessage(), 'horde.err');
        }

        return false;
    }

}
