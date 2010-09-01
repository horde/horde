<?php
/**
 * Jonah Base Class.
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
     * @param string $url  The url to get the list of the channel's stories.
     */
    static public function readURL($url)
    {
        global $conf;

        $http = $GLOBALS['injector']
          ->getInstance('Horde_Http_Client')
          ->getClient();

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
     * Returns a drop-down select box to choose which view to display.
     *
     * @param $name      Name to assign to select box.
     * @param $selected  Currently selected item. (optional)
     * @param $onchange  JavaScript onchange code. (optional)
     *
     * @return string Generated select box code
     */
    static public function buildViewWidget($name, $selected = 'standard', $onchange = '')
    {
        require JONAH_BASE . '/config/templates.php';

        if ($onchange) {
            $onchange = ' onchange="' . $onchange . '"';
        }

        $html = '<select name="' . $name . '"' . $onchange . '>' . "\n";
        foreach ($templates as $key => $tinfo) {
            $select = ($selected == $key) ? ' selected="selected"' : '';
            $html .= '<option value="' . $key . '"' . $select . '>' . $tinfo['name'] . "</option>\n";
        }
        return $html . '</select>';
    }

    /**
     */
    static public function getChannelTypeLabel($type)
    {
        switch ($type) {
        case Jonah::INTERNAL_CHANNEL:
            return _("Local Feed");

        case Jonah::EXTERNAL_CHANNEL:
            return _("External Feed");

        case Jonah::AGGREGATED_CHANNEL:
            return _("Aggregated Feed");

        case Jonah::COMPOSITE_CHANNEL:
            return _("Composite Feed");
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
        case 'external_channels':
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
     *
     * @param string $type  The Jonah::* constant for the channel type.
     *
     * @return string  The string representation of the channel type.
     */
    static public function typeToPermName($type)
    {
        if ($type == Jonah::INTERNAL_CHANNEL) {
            return 'internal_channels';
        } elseif ($type == Jonah::EXTERNAL_CHANNEL) {
            return 'external_channels';
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
     * Build Jonah's list of menu items.
     */
    static public function getMenu($returnType = 'object')
    {
        global $registry, $conf;

        $menu = new Horde_Menu();

        /* If authorized, show admin links. */
        if (Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT)) {
            $menu->addArray(array('url' => Horde::url('channels/index.php'), 'text' => _("_Feeds"), 'icon' => 'jonah.png'));
        }
        foreach ($conf['news']['enable'] as $channel_type) {
            if (Jonah::checkPermissions($channel_type, Horde_Perms::EDIT)) {
                $menu->addArray(array('url' => Horde::url('channels/edit.php'), 'text' => _("New Feed"), 'icon' => 'new.png'));
                break;
            }
        }
        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $news = $GLOBALS['injector']->getInstance('Jonah_Driver');
            $channel = $news->getChannel($channel_id);
            if ($channel['channel_type'] == Jonah::INTERNAL_CHANNEL &&
                Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::EDIT, $channel_id)) {
                $menu->addArray(array('url' => Horde::url('stories/edit.php')->add('channel_id', (int)$channel_id), 'text' => _("_New Story"), 'icon' => 'new.png'));
            }
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

}
