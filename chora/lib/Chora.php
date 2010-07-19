<?php
/**
 * Chora Base Class.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Chora
 */
class Chora
{
    /**
     * Cached data for isRestricted().
     *
     * @var array
     */
    static public $restricted;

    /**
     * Cached data for readableTime().
     *
     * @var array
     */
    static public $rtcache;

    /**
     * Cached data for formatDate().
     *
     * @var string
     */
    static public $fdcache;

    /**
     * Create the breadcrumb directory listing.
     *
     * @param string $where  The current filepath.
     * @param string $onb    If not null, the branch to add to the generated
     *                       URLs.
     *
     * @return string  The directory string.
     */
    static public function whereMenu($where, $onb = null)
    {
        $bar = '';
        $dirs = explode('/', $where);
        $dir_count = count($dirs) - 1;

        $path = '';
        foreach ($dirs as $i => $dir) {
            if (!empty($path)) {
                $path .= '/';
            }
            $path .= $dir;
            if (!empty($dir)) {
                $url = self::url('browsedir', $path . ($i == $dir_count && !$GLOBALS['atdir'] ? '' : '/'));
                if (!empty($onb)) {
                    $url = Horde_Util::addParameter($url, array('onb' => $onb));
                }
                $bar .= '/ <a href="' . $url . '">' . $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($dir, 'space2html', array('encode' => true, 'encode_all' => true)) . '</a> ';
            }
        }

        return $bar;
    }

    /**
     * Output an error page.
     *
     * @param string $message  The verbose error message to be displayed.
     * @param string $code     The HTTP error number (and optional text), for
     *                         sending 404s or other codes if appropriate.
     */
    static public function fatal($message, $code = null)
    {
        if (defined('CHORA_ERROR_HANDLER') && constant('CHORA_ERROR_HANDLER')) {
            return;
        }

        if (is_a($message, 'Horde_Vcs_Exception')) {
            $message = $message->getMessage();
        }

        if ($code) {
            header('HTTP/1.0 ' . $code);
        }

        $GLOBALS['notification']->push($message, 'horde.error');
        require CHORA_TEMPLATES . '/common-header.inc';
        require CHORA_TEMPLATES . '/menu.inc';
        require $GLOBALS['registry']->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }

    /**
     * Generate a URL that links into Chora.
     *
     * @param string $script  Name of the Chora script to link into.
     * @param string $uri     The path being browsed.
     * @param array $args     Key/value pair of any GET parameters to append.
     * @param string $anchor  Anchor entity name.
     *
     * @return string  The URL, with session information if necessary.
     */
    static public function url($script, $uri = '', $args = array(),
                               $anchor = '')
    {
        $arglist = self::_getArgList($GLOBALS['acts'], $GLOBALS['defaultActs'], $args);
        $script .= '.php';

        if ($GLOBALS['conf']['options']['urls'] == 'rewrite') {
            if (in_array($script, array('browse.php', 'browsedir.php'))) {
                $script = $uri;
                if (substr($script, 0, 1) == '/') {
                    $script = substr($script, 1);
                }
            } else {
                $script .= '/' . $uri;
            }
        } elseif (!empty($uri)) {
            $arglist['f'] = $uri;
        }

        $url = Horde_Util::addParameter(Horde::applicationUrl($script), $arglist);

        return empty($anchor) ? $url : ($url . '#' . $anchor);
    }

    /**
     * Generates hidden form fields with all required parameters.
     *
     * @return string  The form fields, with session information if necessary.
     */
    static public function formInputs()
    {
        $arglist = self::_getArgList($GLOBALS['acts'], $GLOBALS['defaultActs'], array());

        $fields = Horde_Util::formInput();
        foreach ($arglist as $key => $val) {
            $fields .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($val) . '" />';
        }

        return $fields;
    }

    /**
     * TODO
     */
    static protected function _getArgList($acts, $defaultActs, $args)
    {
        $differing = array();

        foreach ($acts as $key => $val) {
            if ($val != $defaultActs[$key]) {
                $differing[$key] = $val;
            }
        }

        return array_merge($differing, $args);
    }

    /**
     * TODO
     */
    static public function checkPerms($key)
    {
        return (!$GLOBALS['injector']->getInstance('Horde_Perms')->exists('chora:sourceroots:' . $key) ||
                $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('chora:sourceroots:' . $key, $GLOBALS['registry']->getAuth(), Horde_Perms::READ | Horde_Perms::SHOW));
    }

