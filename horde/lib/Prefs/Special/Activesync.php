<?php
/**
 * Special prefs handling for the 'activesyncmanagement' preference.
 *
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
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
        global $injector, $page_output, $prefs, $registry;

        try {
            $state = $injector->getInstance('Horde_ActiveSyncState');
        } catch (Horde_Exception $e) {
            return _("ActiveSync not activated.");
        }

        $devices = $state->listDevices($registry->getAuth());

        $view = new Horde_View(array(
            'templatePath' => array(HORDE_TEMPLATES . '/prefs', HORDE_TEMPLATES . '/activesync')
        ));
        $view->addHelper('Tag');
        $view->isAdmin = false;

        $selfurl = $ui->selfUrl();
        $view->reset = $selfurl->copy()->add('reset', 1);
        $devs = array();
        $js = array();
        $collections = array();
        foreach ($devices as $device) {
            $dev = $state->loadDeviceInfo($device['device_id'], $registry->getAuth());
            try {
                $dev = $GLOBALS['injector']->getInstance('Horde_Core_Hooks')
                    ->callHook('activesync_device_modify', 'horde', array($dev));
            } catch (Horde_Exception_HookNotSet $e) {}
            $js[$dev->id . ':' . $registry->getAuth()] = array(
                'id' => $dev->id,
                'user' => $dev->user
            );
            $syncCache = new Horde_ActiveSync_SyncCache($state, $dev->id, $dev->user, $injector->getInstance('Horde_Log_Logger'));
            $dev->hbinterval = $syncCache->hbinterval
                ? $syncCache->hbinterval
                : ($syncCache->wait ? $syncCache->wait * 60 : _("Unavailable"));
            $devs[] = $dev;
            $collection = array();
            foreach ($syncCache->getCollections() as $id => $c) {
                $collection[] = array(
                    _("Collection id") => $id,
                    _("Class") => $c['class'],
                    _("Server Id") => $c['serverid'],
                    _("Last synckey") => $c['lastsynckey']
                );
            }
            $collections[] = $collection;
        }
        $view->collections = $collections;
        // Identities
        if (!$prefs->isLocked('activesync_identity')) {
            $ident = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Identity')
                ->create($registry->getAuth());
            $view->identities = $ident->getAll('id');
            $view->identities['horde'] = _("Use Horde Default");
            $view->default = $prefs->getValue('activesync_identity');
            if (is_null($view->default)) {
                $view->default = $prefs->getValue('default_identity');
            }
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
        $state = $injector->getInstance('Horde_ActiveSyncState');
        $state->setLogger($injector->getInstance('Horde_Log_Logger'));

        try {
            if ($ui->vars->wipeid) {
                if (!$state->deviceExists($ui->vars->wipeid, $auth)) {
                    throw new Horde_Exception_PermissionDenied();
                }
                $state->setDeviceRWStatus($ui->vars->wipeid, Horde_ActiveSync::RWSTATUS_PENDING);
                $notification->push(sprintf(_("A remote wipe for device id %s has been initiated. The device will be wiped during the next synchronisation."), $ui->vars->wipe));
            } elseif ($ui->vars->cancelwipe) {
                if (!$state->deviceExists($ui->vars->cancelwipe, $auth)) {
                    throw new Horde_Exception_PermissionDenied();
                }
                $state->setDeviceRWStatus($ui->vars->cancelwipe, Horde_ActiveSync::RWSTATUS_OK);
                $notification->push(sprintf(_("The Remote Wipe for device id %s has been cancelled."), $ui->vars->wipe));
            } elseif ($ui->vars->reset) {
                $devices = $state->listDevices($auth);
                foreach ($devices as $device) {
                    $state->removeState(array(
                        'devId' => $device['device_id'],
                        'user' => $auth
                    ));
                }
                $notification->push(_("All state removed for your ActiveSync devices. They will resynchronize next time they connect to the server."));
            } elseif ($ui->vars->removedevice) {
                $state->removeState(array(
                    'devId' => $ui->vars->removedevice,
                    'user' => $auth
                ));
                $notification->push(sprintf(_("The state for device id %s has been reset. It will resynchronize next time it connects to the server."), $ui->vars->removedevice));
            }
        } catch (Horde_ActiveSync_Exception $e) {
            $notification->push(_("There was an error communicating with the ActiveSync server: %s"), $e->getMessage(), 'horde.err');
        }

        $GLOBALS['prefs']->setValue('activesync_identity', Horde_Util::getPost('activesync_identity'));
        return false;
    }

}
