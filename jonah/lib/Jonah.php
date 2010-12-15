<?php
/**
 * Jonah Base Class.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Eric Rechlin <eric@hpcalc.org>
 *
 * @package Jonah
 */
class Jonah
{
    /**
     * Internal Jonah channel.
     */
    const INTERNAL_CHANNEL = 0;

    /**
     * External channel.
     */
    const EXTERNAL_CHANNEL = 1;

    /**
     * Aggregated channel.
     */
    const AGGREGATED_CHANNEL = 2;

    /**
     * Composite channel.
     */
    const COMPOSITE_CHANNEL = 3;

    /**
     */
    const ORDER_PUBLISHED = 0;
    const ORDER_READ = 1;
    const ORDER_COMMENTS = 2;

    /**
     * Obtain the list of stories from the passed in URI.
     *
     * @deprecated Will be removed when external channels are removed.
     *
     * @param string $url  The url to get the list of the channel's stories.
     */
    static public function readURL($url)
    {
        global $conf;

        $http = $GLOBALS['injector']
          ->getInstance('Horde_Core_Factory_HttpClient')
          ->create();

        try {
            $response = $http->get($url);
        } catch (Horde_Http_Exception $e) {
            throw new Jonah_Exception(sprintf(_("Could not open %s: %s"), $url, $e->getMessage()));
        }
        if ($response->code <> '200') {
            throw new Jonah_Exception(sprintf(_("Could not open %s: %s"), $url, $response->code));
        }
        $result = array('body' => $response->getBody());
        $content_type = $response->getHeader('Content-Type');
        if (preg_match('/.*;\s?charset="?([^"]*)/', $content_type, $match)) {
            $result['charset'] = $match[1];
        } elseif (preg_match('/<\?xml[^>]+encoding=["\']?([^"\'\s?]+)[^?].*?>/i', $result['body'], $match)) {
            $result['charset'] = $match[1];
        }

        return $result;
    }

    /**
     * @deprecated Remove when external channels moved to hippo.
     */
    static public function getChannelTypeLabel($type)
    {
        switch ($type) {
        case Jonah::INTERNAL_CHANNEL:
            return _("Local Feed");
        }
    }

