<?php
/**
 * The Agora:: class provides basic Agora functionality.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Agora
 */
class Agora {

    /**
     * The virtual path to use for VFS data.
     */
    const VFS_PATH = '.horde/agora/attachments/';
    const AVATAR_PATH = '.horde/agora/avatars/';

    /**
     * Determines the requested forum_id, message_id and application by
     * checking first if they are passed as the single encoded var or
     * individual vars.
     *
     * @return array  Forum, message id and application.
     */
    function getAgoraId()
    {
        if (($id = Horde_Util::getFormData('agora')) !== null) {
            if (strstr($id, '.')) {
                list($forum_id, $message_id) = explode('.', $id, 2);
            } else {
                $forum_id = $id;
                $message_id = 0;
            }
        } else {
            $forum_id = Horde_Util::getFormData('forum_id');
            $message_id = Horde_Util::getFormData('message_id');
        }
        $scope = basename(Horde_Util::getFormData('scope', 'agora'));

        return array($forum_id, $message_id, $scope);
    }

    /**
     * Creates the Agora id.
     *
     * @return string  If passed with the $url parameter, returns a completed
     *                 url with the agora_id tacked on at the end, otherwise
     *                 returns the simple agora_id.
     */
    function setAgoraId($forum_id, $message_id, $url = '', $scope = null, $encode = false)
    {
        $agora_id = $forum_id . '.' . $message_id;

        if (!empty($url)) {
            if ($scope) {
                $url = Horde_Util::addParameter($url, 'scope', $scope, $encode);
            } else {
                $url = Horde_Util::addParameter($url, 'scope', Horde_Util::getGet('scope', 'agora'), $encode);
            }
            return Horde_Util::addParameter($url, 'agora', $agora_id, $encode);
        }

        return $agora_id;
    }

    /**
     * Returns a new or the current CAPTCHA string.
     *
     * @param boolean $new  If true, a new CAPTCHA is created and returned.
     *                      The current, to-be-confirmed string otherwise.
     *
     * @return string  A CAPTCHA string.
     */
    function getCAPTCHA($new = false)
    {
        global $session;

        if ($new || !$session->get('agora', 'captcha')) {
            $captcha = '';
            for ($i = 0; $i < 5; ++$i) {
                $captcha .= chr(rand(65, 90));
            }
            $session->set('agora', 'captcha', $captcha);
        }

        return $session->get('agora', 'captcha');
    }

    /**
     * Formats a list of forums, showing each child of a parent with
     * appropriate indent using '.. ' as a leader.
     *
     * @param array $forums  The list of forums to format.
     *
     * @return array  Formatted forum list.
     */
    function formatCategoryTree($forums)
    {
        /* TODO this doesn't work, as forun_name doesn't contain ":".
         * Should use forum_parent_id instead. */
        $forums_list = array();
        foreach (array_values($forums) as $forum) {
            $levels = explode(':', $forum['forum_name']);
            $forums_list[$forum['forum_id']] = str_repeat('.. ', count($levels) - 1) . array_pop($levels);
        }
        return $forums_list;
    }

    /**
     * Returns the column to sort by, checking first if it is specified in the
     * URL, then returning the value stored in prefs.
     *
     * @param string $view  The view name, used to identify preference settings
     *                      for sorting.
     *
     * @return string  The column to sort by.
     */
    function getSortBy($view)
    {
        global $prefs;

        if (($sortby = Horde_Util::getFormData($view . '_sortby')) !== null) {
            $prefs->setValue($view . '_sortby', $sortby);
        }
        $sort_by = $prefs->getValue($view . '_sortby');

        /* BC check for now invalid sort criteria. */
        if ($sort_by == 'message_date' || substr($sort_by, 0, 1) == 'l') {
            $sort_by = $prefs->getDefault($view . '_sortby');
            $prefs->setValue($view . '_sortby', $sortby);
        }

        return $sort_by;
    }