    /**
     * Returns the entries of $sourceroots that the current user has access
     * to.
     *
     * @return array  The sourceroots that the current user has access to.
     */
    static public function sourceroots()
    {
        $arr = array();

        foreach ($GLOBALS['sourceroots'] as $key => $val) {
            if (self::checkPerms($key)) {
                $arr[$key] = $val;
            }
        }

        return $arr;
    }

    /**
     * Generate a list of repositories available from this installation of
     * Chora.
     *
     * @return string  XHTML code representing links to the repositories.
     */
    static public function repositories()
    {
        $sourceroots = self::sourceroots();
        $num_repositories = count($sourceroots);

        if ($num_repositories == 1) {
            return '';
        }

        $arr = array();
        foreach ($sourceroots as $key => $val) {
            if ($GLOBALS['sourceroot'] != $key) {
                $arr[] = '<option value="' . self::url('browsedir', '', array('rt' => $key)) . '">' . $val['name'] . '</option>';
            }
        }

        return '<form action="#" id="repository-picker">' .
            '<select onchange="location.href=this[this.selectedIndex].value">' .
            '<option value="">' . _("Change repositories:") . '</option>' .
            implode(' , ', $arr) . '</select></form>';
    }

    /**
     * Pretty-print the checked out copy, using Horde_Mime_Viewer.
     *
     * @param string $mime_type  File extension of the checked out file.
     * @param resource $fp       File pointer to the head of the checked out
     *                           copy.
     *
     * @return mixed  The Horde_Mime_Viewer object which can be rendered or
     *                false on failure.
     */
    static public function pretty($mime_type, $fp)
    {
        $lns = '';
        while ($ln = fread($fp, 8192)) {
            $lns .= $ln;
        }

        $mime = new Horde_Mime_Part();
        $mime->setType($mime_type);
        $mime->setContents($lns);

        return Horde_Mime_Viewer::factory($mime);
    }