    /**
     *
     *
     * @param string $filter       The type of channel
     * @param integer $permission  Horde_Perms:: constant
     * @param mixed $in            ??
     *
     * @return mixed  An array of results or a single boolean?
     */
    static public function checkPermissions($filter, $permission = Horde_Perms::READ, $in = null)
    {
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'jonah:admin', 'permlevel' =>  $permission))) {
            if (empty($in)) {
                // Calls with no $in parameter are checking whether this user
                // has permission.  Since this user is an admin, they always
                // have permission.  If the $in parameter is an empty array,
                // the method is expected to return an array too.
                return is_array($in) ? array() : true;
            } else {
                return $in;
            }
        }

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        $out = array();

        switch ($filter) {
        case 'internal_channels':
            if (empty($in) || !$perms->exists('jonah:news:' . $filter . ':' . $in)) {
                return $perms->hasPermission('jonah:news:' . $filter, $GLOBALS['registry']->getAuth(), $permission);
            } elseif (!is_array($in)) {
                return $perms->hasPermission('jonah:news:' . $filter . ':' . $in, $GLOBALS['registry']->getAuth(), $permission);
            } else {
                foreach ($in as $key => $val) {
                    if ($perms->hasPermission('jonah:news:' . $filter . ':' . $val, $GLOBALS['registry']->getAuth(), $permission)) {
                        $out[$key] = $val;
                    }
                }
            }
            break;

        case 'channels':
            foreach ($in as $key => $val) {
                $perm_name = Jonah::typeToPermName($val['channel_type']);
                if ($perms->hasPermission('jonah:news:' . $perm_name,  $GLOBALS['registry']->getAuth(), $permission) ||
                    $perms->hasPermission('jonah:news:' . $perm_name . ':' . $val['channel_id'], $GLOBALS['registry']->getAuth(), $permission)) {
                    $out[$key] = $in[$key];
                }
            }
            break;

        default:
            return $perms->hasPermission($filter, $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
        }

        return $out;
    }

    /**
     * @deprecated Remove when external channels removed.
     *
     * @param string $type  The Jonah::* constant for the channel type.
     *
     * @return string  The string representation of the channel type.
     */
    static public function typeToPermName($type)
    {
        if ($type == Jonah::INTERNAL_CHANNEL) {
            return 'internal_channels';
        }
    }

    /**
     * Returns an array of configured body types from Jonah's $conf array.
     *
     * @return array  An array of body types.
     */
    static public function getBodyTypes()
    {
        static $types = array();
        if (!empty($types)) {
            return $types;
        }

        if (in_array('richtext', $GLOBALS['conf']['news']['story_types'])) {
            $types['richtext'] = _("Rich Text");
        }

        /* Other than checking if text is enabled, it is inserted by default if
         * no other body type has been enabled in the config. */
        if (in_array('text', $GLOBALS['conf']['news']['story_types']) ||
            empty($types)) {
            $types['text'] = _("Text");
        }

        return $types;
    }

    /**
     * Tries to figure out a default body type. Used when none has been
     * specified and a types is needed to fall back on to.
     *
     * @return string  A default type.
     */
    static public function getDefaultBodyType()
    {
        $types = Jonah::getBodyTypes();
        if (isset($types['text'])) {
            return 'text';
        } elseif (isset($types['richtext'])) {
            return 'richtext';
        }
        /* The two most common body types have not been found, so just return
         * the first one that is in the array. */
        return array_shift(array_keys($types));
    }

    /**
     * Returns the available channel types based on what was set in the
     * configuration.
     *
     * @return array  The available news channel types.
     */
    static public function getAvailableTypes()
    {
        $types = array();

        if (empty($GLOBALS['conf']['news']['enable'])) {
            return $types;
        }
        if (in_array('internal', $GLOBALS['conf']['news']['enable'])) {
            $types[Jonah::INTERNAL_CHANNEL] = _("Local Feed");
        }
        if (in_array('composite', $GLOBALS['conf']['news']['enable'])) {
            $types[Jonah::COMPOSITE_CHANNEL] = _("Composite Feed");
        }

        return $types;
    }

    public static function listFeeds($owneronly = false, $permission = Horde_Perms::SHOW)
    {
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }
        try {
            $feeds = $GLOBALS['jonah_shares']->listShares(
                $GLOBALS['registry']->getAuth(),
                array('perm'=>$permission, 
                'attributes'=> $owneronly ? $GLOBALS['registry']->getAuth() : null,
                'sort_by' => 'name'));
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            return array();
        }

        return $feeds;
    }

    public static function getFeed($feedId)
    {
        try {
            $feed = $GLOBALS['jonah_shares']->getShareById($feedId);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            return array();
        }
        return $feed;
    }

    public static function addFeed($info)
    {
        try {
            $feed = $GLOBALS['jonah_shares']->newShare($GLOBALS['registry']->getAuth(), $info['slug']);
            $feed->set('name', $info['name']);
            $feed->set('desc', $info['description']);
            $feed->set('full_feed', (int)$info['full_feed']);
            $feed->set('interval', (int)$info['interval']);
            $feed->set('url', $info['url']);
            $feed->set('link', $info['link']);
            $feed->set('page_link', $info['page_link']);
            $feed->set('story_url', $info['story_url']);
            $feed->set('img', $info['img']);
            $feed->set('updated', (int)$info['updated']);

            $GLOBALS['jonah_shares']->addShare($feed);
        } catch (Horde_Share_Exception $e) {
            throw new Jonah_Exception($e);
        }
        return $feed;
    }

    public static function updateFeed($feed, $info) {
        if (!$GLOBALS['registry']->getAuth() ||
            ($feed->get('owner') != $GLOBALS['registry'] &&
            (!is_null($feed->get('owner')) || !$GLOBALS['registry']->isAdmin()))) {
            throw new Horde_Exception_PermissionDenied(_("You are not allowed to modify the feed."));
        }

        $feed->set('name', $info['name']);
        $feed->set('desc', $info['description']);
        $feed->set('full_feed', $info['full_feed']);
        $feed->set('interval', $info['interval']);
        $feed->set('url', $info['url']);
        $feed->set('link', $info['link']);
        $feed->set('page_link', $info['page_link']);
        $feed->set('story_url', $info['story_url']);
        $feed->set('img', $info['img']);
        $feed->set('updated', $info['updated']);

        try {
            $feed->save();
        } catch (Horde_Share_Exception $e) {
            throw new Jonah_Exception(_("Unable to save feed \"%s\": %s"), $info['name'], $e->getMessage());
        }
    }

    /**
     * Deletes a feed.
     *
     * @param Horde_Share $feed  The feed to delete.
     */
    public static function deleteFeed($feed)
    {
        if (!$GLOBALS['registry']->getAuth() ||
            ($feed->get('owner') != $GLOBALS['registry']->getAuth() &&
             (!is_null($feed->get('owner')) || !$GLOBALS['registry']->isAdmin()))) {
            throw new Horde_Exception_PermissionDenied(_("You are not allowed to delete this feed."));
        }

        // Delete the feed.
        $storage = &Jonah_Driver::singleton($feed->getName());
        $result = $storage->deleteAll();
        if ($result instanceof PEAR_Error) {
            throw new Jonah_Exception(sprintf(_("Unable to delete \"%s\": %s"), $feed->get('name'), $result->getMessage()));
        }

        // Remove share and all groups/permissions.
        try {
            return $GLOBALS['jonah_shares']->removeShare($feed);
        } catch (Horde_Share_Exception $e) {
            throw new Jonah_Exception($e);
        }
    }

}