    /**
     * Returns the sort direction, checking first if it is specified in the URL,
     * then returning the value stored in prefs.
     *
     * @param string $view  The view name, used to identify preference settings
     *                      for sorting.
     *
     * @return integer  The sort direction, 0 = ascending, 1 = descending.
     */
    function getSortDir($view)
    {
        global $prefs;
        if (($sortdir = Horde_Util::getFormData($view . '_sortdir')) !== null) {
            $prefs->setValue($view . '_sortdir', $sortdir);
        }
        return $prefs->getValue($view . '_sortdir');
    }

    /**
     * Formats column headers have sort links and sort arrows.
     *
     * @param array  $columns   The columns to format.
     * @param string $sort_by   The current 'sort-by' column.
     * @param string $sort_dir  The current sort direction.
     * @param string $view      The view name, used to identify preference
     *                          settings for sorting.
     *
     * @return array  The formated column headers to be displayed.
     */
    function formatColumnHeaders($columns, $sort_by, $sort_dir, $view)
    {
        /* Get the current url, remove any sorting parameters. */
        $url = Horde::selfUrl(true);
        $url = Horde_Util::removeParameter($url, array($view . '_sortby', $view . '_sortdir'));

        /* Go through the column headers to format and add sorting links. */
        $headers = array();
        foreach ($columns as $col_name => $col_title) {
            $extra = array();
            /* Is this a column with two headers? */
            if (is_array($col_title)) {
                $keys = array_keys($col_title);
                $extra_name = $keys[0];
                if ($sort_by == $keys[1]) {
                    $extra = array($keys[0] => $col_title[$keys[0]]);
                    $col_name = $keys[1];
                    $col_title = $col_title[$keys[1]];
                } else {
                    $extra = array($keys[1] => $col_title[$keys[1]]);
                    $col_name = $keys[0];
                    $col_title = $col_title[$keys[0]];
                }
            }
            if ($sort_by == $col_name) {
                /* This column is currently sorted by, plain title and
                 * add sort direction arrow. */
                $sort_img = ($sort_dir ? 'za.png' : 'az.png');
                $sort_title = ($sort_dir ? _("Sort Ascending") : _("Sort Descending"));
                $col_arrow = Horde::link(Horde_Util::addParameter($url, array($view . '_sortby' => $col_name, $view . '_sortdir' => $sort_dir ? 0 : 1)), $sort_title) .
                    Horde::img($sort_img, $sort_title) . '</a> ';
                $col_class = 'selected';
            } else {
                /* Column not currently sorted, add link to sort by
                 * this one and no sort arrow. */
                $col_arrow = '';
                $col_title = Horde::link(Horde_Util::addParameter($url, $view . '_sortby', $col_name), sprintf(_("Sort by %s"), $col_title)) . $col_title . '</a>';
                $col_class = 'item';
            }
            $col_class .= ' leftAlign';
            if (count($extra)) {
                list($name, $title) = each($extra);
                $col_title .= '&nbsp;<small>[' .
                    Horde::link(Horde_Util::addParameter($url, $view . '_sortby', $name), sprintf(_("Sort by %s"), $title)) . $title . '</a>' .
                    ']</small>';
                $col_name = $extra_name;
            }
            $headers[$col_name] = $col_arrow . $col_title;
            $headers[$col_name . '_class_plain'] = $col_class;
            $headers[$col_name . '_class'] = empty($col_class) ? '' : ' class="' . $col_class . '"';
        }

        return $headers;
    }