    /**
     * Check if the given item is restricted from being shown.
     *
     * @param string $where  The current file path.
     *
     * @return boolean  Is item allowed to be displayed?
     */
    static public function isRestricted($where)
    {
        // First check if the current user has access to this repository.
        if (!self::checkPerms($GLOBALS['sourceroot'])) {
            return true;
        }

        if (!isset(self::$restricted)) {
            $restricted = array();

            if (isset($GLOBALS['conf']['restrictions']) &&
                is_array($GLOBALS['conf']['restrictions'])) {
                $restricted = $GLOBALS['conf']['restrictions'];
            }

            foreach ($GLOBALS['sourceroots'] as $key => $val) {
                if (($GLOBALS['sourceroot'] == $key) &&
                    isset($val['restrictions']) &&
                    is_array($val['restrictions'])) {
                    $restricted = array_merge($restricted, $val['restrictions']);
                    break;
                }
            }

            self::$restricted = $restricted;
        }

        if (!empty($restricted)) {
            for ($i = 0; $i < count($restricted); ++$i) {
                if (preg_match('|' . str_replace('|', '\|', $restricted[$i]) . '|', $where)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Build Chora's list of menu items.
     *
     * @return string  The menu HTML code.
     */
    static public function getMenu()
    {
        $menu = new Horde_Menu();
        $menu->add(self::url('browsedir'), _("_Browse"), 'chora.png');
        return $menu;
    }

    /**
     * Generate the link used for various file-based views.
     *
     * @param string $where    The current file path.
     * @param string $current  The current view ('browsefile', 'patchsets',
     *                         'history', 'cvsgraph', or 'stats').
     *
     * @return array  An array of file view links.
     */
    static public function getFileViews($where, $current)
    {
        $views = ($current == 'browsefile')
            ? array('<em class="widget">' . _("Logs") . '</em>')
            : array(Horde::widget(self::url('browsefile', $where), _("Logs"), 'widget', '', '', _("_Logs")));

        if ($GLOBALS['VC']->hasFeature('patchsets')) {
            $views[] = ($current == 'patchsets')
                ? '<em class="widget">' . _("Patchsets") . '</em>'
                : Horde::widget(self::url('patchsets', $where), _("Patchsets"), 'widget', '', '', _("_Patchsets"));
        }

        if ($GLOBALS['VC']->hasFeature('branches')) {
            if (empty($GLOBALS['conf']['paths']['cvsgraph'])) {
                $views[] = ($current == 'history')
                    ? '<em class="widget">' . _("Branches") . '</em>'
                    : Horde::widget(self::url('history', $where), _("Branches"), 'widget', '', '', _("_Branches"));
            } else {
                $views[] = ($current == 'cvsgraph')
                    ? '<em class="widget">' . _("Branches") . '</em>'
                    : Horde::widget(self::url('cvsgraph', $where), _("Branches"), 'widget', '', '', _("_Branches"));
            }
        }

        $views[] = ($current == 'stats')
            ? '<em class="widget">' . _("Statistics") . '</em>'
            : Horde::widget(self::url('stats', $where), _("Statistics"), 'widget', '', '', _("_Statistics"));

        return _("View:") . ' ' . implode(' | ', $views);
    }

    /**
     * Return a list of tags for a given log entry.
     *
     * @param Horde_Vcs_Log $lg  The Horde_Vcs_Log object.
     * @param string $where      The current filepath.
     *
     * @return array  An array of linked tags.
     */
    static public function getTags($lg, $where)
    {
        $tags = array();

        foreach ($lg->querySymbolicBranches() as $symb => $bra) {
            $tags[] = '<a href="' . self::url('browsefile', $where, array('onb' => $bra)) . '">'. htmlspecialchars($symb) . '</a>';
        }

        foreach ($lg->queryTags() as $tag) {
            $tags[] = htmlspecialchars($tag);
        }

        return $tags;
    }

    /**
     * Return a text description of how long its been since the file
     * has been last modified.
     *
     * @param integer $date  Number of seconds since epoch we wish to display.
     * @param boolean $long  If true, display a more verbose date.
     *
     * @return string  The human-readable date.
     */
    static public function readableTime($date, $long = false)
    {
        /* Initialize popular variables. */
        if (!isset(self::$rtcache)) {
            $desc = array(
                1 => array(_("second"), _("seconds")),
                60 => array(_("minute"), _("minutes")),
                3600 => array(_("hour"), _("hours")),
                86400 => array(_("day"), _("days")),
                604800 => array(_("week"), _("weeks")),
                2628000 => array(_("month"), _("months")),
                31536000 => array(_("year"), _("years"))
            );

            self::$rtcache = array(
                'breaks' => array_keys($desc),
                'desc' => $desc,
                'time' => time(),
            );
        }

        $cache = self::$rtcache;
        $i = count($cache['breaks']);
        $secs = $cache['time'] - $date;

        if ($secs < 2) {
            return _("very little time");
        }

        while (--$i && $i && $cache['breaks'][$i] * 2 > $secs);

        $break = $cache['breaks'][$i];

        $val = intval($secs / $break);
        $retval = $val . ' ' . ($val > 1 ? $cache['desc'][$break][1] : $cache['desc'][$break][0]);
        if ($long && $i > 0) {
            $rest = $secs % $break;
            $break = $cache['breaks'][--$i];
            $rest = (int)($rest / $break);
            if ($rest > 0) {
                $retval .= ', ' . $rest . ' ' . ($rest > 1 ? $cache['desc'][$break][1] : $cache['desc'][$break][0]);
            }
        }

        return $retval;
    }

    /**
     * Convert a commit-name into whatever the user wants.
     *
     * @param string $name  Account name.
     *
     * @return string  The transformed name.
     */
    static public function showAuthorName($name, $fullname = false)
    {
        try {
            $users = $GLOBALS['VC']->getUsers($GLOBALS['conf']['paths']['cvsusers']);
            if (isset($users[$name])) {
                return '<a href="mailto:' . htmlspecialchars($users[$name]['mail']) . '">' .
                    htmlspecialchars($fullname ? $users[$name]['name'] : $name) .
                    '</a>' . ($fullname ? ' <em>' . htmlspecialchars($name) . '</em>' : '');
            }
        } catch (Horde_Vcs_Exception $e) {}

        return htmlspecialchars($name);
    }

    /**
     * Return formatted date information.
     *
     * @param integer $date  Number of seconds since epoch we wish to display.
     *
     * @return string  The date formatted pursuant to Horde prefs.
     */
    static public function formatDate($date)
    {
        if (!isset(self::$fdcache)) {
            self::$fdcache = $GLOBALS['prefs']->getValue('date_format') .
                ($GLOBALS['prefs']->getValue('twenty_four')
                 ? ' %H:%M'
                 : ' %I:%M %p');
        }

        return strftime(self::$fdcache, $date);
    }

    /**
     * Formats a log message.
     *
     * @param string $log  The log message text.
     *
     * @return string  The formatted message.
     */
    static public function formatLogMessage($log)
    {
        $log = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($log, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));

        return (empty($GLOBALS['conf']['tickets']['regexp']) || empty($GLOBALS['conf']['tickets']['replacement']))
            ? $log
            : preg_replace($GLOBALS['conf']['tickets']['regexp'], $GLOBALS['conf']['tickets']['replacement'], $log);
    }

}
