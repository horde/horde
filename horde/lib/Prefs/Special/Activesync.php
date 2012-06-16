<?php
/**
 * Special prefs handling for the 'activesyncmanagement' preference.
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

        $auth = $registry->getAuth();
        $stateMachine = $injector->getInstance('Horde_ActiveSyncState');
        $devices = $stateMachine->listDevices($auth);

        $js = array();
        foreach ($devices as $key => $val) {
            $js[$key] = array(
                'id' => $val['device_id'],
                'user' => $val['device_user']
            );
        }

        $page_output->addScriptFile('activesyncprefs.js', 'horde');
        $page_output->addInlineJsVars(array(
            'HordeActiveSyncPrefs.devices' => $js
        ));

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $selfurl = $ui->selfUrl();
        $t->set('reset', $selfurl->copy()->add('reset', 1));
        $devs = array();

        foreach ($devices as $key => $device) {
            $device['class'] = fmod($key, 2) ? 'rowOdd' : 'rowEven';
            $device['key'] = $key;

            $stateMachine->loadDeviceInfo($device['device_id'], $auth);
            $ts = $stateMachine->getLastSyncTimestamp();
            $device['ts'] = empty($ts) ? _("None") : strftime($prefs->getValue('date_format') . ' %H:%M', $ts);

            switch ($device['device_rwstatus']) {
            case Horde_ActiveSync::RWSTATUS_PENDING:
                $status = '<span class="notice">' . _("Wipe is pending") . '</span>';
                $device['ispending'] = true;
                break;

            case Horde_ActiveSync::RWSTATUS_WIPED:
                $status = '<span class="notice">' . _("Device is wiped") . '</span>';
                break;

            default:
                $status = $device['device_policykey']
                    ? _("Provisioned")
                    : _("Not Provisioned");
                break;
            }

            $device['wipe'] = $selfurl->copy()->add('wipe', $device['device_id']);
            $device['remove'] = $selfurl->copy()->add('remove', $device['device_id']);
            $device['status'] = $status . '<br />' . _("Device id:") . $device['device_id'] . '<br />' . _("Policy Key:") . $device['device_policykey'] . '<br />' . _("User Agent:") . $device['device_agent'];

            $devs[] = $device;
        }

        $t->set('devices', $devs);

        return $t->fetch(HORDE_TEMPLATES . '/prefs/activesync.html');
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