    /**
     * Returns a {@link VFS} instance.
     *
     * @return VFS  A VFS instance.
     */
    function getVFS()
    {
        global $conf;

        if (!isset($conf['vfs']['type'])) {
            return PEAR::raiseError(_("The VFS backend needs to be configured to enable attachment uploads."));
        }

        try {
            return $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (Horde_Vfs_Exception $e) {
            return PEAR::raiseError($e);
        }
    }

    function validateAvatar($avatar_path)
    {
        if (!$GLOBALS['conf']['avatar']['allow_avatars'] || !$avatar_path) {
            return false;
        }

        preg_match('/^(http|vfs):\/\/(.*)\/(gallery|uploaded|.*)\/(.*\..*)/i',
                   $avatar_path, $matches);

        switch ($matches[1]) {
        case 'http':
            if (!$GLOBALS['conf']['avatar']['enable_external']) {
                /* Avatar is external and external avatars have been
                 * disabled. */
                return false;
            }
            $dimensions = @getimagesize($avatar_path);
            if (($dimensions === false) ||
                ($dimensions[0] > $GLOBALS['conf']['avatar']['max_width']) ||
                ($dimensions[1] > $GLOBALS['conf']['avatar']['max_height'])) {
                /* Avatar is external and external avatars are
                 * enabled, but the image is too wide or high. */
                return false;
            } else {
                $avatar = null;

                $flock = fopen($avatar_path, 'r');
                while (!feof($flock)) {
                    $avatar .= fread($flock, 2048);
                }
                fclose($flock);

                if (strlen($avatar) > ($GLOBALS['conf']['avatar']['max_size'] * 1024)) {
                    /* Avatar is external and external avatars have
                     * been enabled, but the file is too large. */
                    return false;
                }
            }
            return true;

        case 'vfs':
            switch ($matches[3]) {
            case 'gallery':
                /* Avatar is within the gallery. */
                return $GLOBALS['conf']['avatar']['enable_gallery'];

            case 'uploaded':
                /* Avatar is within the uploaded avatar collection. */
                return $GLOBALS['conf']['avatar']['enable_uploads'];

            default:
                /* Malformed URL. */
                return false;
            }
            break;

        default:
            /* Malformed URL. */
            return false;
        }

        return false;
    }

    function getAvatarUrl($avatar_path, $scopeend_sid = true)
    {
        if (!$avatar_path) {
            return PEAR::raiseError(_("Malformed avatar."));
        }

        preg_match('/^(http|vfs):\/\/(.*)\/(gallery|uploaded|.*)\/(.*\..*)/i',
                   $avatar_path, $matches);

        switch ($matches[1]) {
        case 'http':
            /* HTTP URL's are already "real" */
            break;

        case 'vfs':
            /* We need to do some re-writing to VFS paths. */
            switch ($matches[3]) {
            case 'gallery':
                $avatar_collection_id = '1';
                break;

            case 'uploaded':
                $avatar_collection_id = '2';
                break;

            default:
                return PEAR::raiseError(_("Malformed database entry."));
            }

            $avatar_path = Horde::url('avatars/?id=' . urlencode($matches[4]) . ':' . $avatar_collection_id, true, $scopeend_sid);
            break;
        }

        return $avatar_path;
    }

    /**
     * Send new posts to a distribution email address for a wider audience
     *
     * @param int $message_id  Identifier of message to be distributed
     *
     * @throws Horde_Mime_Exception
     */
    function distribute($message_id)
    {
        global $conf;

        $storage = $GLOBALS['injector']->getInstance('Agora_Factory_Driver')->create();
        $message = $storage->getMessage($message_id);
        $forum = $storage->getForum($message['forum_id']);

        if (empty($forum['forum_distribution_address'])) {
            return;
        }

        $mail = new Horde_Mime_Mail();
        $mail->addHeader('X-Horde-Agora-Post', $message_id);
        $mail->addHeader('From', strpos($message['message_author'], '@') ? $message['message_author'] : $forum['forum_distribution_address']);
        $mail->addHeader('Subject', '[' . $forum['forum_name'] . '] ' . $message['message_subject']);
        $mail->addHeader('To', $forum['forum_distribution_address']);
        $mail->setBody($message['body']);

        $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
    }
}
