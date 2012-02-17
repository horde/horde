<?php
/**
 * Horde-specific prefs handling.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde
 */
class Horde_Prefs_Ui
{
    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $conf, $injector;

        /* Hide prefGroups. */
        try {
            $injector->getInstance('Horde_Core_Factory_Auth')->create()->hasCapability('update');
        } catch (Horde_Exception $e) {
            $ui->suppressGroups[] = 'forgotpass';
        }

        if (empty($conf['facebook']['enabled']) ||
            empty($conf['facebook']['id']) ||
            empty($conf['facebook']['secret'])) {
            $ui->suppressGroups[] = 'facebook';
        }

        if (empty($conf['twitter']['enabled']) ||
            empty($conf['twitter']['key']) ||
            empty($conf['twitter']['secret'])) {
            $ui->suppressGroups[] = 'twitter';
        }

        if (empty($conf['imsp']['enabled'])) {
            $ui->suppressGroups[] = 'imspauth';
        }

        if (empty($conf['activesync']['enabled'])) {
            $ui->suppressGroups[] = 'activesync';
        }
    }

    /**
     * Determine active prefs when displaying a group.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsGroup($ui)
    {
        global $injector, $registry;

        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'initial_application':
                $out = array();
                $apps = $registry->listApps(array('active'));
                foreach ($apps as $a) {
                    $perms = $injector->getInstance('Horde_Perms');
                    if (file_exists($registry->get('fileroot', $a)) &&
                        (($perms->exists($a) && ($perms->hasPermission($a, $registry->getAuth(), Horde_Perms::READ) || $registry->isAdmin())) ||
                         !$perms->exists($a))) {
                        $out[$a] = $registry->get('name', $a);
                    }
                }
                asort($out);
                $ui->override['initial_application'] = $out;
                break;

            case 'language':
                $ui->override['language'] = $registry->nlsconfig->languages;
                array_unshift($ui->override['language'], _("Default"));
                break;

            case 'remotemanagement':
                Horde::addScriptFile('rpcprefs.js', 'horde');
                $ui->nobuttons = true;
                break;

            case 'theme':
                $ui->override['theme'] = Horde_Themes::themeList();
                break;

            case 'timezone':
                $ui->override['timezone'] = Horde_Nls::getTimezones();
                array_unshift($ui->override['timezone'], _("Default"));
                break;
            }
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the options page.
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'categorymanagement':
            return $this->_categoryManagement($ui);

        case 'remotemanagement':
            return $this->_remoteManagement($ui);

        case 'syncmlmanagement':
            return $this->_syncmlManagement($ui);

        case 'activesyncmanagement':
            return $this->_activesyncManagement($ui);

        case 'facebookmanagement':
            return $this->_facebookManagement($ui);

        case 'twittermanagement':
            return $this->_twitterManagement($ui);
        }

        return '';
    }

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        switch ($item) {
        case 'categorymanagement':
            return $this->_updateCategoryManagement($ui);

        case 'remotemanagement':
            $this->_updateRemoteManagement($ui);
            break;

        case 'syncmlmanagement':
            $this->_updateSyncmlManagement($ui);
            break;

        case 'activesyncmanagement':
            $this->_updateActiveSyncManagement($ui);
            break;

        case 'facebookmanagement':
            $this->_updateFacebookManagement($ui);
            break;

        case 'twittermanagement':
            $this->_updateTwitterManagement($ui);
        }

        return false;
    }

    /**
     * Called when preferences are changed.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsCallback($ui)
    {
        global $prefs, $registry;

        if ($prefs->isDirty('language')) {
            $registry->setLanguageEnvironment($prefs->getValue('language'));
            foreach ($registry->listApps() as $app) {
                if ($registry->isAuthenticated(array('app' => $app, 'notransparent' => true))) {
                    $registry->callAppMethod($app, 'changeLanguage');
                }
            }
        }
    }

    /**
     * Create code for category management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _categoryManagement($ui)
    {
        Horde::addScriptFile('categoryprefs.js', 'horde');
        Horde::addScriptFile('colorpicker.js', 'horde');
        Horde::addInlineJsVars(array(
            'HordeCategoryPrefs.category_text' => _("Enter a name for the new category:")
        ));

        $cManager = new Horde_Prefs_CategoryManager();
        $categories = $cManager->get();
        $colors = $cManager->colors();
        $fgcolors = $cManager->fgColors();

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if (!$GLOBALS['prefs']->isLocked('category_colors')) {
            $t->set('picker_img',  Horde::img('colorpicker.png', _("Color Picker")));
        }
        $t->set('delete_img',  Horde::img('delete.png'));

        // Default Color
        $color = isset($colors['_default_'])
            ? htmlspecialchars($colors['_default_'])
            : '#FFFFFF';
        $fgcolor = isset($fgcolors['_default_'])
            ? htmlspecialchars($fgcolors['_default_'])
            : '#000000';
        $color_b = 'color_' . hash('md5', '_default_');

        $t->set('default_color', $color);
        $t->set('default_fgcolor', $fgcolor);
        $t->set('default_label', Horde::label($color_b, _("Default Color")));
        $t->set('default_id', $color_b);

        // Unfiled Color
        $color = isset($colors['_unfiled_'])
            ? htmlspecialchars($colors['_unfiled_'])
            : '#FFFFFF';
        $fgcolor = isset($fgcolors['_unfiled_'])
            ? htmlspecialchars($fgcolors['_unfiled_'])
            : '#000000';
        $color_b = 'color_' . hash('md5', '_unfiled_');

        $t->set('unfiled_color', $color);
        $t->set('unfiled_fgcolor', $fgcolor);
        $t->set('unfiled_label', Horde::label($color_b, _("Unfiled")));
        $t->set('unfiled_id', $color_b);

        $entries = array();
        foreach ($categories as $name) {
            $color = isset($colors[$name])
                ? htmlspecialchars($colors[$name])
                : '#FFFFFF';
            $fgcolor = isset($fgcolors[$name])
                ? htmlspecialchars($fgcolors[$name])
                : '#000000';
            $color_b = 'color_' . hash('md5', $name);

            $entries[] = array(
                'color' => $color,
                'fgcolor' => $fgcolor,
                'label' => Horde::label($color_b, ($name == '_default_' ? _("Default Color") : htmlspecialchars($name))),
                'id' => $color_b,
                'name' => htmlspecialchars($name)
            );
        }
        $t->set('categories', $entries);

        return $t->fetch(HORDE_TEMPLATES . '/prefs/category.html');
    }

    /**
     * Create code for remote server management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _remoteManagement($ui)
    {
        $rpc_servers = @unserialize($GLOBALS['prefs']->getValue('remote_summaries'));
        if (!is_array($rpc_servers)) {
            $rpc_servers = array();
        }

        $js = $serverlist = array();
        foreach ($rpc_servers as $key => $val) {
            $js[] = array($val['url'], $val['user']);
            $serverlist[] = array(
                'i' => $key,
                'l' => htmlspecialchars($val['url'])
            );
        }

        Horde::addInlineJsVars(array(
            'HordeRpcPrefs.servers' => $js
        ));

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('serverlabel', Horde::label('server', _("Your remote servers:")));
        $t->set('serverlist', $serverlist);
        $t->set('urllabel', Horde::label('url', _("Remote URL (http://www.example.com/horde):")));
        $t->set('userlabel', Horde::label('user', _("Username:")));
        $t->set('passwdlabel', Horde::label('passwd', _("Password:")));

        return $t->fetch(HORDE_TEMPLATES . '/prefs/rpc.html');
    }

    /**
     * Create code for SyncML management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  HTML UI code.
     */
    protected function _syncmlManagement($ui)
    {
        Horde::addScriptFile('syncmlprefs.js', 'horde');
        $devices = Horde_SyncMl_Backend::factory('Horde')->getUserAnchors($GLOBALS['registry']->getAuth());

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $partners = array();
        $selfurl = $ui->selfUrl()->add('deleteanchor', 1);
        $format = $GLOBALS['prefs']->getValue('date_format') . ' %H:%M';

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
     * Create code for ActiveSync management.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string HTML UI code.
     */
    protected function _activesyncManagement($ui)
    {
        if (empty($GLOBALS['conf']['activesync']['enabled'])) {
            return _("ActiveSync not activated.");
        }

        $stateMachine = $GLOBALS['injector']->getInstance('Horde_ActiveSyncState');
        $devices = $stateMachine->listDevices($GLOBALS['registry']->getAuth());

        $js = array();
        foreach ($devices as $key => $val) {
            $js[$key] = array(
                'id' => $val['device_id'],
                'user' => $val['device_user']
            );
        }

        Horde::addScriptFile('activesyncprefs.js', 'horde');
        Horde::addInlineJsVars(array(
            'HordeActiveSyncPrefs.devices' => $js
        ));

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $selfurl = $ui->selfUrl();
        $t->set('reset', $selfurl->copy()->add('reset', 1));
        $devs = array();

        foreach ($devices as $key => $device) {
            $device['class'] = fmod($key, 2) ? 'rowOdd' : 'rowEven';
            $device['key'] = $key;

            $stateMachine->loadDeviceInfo($device['device_id'], $GLOBALS['registry']->getAuth());
            $ts = $stateMachine->getLastSyncTimestamp();
            $device['ts'] = empty($ts) ? _("None") : strftime($GLOBALS['prefs']->getValue('date_format') . ' %H:%M', $ts);

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
     * Facebook session management
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return string  The HTML code to display the Facebook prefs.
     */
    protected function _facebookManagement($ui)
    {
        global $prefs;

        try {
            $facebook = $GLOBALS['injector']->getInstance('Horde_Service_Facebook');
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        $GLOBALS['injector']->getInstance('Horde_Themes_Css')->addThemeStylesheet('facebook.css');
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);
        $t->set('app_name', $GLOBALS['registry']->get('name', 'horde'));

        // Ensure we have authorized horde.
        try {
            // @TODO: FB is in the process of adding this to the Graph API.
            $session_uid = $facebook->auth->getLoggedInUser();
            $fbp = unserialize($prefs->getValue('facebook'));
            $uid = $fbp['uid'];
            // Verify the userid matches the one we expect for the session
            if ($fbp['uid'] != $session_uid) {
                $haveSession = false;
            } else {
                $haveSession = true;
            }
        } catch (Horde_Service_Facebook_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            $haveSession = false;
            $prefs->setValue('facebook', serialize(array('uid' => '', 'sid' => 0)));
        }

        // We have a session, build the template.
        if (!empty($haveSession)) {
            try {
                $facebook->batchBegin();
                $offline = &$facebook->users->hasAppPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_OFFLINE, $uid);
                $publish = &$facebook->users->hasAppPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM, $uid);
                $read = &$facebook->users->hasAppPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_READSTREAM, $uid);
                $friends = &$facebook->users->hasAppPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_ABOUT, $uid);
                $facebook->batchEnd();

                $t->set('have_offline', $offline);
                $t->set('have_publish', $publish);
                $t->set('have_read', $read);
                $t->set('have_friends', $friends);

            } catch (Horde_Service_Facebook_Exception $e) {
                $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
            }

            // Get user info. FB recommends using the FB photo and styling.
            $fql = 'SELECT first_name, last_name, status, pic_with_logo, current_location FROM user WHERE uid IN (' . $uid . ')';
            try {
                $user_info = $facebook->fql->run($fql);
            } catch (Horde_Service_Facebook_Exception $e) {
                $GLOBALS['notification']->push(_("Temporarily unable to connect with Facebook, Please try again."), 'horde.alert');
            }

            // FB Perms links
            $cburl = Horde::url('services/facebook', true);
            $url = $facebook->auth->getOAuthUrl($cburl, array(Horde_Service_Facebook_Auth::EXTEND_PERMS_OFFLINE));
            $t->set('authUrl', Horde::signQueryString($url));
            $t->set('have_session', true);
            $t->set('user_pic_url', $user_info[0]['pic_with_logo']);
            $t->set('user_name', $user_info[0]['first_name'] . ' ' . $user_info[0]['last_name']);

            $url = $facebook->auth->getOAuthUrl($cburl, array(Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM));
            $t->set('publish_url', $url);

            // User read perms
            $url = $facebook->auth->getOAuthUrl($cburl, array(
                Horde_Service_Facebook_Auth::EXTEND_PERMS_READSTREAM,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_ABOUT,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_BIRTHDAY,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_EVENTS,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_HOMETOWN,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_LOCATION,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_PHOTOS));
            $t->set('read_url', Horde::signQueryString($url));

            // Friend read perms
            $url = $facebook->auth->getOAuthUrl($cburl, array(
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_ABOUT,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_BIRTHDAY,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_HOMETOWN,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_LOCATION,
                Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_PHOTOS));
            $t->set('friends_url', Horde::signQueryString($url));

            return $t->fetch(HORDE_TEMPLATES . '/prefs/facebook.html');
        }

        /* No existing session */
        $t->set('have_session', false);
        $t->set('authUrl', $facebook->auth->getOAuthUrl(Horde::url('services/facebook', true)));

        return $t->fetch(HORDE_TEMPLATES . '/prefs/facebook.html');
    }

    protected function _twitterManagement($ui)
    {
        global $prefs, $registry;

        $twitter = $GLOBALS['injector']->getInstance('Horde_Service_Twitter');
        $token = unserialize($prefs->getValue('twitter'));

        /* Check for an existing token */
        if (!empty($token['key']) && !empty($token['secret'])) {
            $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
            $twitter->auth->setToken($auth_token);
        }
        try {
            $profile = Horde_Serialize::unserialize($twitter->account->verifyCredentials(), Horde_Serialize::JSON);
        } catch (Horde_Service_Twitter_Exception $e) {}

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        /* Could not find a valid auth token, and we are not in the process of getting one */
        if (empty($profile)) {
            try {
                $results = $twitter->auth->getRequestToken();
            } catch (Horde_Service_Twitter_Exception $e) {
                $t->set('error', sprintf(_("Error connecting to Twitter: %s Details have been logged for the administrator."), $e->getMessage()), true);
                exit;
            }
            $GLOBALS['session']->store($results->secret, false, 'twitter_request_secret');
            $t->set('appname', $registry->get('name'));
            $t->set('link', Horde::link(Horde::externalUrl($twitter->auth->getUserAuthorizationUrl($results), false), '', 'button', '', 'openTwitterWindow(); return false;') . 'Twitter</a>');
            $t->set('popupjs', Horde::popupJs(Horde::externalUrl($twitter->auth->getUserAuthorizationUrl($results), false), array('urlencode' => true)));
        } else {
            $t->set('haveSession', true, true);
            $t->set('profile_image_url', $profile->profile_image_url);
            $t->set('profile_screenname', htmlspecialchars($profile->screen_name));
            $t->set('profile_name', htmlspecialchars($profile->name));
            $t->set('profile_location', htmlspecialchars($profile->location));
            $t->set('appname', $registry->get('name'));
        }

        return $t->fetch(HORDE_TEMPLATES . '/prefs/twitter.html');
    }

    /**
     * Update category related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _updateCategoryManagement($ui)
    {
        $cManager = new Horde_Prefs_CategoryManager();

        /* Always save colors of all categories. */
        $colors = array();
        $categories = $cManager->get();
        foreach ($categories as $category) {
            if ($color = $ui->vars->get('color_' . hash('md5', $category))) {
                $colors[$category] = $color;
            }
        }
        if ($color = $ui->vars->get('color_' . hash('md5', '_default_'))) {
            $colors['_default_'] = $color;
        }
        if ($color = $ui->vars->get('color_' . hash('md5', '_unfiled_'))) {
            $colors['_unfiled_'] = $color;
        }
        $cManager->setColors($colors);

        switch ($ui->vars->cAction) {
        case 'add':
            $cManager->add($ui->vars->category);
            break;

        case 'remove':
            $cManager->remove($ui->vars->category);
            break;

        default:
            /* Save button. */
            Horde::addInlineScript(array(
                'if (window.opener && window.name) window.close();'
            ));
            return true;
        }

        return false;
    }

    /**
     * Update remote servers related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateRemoteManagement($ui)
    {
        global $notification, $prefs;

        $rpc_servers = @unserialize($prefs->getValue('remote_summaries'));
        if (!is_array($rpc_servers)) {
            $rpc_servers = array();
        }

        if ($ui->vars->rpc_change || $ui->vars->rpc_create) {
            $tmp = array(
                'passwd' => $ui->vars->passwd,
                'url' => $ui->vars->url,
                'user' => $ui->vars->user
            );

            if ($ui->vars->rpc_change) {
                $rpc_servers[$ui->vars->server] = $tmp;
            } else {
                $rpc_servers[] = $tmp;
            }

            $prefs->setValue('remote_summaries', serialize($rpc_servers));
            $notification->push(sprintf(_("The server \"%s\" has been saved."), $ui->vars->url), 'horde.success');
        } elseif ($ui->vars->rpc_delete) {
            if ($ui->vars->server == -1) {
                $notification->push(_("You must select an server to be deleted."), 'horde.warning');
            } else {
                $notification->push(sprintf(_("The server \"%s\" has been deleted."), $rpc_servers[$ui->vars->server]['url']), 'horde.success');

                $deleted_server = $rpc_servers[$ui->vars->server]['url'];
                unset($rpc_servers[$ui->vars->server]);
                $prefs->setValue('remote_summaries', serialize(array_values($rpc_servers)));

                $chosenColumns = explode(';', $prefs->getValue('show_summaries'));
                if ($chosenColumns != array('')) {
                    $newColumns = array();
                    foreach ($chosenColumns as $chosenColumn) {
                        $chosenColumn = explode(',', $chosenColumn);
                        $remote = explode('|', $chosenColumn[0]);
                        if (count($remote) != 3 || $remote[2] == $deleted_server) {
                            $newColumns[] = implode(',', $chosenColumn);
                        }
                    }
                    $prefs->setValue('show_summaries', implode(';', $newColumns));
                }
            }
        }
    }

    /**
     * Update SyncML related preferences.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateSyncmlManagement($ui)
    {
        $backend = Horde_SyncMl_Backend::factory('Horde');

        if ($ui->vars->removedb && $ui->vars->removedevice) {
            try {
                $backend->removeAnchor($GLOBALS['registry']->getAuth(), $ui->vars->removedevice, $ui->vars->removedb);
                $backend->removeMaps($GLOBALS['registry']->getAuth(), $ui->vars->removedevice, $ui->vars->removedb);
                $GLOBALS['notification']->push(sprintf(_("Deleted synchronization session for device \"%s\" and database \"%s\"."), $ui->vars->deviceid, $ui->vars->db), 'horde.success');
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push(_("Error deleting synchronization session:") . ' ' . $e->getMessage(), 'horde.error');
            }
        } elseif ($ui->vars->deleteall) {
            try {
                $backend->removeAnchor($GLOBALS['registry']->getAuth());
                $backend->removeMaps($GLOBALS['registry']->getAuth());
                $GLOBALS['notification']->push(_("All synchronization sessions deleted."), 'horde.success');
            } catch (Horde_Exception $e) {
                $GLOBALS['notification']->push(_("Error deleting synchronization sessions:") . ' ' . $e->getMessage(), 'horde.error');
            }
        }
    }

    /**
     * Update ActiveSync actions
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateActiveSyncManagement($ui)
    {
        $stateMachine = $GLOBALS['injector']->getInstance('Horde_ActiveSyncState');
        $stateMachine->setLogger($GLOBALS['injector']->getInstance('Horde_Log_Logger'));
        try {
            if ($ui->vars->wipeid) {
                $stateMachine->loadDeviceInfo($ui->vars->wipeid, $GLOBALS['registry']->getAuth());
                $stateMachine->setDeviceRWStatus($ui->vars->wipeid, Horde_ActiveSync::RWSTATUS_PENDING);
                $GLOBALS['notification']->push(sprintf(_("A remote wipe for device id %s has been initiated. The device will be wiped during the next synchronisation."), $ui->vars->wipe));
            } elseif ($ui->vars->cancelwipe) {
                $stateMachine->loadDeviceInfo($ui->vars->cancelwipe, $GLOBALS['registry']->getAuth());
                $stateMachine->setDeviceRWStatus($ui->vars->cancelwipe, Horde_ActiveSync::RWSTATUS_OK);
                $GLOBALS['notification']->push(sprintf(_("The Remote Wipe for device id %s has been cancelled."), $ui->vars->wipe));
            } elseif ($ui->vars->reset) {
                $devices = $stateMachine->listDevices($GLOBALS['registry']->getAuth());
                foreach ($devices as $device) {
                    $stateMachine->removeState(null, $device['device_id'], $GLOBALS['registry']->getAuth());
                }
                $GLOBALS['notification']->push(_("All state removed for your ActiveSync devices. They will resynchronize next time they connect to the server."));
            } elseif ($ui->vars->removedevice) {
                $stateMachine->removeState(null, $ui->vars->removedevice, $GLOBALS['registry']->getAuth());
                $GLOBALS['notification']->push(sprintf(_("The state for device id %s has been reset. It will resynchronize next time it connects to the server."), $ui->vars->removedevice));
            }
        } catch (Horde_ActiveSync_Exception $e) {
            $GLOBALS['notification']->push(_("There was an error communicating with the ActiveSync server: %s"), $e->getMessage(), 'horde.err');
        }
    }

    /**
     * Update facebook related prefs
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    protected function _updateFacebookManagement($ui)
    {
        global $prefs;

        try {
            $facebook = $GLOBALS['injector']->getInstance('Horde_Service_Facebook');
        } catch (Horde_Exception $e) {
            return _($e->getMessage());
        }
        try {
            switch ($ui->vars->fbactionID) {
            case 'revokeInfinite':
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_OFFLINE);
                break;
            case 'revokeApplication':
                $facebook->auth->revokeAuthorization();
                $prefs->setValue('facebook', array('uid' => '',
                                                   'sid' => ''));
                break;
            case 'revokePublish':
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_PUBLISHSTREAM);
                break;
            case 'revokeRead':
                $facebook->batchBegin();
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_READSTREAM);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_ABOUT);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_HOMETOWN);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_LOCATION);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_PHOTOS);
                $facebook->batchEnd();
                $facebook->batchBegin();
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_BIRTHDAY);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_USER_EVENTS);
                $facebook->batchEnd();
                break;
            case 'revokeFriends':
                $facebook->batchBegin();
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_ABOUT);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_BIRTHDAY);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_HOMETOWN);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_LOCATION);
                $facebook->auth->revokeExtendedPermission(Horde_Service_Facebook_Auth::EXTEND_PERMS_FRIENDS_PHOTOS);
                $facebook->batchEnd();
                break;
            }
        } catch (Horde_Service_Facebook_Exception $e) {
            $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
        }
    }

    protected function _updateTwitterManagement($ui)
    {
        global $prefs, $registry;

        $twitter = $GLOBALS['injector']->getInstance('Horde_Service_Twitter');
        $token = unserialize($prefs->getValue('twitter'));

        /* Check for an existing token */
        if (!empty($token['key']) && !empty($token['secret'])) {
            $auth_token = new Horde_Oauth_Token($token['key'], $token['secret']);
            $twitter->auth->setToken($auth_token);
        }

        switch ($ui->vars->twitteractionID) {
        case 'revokeInfinite':
            $twitter->account->endSession();
            $prefs->setValue('twitter', 'a:0:{}');
            echo '<script type="text/javascript">location.href="' . Horde::url('services/prefs.php', true)->add(array('group' => 'twitter', 'app'  => 'horde')) . '";</script>';
            exit;
        }
    }
}
