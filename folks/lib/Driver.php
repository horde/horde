<?php
/**
 * Folks_Driver:: defines an API for implementing storage backends for
 * Folks.
 *
 * $Id: Driver.php 1247 2009-01-30 15:01:34Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

class Folks_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Attempts to return a concrete Folks_Driver instance based on $driver.
     *
     * @param string $driver  The type of the concrete Folks_Driver subclas
     *                        to return.  The clas name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclas might need.
     *
     * @return Folks_Driver  The newly created concrete Folks_Driver
     *                          instance, or false on an error.
     */
    public function factory($driver = null, $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        $driver = basename($driver);

        if ($params === null) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $clas = 'Folks_Driver_' . $driver;
        if (!class_exists($clas)) {
            include dirname(__FILE__) . '/Driver/' . $driver . '.php';
        }
        if (class_exists($clas)) {
            return new $clas($params);
        } else {
            return false;
        }
    }

    /**
     * Store image
     *
     * @param string $file   Image file
     * @param string $user   User pricture belongs to
     */
    protected function _saveImage($file, $user)
    {
        global $conf;

        $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images');
        $p = hash('md5', $user);
        $vfspath = Folks::VFS_PATH . '/' . substr(str_pad($p, 2, 0, STR_PAD_LEFT), -2) . '/';
        $vfs_name = $p . '.' . $conf['images']['image_type'];
        $driver = $conf['image']['driver'];
        $context = array('tmpdir' => Horde::getTempDir());
        if (!empty($conf['image']['convert'])) {
            $context['convert'] = $conf['image']['convert'];
            $context['identify'] = $conf['image']['identify'];
        }
        $img = Horde_Image::factory($driver,
                                    array('type' => $conf['images']['image_type'],
                                          'context' => $context));
        try {
            $result = $img->loadFile($file);
        } catch (Horde_Image_Exception $e) {
            throw new Horde_Exception_Prior($e);
        }
        $dimensions = $img->getDimensions();
        if ($dimensions instanceof PEAR_Error) {
            return $dimensions;
        }
        $img->resize(min($conf['images']['screen_width'], $dimensions['width']),
                        min($conf['images']['screen_height'], $dimensions['height']));

        // Store big image
        try {
            $vfs->writeData($vfspath . '/big/', $vfs_name, $img->raw(), true);
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($result->getMessage());
        }

        // Resize thumbnail
        $dimensions = $img->getDimensions();
        $img->resize(min($conf['images']['thumbnail_width'], $dimensions['width']),
                     min($conf['images']['thumbnail_height'], $dimensions['height']));

        // Store thumbnail
        return $vfs->writeData($vfspath . '/small/', $vfs_name, $img->raw(), true);
    }

    /**
     * Delete user image
     *
     * @param string $user   User pricture belongs to
     */
    public function deleteImage($user)
    {
        $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images');
        $p = hash('md5', $user);
        $vfspath = Folks::VFS_PATH . '/' . substr(str_pad($p, 2, 0, STR_PAD_LEFT), -2) . '/';
        $vfs_name = $p . '.' . $GLOBALS['conf']['images']['image_type'];

        try {
            $vfs->deleteFile($vfspath . '/big/', $vfs_name);
            $vfs->deleteFile($vfspath . '/small/', $vfs_name);
        } catch (VFS_Exception $e) {
            return $e->getMessage();
        }

        // Delete cache
        $GLOBALS['cache']->expire('folksProfile' . $user);

        return $this->_deleteImage($user);
    }

    /**
     * Get usersnames online
     *
     * @return array  users online
     */
    public function getOnlineUsers()
    {
        static $online;

        if ($online !== null) {
            return $online;
        }

        $online = $GLOBALS['cache']->get('folksOnlineUsers', $GLOBALS['conf']['online']['ttl']);
        if ($online) {
            $online = unserialize($online);
        } else {
            $online = $this->_getOnlineUsers();
            if ($online instanceof PEAR_Error) {
                return $online;
            }
            $online = array_flip($online);
            $GLOBALS['cache']->set('folksOnlineUsers', serialize($online));
        }

        return $online;
    }

    /**
     * Reset online users cache
     *
     * @return boolean
     */
    public function resetOnlineUsers()
    {
        $this->_updateOnlineStatus();
        return $GLOBALS['cache']->expire('folksOnlineUsers');
    }

    /**
     * Get usersnames online
     *
     * @return array  users online
     */
    public function getRecentVisitors($limit = 10)
    {
        $recent = $GLOBALS['cache']->get('folksRecentVisitors' . $limit, $GLOBALS['conf']['online']['ttl']);
        if ($recent) {
            $recent = unserialize($recent);
        } else {
            $recent = $this->_getRecentVisitors($limit);
            if ($recent instanceof PEAR_Error) {
                return $recent;
            }
            $GLOBALS['cache']->set('folksRecentVisitors' . $limit, serialize($recent));
        }

        return $recent;
    }

    /**
     * Get last signed up users
     *
     * @return array  users online
     */
    public function getNewUsers($limit = 10)
    {
        $new = $GLOBALS['cache']->get('folksNewUsers', $GLOBALS['conf']['cache']['default_lifetime']);
        if ($new) {
            $new = unserialize($new);
        } else {
            $new = $this->getUsers(array('sort_by' => 'signup_at',
                                        'sort_dir' => 0), 0, $limit);
            if ($new instanceof PEAR_Error) {
                return $new;
            }
            $GLOBALS['cache']->set('folksNewUsers', serialize($new));
        }

        return $new;
    }

    /**
     * Get random users
     *
     * @param integer $limit   Username to check
     * @param boolean $online   User is online?
     *
     * @return array  users
     */
    public function getRandomUsers($limit = 10, $online = true)
    {
        $random = $GLOBALS['cache']->get('folksRandomUsers' . $limit . '-' . $online, $GLOBALS['conf']['online']['ttl']);
        if ($random) {
            $random = unserialize($random);
        } else {
            $random = $this->_getRandomUsers($limit, $online);
            if ($random instanceof PEAR_Error) {
                return $random;
            }
            $GLOBALS['cache']->set('folksRandomUsers' . $limit . '-' . $online, serialize($random));
        }

        return $random;
    }

    /**
     * Check if a user is online
     *
     * @param string $user   Username to check
     *
     * @return boolean
     */
    public function isOnline($user)
    {
        $online = $this->getOnlineUsers();

        return array_key_exists($user, $online);
    }

    /**
     * Update user online status
     */
    public function updateOnlineStatus()
    {
        // Update user online status only if needed
        // is not added site wide to a general template file
        // scripts/online.sql
        if ($GLOBALS['conf']['online']['autoupdate'] &&
            (!isset($_SESSION['folks']['last_update']) ||
                $_SESSION['folks']['last_update'] + $GLOBALS['conf']['online']['ttl'] < $_SERVER['REQUEST_TIME'])) {

            // Update online status
            $this->_updateOnlineStatus();

            // Update profile
            if ($GLOBALS['registry']->isAuthenticated()) {
                $this->_saveProfile(array('last_online_on' => $_SERVER['REQUEST_TIME']), $GLOBALS['registry']->getAuth());
            }
        }

        // Delete aways users of needed - if not done by cron job as recomended.
        if ($GLOBALS['conf']['online']['autodelete']) {
            $to = $_SERVER['REQUEST_TIME'] - $GLOBALS['conf']['online']['ttl'];
            $this->_deleteOnlineStatus($to);
        }
    }

    /**
     * Get raw profile of current user
     */
    public function getRawProfile($user)
    {
        return $this->_getProfile($user);
    }

    /**
     * Get user profile
     *
     * @param string $user   Username
     */
    public function getProfile($user = null)
    {
        static $profiles;

        if ($user == null) {
            $user = $GLOBALS['registry']->getAuth();
        }

        if (empty($user)) {
            return PEAR::raiseError(sprintf(_("User \"%s\" does not exists."), $user));
        }

        if (isset($profiles[$user])) {
            return $profiles[$user];
        }

        $profile = $GLOBALS['cache']->get('folksProfile' . $user, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($profile || ($GLOBALS['registry']->isAdmin() && Horde_Util::getGet('debug'))) {

            $profile = unserialize($profile);

        } else {
            // Load profile
            $profile = $this->_getProfile($user);
            if ($profile instanceof PEAR_Error) {
                return $profile;
            }

            // Filter description
            $filters = array('text2html', 'bbcode', 'highlightquotes', 'emoticons');
            $filters_params = array(array('parselevel' => Horde_Text_Filter_Text2html::MICRO),
                                    array(),
                                    array(),
                                    array());

            if (($hasBBcode = strpos($profile['user_description'], '[')) !== false &&
                    strpos($profile['user_description'], '[/', $hasBBcode) !== false) {
                $filters_params[0]['parselevel'] = Horde_Text_Filter_Text2html::NOHTML;
            }

            $profile['user_description'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(trim($profile['user_description']), $filters, $filters_params);

            // Get user last external data
            foreach ($profile as $key => $value) {
                if (substr($key, 0, 6) != 'count_') {
                    continue;
                }
                $service = substr($key, 6);
                if ($GLOBALS['conf']['services']['countcron']) {
                    if (empty($value)) {
                        continue;
                    }
                } else {
                    try {
                        $profile['count_' . $service] = Horde::callHook('countService', array($service, $user), 'folks');
                    } catch (Horde_Exception_HookNotSet $e) {}
                    if (empty($profile['count_' . $service])) {
                        continue;
                    }
                }
                try {
                    $profile['count_' . $service . '_list'] = Horde::callHook('getService', array($service, $user), 'folks');
                } catch (Horde_Exception_HookNotSet $e) {}
                if (empty($profile['count_' . $service . '_list'])) {
                    $profile['count_' . $service] = 0;
                }
            }

            // Cache profile

            // cache profile
            $GLOBALS['cache']->set('folksProfile' . $user, serialize($profile));
        }

        $profiles[$user] = $profile;

        return $profile;
    }

    /**
     * Change user password
     *
     * @param string $password   Plain password
     * @param string $user   Username
     */
    public function changePassword($password, $user = null)
    {
        if ($user == null) {
            $user = $GLOBALS['registry']->getAuth();
        }

        $password = hash('md5', $password);

        return $this->_saveProfile(array('user_password' => $password), $user);
    }

    /**
     * Save user profile
     *
     * @param array $data   Profile data
     * @param string $user   Username
     */
    public function saveProfile($data, $user = null)
    {
        if ($user == null) {
            $user = $GLOBALS['registry']->getAuth();
        }

        $GLOBALS['cache']->expire('folksProfile' . $user);

        return $this->_saveProfile($data, $user);
    }

    /**
     * Logs a user view.
     *
     * @param string $id   Username
     *
     * @return boolean True, if the view was logged, false if the mesage was aleredy seen
     */
    function logView($id)
    {
        if (!$GLOBALS['registry']->isAuthenticated() || Horde_Auth::getAUth() == $id) {
            return false;
        }

        /* We already read this user? */
        if (isset($_COOKIE['folks_viewed_user']) &&
            strpos($_COOKIE['folks_viewed_user'], $id . ':') !== false) {
            return false;
        }

        /* Remember when we see a user */
        if (!isset($_COOKIE['folks_viewed_user'])) {
            $_COOKIE['folks_viewed_user'] = $id . ':';
        } else {
            $_COOKIE['folks_viewed_user'] .= $id . ':';
        }

        setcookie('folks_viewed_user', $_COOKIE['folks_viewed_user'], $_SERVER['REQUEST_TIME'] + 22896000, $GLOBALS['conf']['cookie']['path'],
                  $GLOBALS['conf']['cookie']['domain'],  $GLOBALS['conf']['use_ssl'] == 1 ? 1 : 0);

        return $this->_logView($id);
    }

   /**
    * Delete user
    *
    * @param string $user    Username
    *
    * @return boolean
    */
    public function deleteUser($user)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            return false;
        }

        // Delete image
        $result = $this->deleteImage($user);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        // Delete groups
        if ($GLOBALS['conf']['friends']) {
            $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();
            $groups = $shares->listShares($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW, true);
            foreach ($groups as $share) {
                $result = $shares->removeShare($share);
                if ($result instanceof PEAR_Error) {
                    return $result;
                }
            }
        }

        // Delete comments
        if ($registry->hasMethod('forums/deleteForum')) {
            $registry->call('forums/deleteForum', array('folks', $user));
        }

        // Delete user
        $result = $this->_deleteUser($user);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        // Delete cache
        $GLOBALS['cache']->expire('folksProfile' . $user);

        return true;
    }

    /**
     * Get user attributes
     *
     * @param string $user   Username
     * @param string $group   Only a specific group
     */
    public function getAttributes($user = null, $group = null)
    {
        if ($user == null) {
            $user = $GLOBALS['registry']->getAuth();
        }

        $attributes = $GLOBALS['cache']->get('folksUserAttributes' . $user, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($attributes) {
            $attributes = unserialize($attributes);
        } else {
            $attributes = $this->_getAttributes($user);
            if ($attributes instanceof PEAR_Error) {
                return $attributes;
            }
            $GLOBALS['cache']->set('folksUserAttributes' . $user, serialize($attributes));
        }

        return $group ? (isset($attributes[$group]) ? $attributes[$group] : array()) : $attributes;
    }

    /**
     * Save user attributes
     *
     * @param array $data   Attributes data
     * @param string $user   Username
     * @param string $group   Only a specific group
     */
    public function saveAttributes($data, $group, $user = null)
    {
        if ($user == null) {
            $user = $GLOBALS['registry']->getAuth();
        }

        $GLOBALS['cache']->expire('folksAttributes' . $user);

        return $this->_saveAttributes($data, $group, $user);
    }

    /**
     * Compare an encrypted pasword to a plaintext string to see if
     * they match.
     *
     * @param string $user   Username
     * @param string $plaintext  The plaintext pasword to verify.
     *
     * @return boolean  True if matched, false otherwise.
     */
    public function comparePassword($user, $plaintext)
    {
        $encrypted = $this->_getCryptedPassword($user);
        if ($encrypted instanceof PEAR_Error) {
            return $encrypted;
        } elseif (empty($encrypted)) {
            return false;
        }

        return $encrypted == hash('md5', $plaintext);
    }

    /**
     * Get encripted cookie login string
     *
     * @param string $user   Username to get cookie for
     *
     * @return string  Encripted
     */
    public function getCookie($user)
    {
        $encrypted = $this->_getCryptedPassword($user);
        if ($encrypted instanceof PEAR_Error) {
            return $encrypted;
        }

        // force relogin once a mount and user pass to encript cookie
        $key = date('m') . $encrypted;

        return Folks::encodeString($user, $key);
    }

    /**
     * Get confirmation code
     *
     * @param string $user   Username to get code for
     * @param string $type   Code type
     *
     * @return string  Confirmation code
     */
    public function getConfirmationCode($user, $type = 'activate')
    {
        $encrypted = $this->_getCryptedPassword($user);
        if ($encrypted instanceof PEAR_Error) {
            return $encrypted;
        }

        return Folks::encodeString($user, $type . $encrypted);
    }

   /**
    * Save search criteria
    *
    * @param string $criteria    Search criteria
    * @param string $name    Search name
    */
    public function saveSearch($criteria, $name)
    {
        $GLOBALS['cache']->expire('folksearch' . $GLOBALS['registry']->getAuth());

        return $this->_saveSearch($criteria, $name);
    }

   /**
    * Get saved search
    *
    * @return array saved searches
    */
    public function getSavedSearch()
    {
        $search = $GLOBALS['cache']->get('folksearch' . $GLOBALS['registry']->getAuth(), $GLOBALS['conf']['cache']['default_lifetime']);
        if ($search) {
            return unserialize($search);
        }

        $search = $this->_getSavedSearch();
        if ($search instanceof PEAR_Error) {
            return $search;
        }

        $GLOBALS['cache']->set('folksearch' . $GLOBALS['registry']->getAuth(), serialize($search));

        return $search;
    }

   /**
    * Get saved search criteria
    *
    * @param string $name    Username
    *
    * @return array  search criteria
    */
    public function getSearchCriteria($name)
    {
        $criteria = $this->_getSearchCriteria($name);
        if ($criteria instanceof PEAR_Error) {
            return $criteria;
        }

        return unserialize($criteria);
    }

   /**
    * Delete saved search
    *
    * @param string $name    Username
    */
    public function deleteSavedSearch($name)
    {
        $GLOBALS['cache']->expire('folksearch' . $GLOBALS['registry']->getAuth());

        return $this->_deleteSavedSearch($name);
    }

   /**
    * Log users activity
    *
    * @param string $message    Activity message
    * @param string $scope    Scope
    * @param string $user    $user
    *
    * @return true on success
    */
    public function logActivity($message, $scope = 'folks', $user = null)
    {
        if ($user == null) {
            $user = $GLOBALS['registry']->getAuth();
        }

        if (empty($message)) {
            return PEAR::raiseError(_("You cannot post an empty activity message."));
        }

        // Don't log user comments
        if ($scope == 'folks:comments' && !$GLOBALS['prefs']->getValue('log_user_comments')) {
            return true;

        // Don't log account changes
        } elseif ($scope == 'folks' && !$GLOBALS['prefs']->getValue('log_account_changes')) {
            return true;
        }

        // Don't log conetnt posting
        $scopes = unserialize($GLOBALS['prefs']->getValue('log_scopes'));
        if (!empty($scopes) && in_array($scopes, $scope)) {
            return true;
        }

        // Don't log comments
        $scope_app = explode(':', $scope);
        $scopes = unserialize($GLOBALS['prefs']->getValue('log_scope_comments'));
        if (!empty($scopes) && in_array($scopes, $scope_app[0])) {
            return true;
        }

        $GLOBALS['cache']->expire($user . '_activity');
        return $this->_logActivity($message, $scope, $user);
    }

   /**
    * Get user's activity
    *
    * @param string $user    Username
    * @param int $limit    Number of actions to return
    *
    * @return array    Activity log
    */
    public function getActivity($user, $limit = 10)
    {
        $activity = $GLOBALS['cache']->get($user . '_activity', $GLOBALS['conf']['cache']['default_lifetime']);
        if ($activity) {
            return unserialize($activity);
        } else {
            $activity = $this->_getActivity($user, $limit);
            if ($activity instanceof PEAR_Error) {
                return $activity;
            }

            $GLOBALS['cache']->set($user . '_activity', serialize($activity));
        }

        return $activity;
    }

   /**
    * Delete users activity
    *
    * @param string $scope    Scope
    * @param integer $date    Date
    *
    * @return true on success
    */
    public function deleteActivity($scope, $date)
    {
        $user = $GLOBALS['registry']->getAuth();
        $GLOBALS['cache']->expire($user . '_activity');
        return $this->_deleteActivity($scope, $date, $user);
    }
}
