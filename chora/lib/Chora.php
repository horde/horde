<?php
/**
 * Chora Base Class.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */
class Chora {

    /**
     * Return a text description of how long its been since the file
     * has been last modified.
     *
     * @param integer $date  Number of seconds since epoch we wish to display.
     * @param boolean $long  If true, display a more verbose date.
     *
     * @return string  The human-readable date.
     */
    function readableTime($date, $long = false)
    {
        static $time, $desc, $breaks;

        /* Initialize popular variables. */
        if (!isset($time)) {
            $time = time();
            $desc = array(
                1 => array(_("second"), _("seconds")),
                60 => array(_("minute"), _("minutes")),
                3600 => array(_("hour"), _("hours")),
                86400 => array(_("day"), _("days")),
                604800 => array(_("week"), _("weeks")),
                2628000 => array(_("month"), _("months")),
                31536000 => array(_("year"), _("years"))
            );
            $breaks = array_keys($desc);
        }

        $i = count($breaks);
        $secs = $time - $date;

        if ($secs < 2) {
            return _("very little time");
        }

        while (--$i && $i && $breaks[$i] * 2 > $secs);

        $break = $breaks[$i];

        $val = intval($secs / $break);
        $retval = $val . ' ' . ($val > 1 ? $desc[$break][1] : $desc[$break][0]);
        if ($long && $i > 0) {
            $rest = $secs % $break;
            $break = $breaks[--$i];
            $rest = (int)($rest / $break);
            if ($rest > 0) {
                $resttime = $rest . ' ' . ($rest > 1 ? $desc[$break][1] : $desc[$break][0]);
                $retval .= ', ' . $resttime;
            }
        }

        return $retval;
    }

    /**
     * Initialize global variables and objects.
     */
    function initialize()
    {
        global $acts, $defaultActs, $conf, $where, $atdir, $fullname, $prefs,
               $sourceroot, $scriptName;

        $sourceroots = Chora::sourceroots();

        /**
         * Variables we wish to propagate across web pages
         *  sbt = Sort By Type (name, age, author, etc)
         *  ha  = Hide Attic Files
         *  ord = Sort order
         *
         * Obviously, defaults go into $defaultActs :)
         * TODO: defaults of 1 will not get propagated correctly - avsm
         * XXX: Rewrite this propagation code, since it sucks - avsm
         */
        $defaultActs = array('sbt' => constant($conf['options']['defaultsort']),
                             'sa'  => 0,
                             'ord' => Horde_Vcs::SORT_ASCENDING,
                             'ws'  => 1);

        /* Use the last sourceroot used as the default value if the user
         * has that preference. */
        $remember_last_file = $prefs->getValue('remember_last_file');
        if ($remember_last_file) {
            $last_file = $prefs->getValue('last_file') ? $prefs->getValue('last_file') : null;
            $last_sourceroot = $prefs->getValue('last_sourceroot') ? $prefs->getValue('last_sourceroot') : null;
        }

        if ($remember_last_file && !empty($last_sourceroot) &&
            is_array(@$sourceroots[$last_sourceroot])) {
            $defaultActs['rt'] = $last_sourceroot;
        } else {
            foreach ($sourceroots as $key => $val) {
                if (isset($val['default']) || !isset($defaultActs['rt'])) {
                    $defaultActs['rt'] = $key;
                }
            }
        }

        /* See if any have been passed as GET variables, and if so,
         * assign them into the acts array. */
        $acts = array();
        foreach ($defaultActs as $key => $default) {
            $acts[$key] = Util::getFormData($key, $default);
        }

        if (!isset($sourceroots[$acts['rt']])) {
            Chora::fatal(_("Malformed URL"), '400 Bad Request');
        }

        $sourcerootopts = $sourceroots[$acts['rt']];
        $sourceroot = $acts['rt'];

        $conf['paths']['temp'] = Horde::getTempDir();
        $GLOBALS['VC'] = Horde_Vcs::factory($sourcerootopts['type'],
            array('sourceroot' => $sourcerootopts['location'],
                  'paths' => $conf['paths'],
                  'username' => isset($sourcerootopts['username']) ? $sourcerootopts['username'] : '',
                  'password' => isset($sourcerootopts['password']) ? $sourcerootopts['password'] : ''));
        if (is_a($GLOBALS['VC'], 'PEAR_Error')) {
            Chora::fatal($GLOBALS['VC']->getMessage());
        }

        $conf['paths']['sourceroot'] = $sourcerootopts['location'];
        $conf['paths']['cvsusers'] = $sourcerootopts['location'] . '/' . (isset($sourcerootopts['cvsusers']) ? $sourcerootopts['cvsusers'] : '');
        $conf['paths']['introText'] = CHORA_BASE . '/config/' . (isset($sourcerootopts['intro']) ? $sourcerootopts['intro'] : '');
        $conf['options']['introTitle'] = isset($sourcerootopts['title']) ? $sourcerootopts['title'] : '';
        $conf['options']['sourceRootName'] = $sourcerootopts['name'];

        $where = Util::getFormData('f', '');
        if ($where == '') {
            $where = '/';
        }

        /* Location relative to the sourceroot. */
        $where = preg_replace('|^/|', '', $where);
        $where = preg_replace('|\.\.|', '', $where);

        /* Location of this script (e.g. /chora/browse.php). */
        $scriptName = preg_replace('|^/?|', '/', $_SERVER['PHP_SELF']);
        $scriptName = preg_replace('|/$|', '', $scriptName);

        /* Store last file/repository viewed, and set 'where' to
         * last_file if necessary. */
        if ($remember_last_file) {
            if (!isset($_SESSION['chora']['login'])) {
                $_SESSION['chora']['login'] = 0;
            }

            /* We store last_sourceroot and last_file only when we have
             * already displayed at least one page. */
            if (!empty($_SESSION['chora']['login'])) {
                $prefs->setValue('last_sourceroot', $acts['rt']);
                $prefs->setValue('last_file', $where);
            } else {
                /* We are displaying the first page. */
                if ($last_file && !$where) {
                    $where = $last_file;
                }
                $_SESSION['chora']['login'] = 1;
            }
        }

        $fullname = $sourcerootopts['location'] . (substr($sourcerootopts['location'], -1) == '/' ? '' : '/') . $where;

        if ($sourcerootopts['type'] == 'cvs') {
            $fullname = preg_replace('|/$|', '', $fullname);
            $atdir = @is_dir($fullname);
        } else {
            $atdir = !$where || (substr($where, -1) == '/');
        }
        $where = preg_replace('|/$|', '', $where);

        if ($sourcerootopts['type'] == 'cvs' && !@is_dir($sourcerootopts['location'])) {
            Chora::fatal(_("Sourceroot not found. This could be a misconfiguration by the server administrator, or the server could be having temporary problems. Please try again later."), '500 Internal Server Error');
        }

        if (Chora::isRestricted($where)) {
            Chora::fatal(sprintf(_("%s: Forbidden by server configuration"), $where), '403 Forbidden');
        }
    }

    function whereMenu()
    {
        global $where, $atdir;

        $bar = $wherePath = '';
        $i = 0;
        $dirs = explode('/', $where);
        $last = count($dirs) - 1;

        foreach ($dirs as $dir) {
            $wherePath .= '/' . $dir;
            if (!$atdir && $i++ == $last) {
                $wherePath .= '/';
            }
            $wherePath = str_replace('//', '/', $wherePath);
            if (!empty($dir) && ($dir != 'Attic')) {
                $bar .= '/ <a href="' . Chora::url('', $wherePath) . '">'. Text::htmlallspaces($dir) . '</a> ';
            }
        }
        return $bar;
    }

    /**
     * Output an error page.
     *
     * @param string $message       The verbose error message to be displayed.
     * @param string $responseCode  The HTTP error number (and optional text),
     *                              for sending 404s or other codes if
     *                              appropriate..
     */
    public static function fatal($message, $responseCode = null)
    {
        if (defined('CHORA_ERROR_HANDLER') && constant('CHORA_ERROR_HANDLER')) {
            return;
        }

        global $registry, $conf, $notification, $browser, $prefs;

        /* Don't store the bad file in the user's preferences. */
        $prefs->setValue('last_file', '');

        if ($responseCode) {
            header('HTTP/1.0 ' . $responseCode);
        }

        $notification->push($message, 'horde.error');
        require CHORA_TEMPLATES . '/common-header.inc';
        require CHORA_TEMPLATES . '/menu.inc';
        require $registry->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }

    /**
     * Given a return object from a Horde_Vcs:: call, make sure
     * that it's not a PEAR_Error object.
     *
     * @param mixed $e  Return object from a Horde_Vcs:: call.
     */
    function checkError($e)
    {
        if (is_a($e, 'PEAR_Error')) {
            Chora::fatal($e->getMessage());
        }
    }

    /**
     * Convert a commit-name into whatever the user wants.
     *
     * @param string $name  Account name.
     *
     * @return string  The transformed name.
     */
    function showAuthorName($name, $fullname = false)
    {
        static $users = null;

        if (is_null($users)) {
            $users = $GLOBALS['VC']->getUsers($GLOBALS['conf']['paths']['cvsusers']);
        }

        if (is_array($users) && isset($users[$name])) {
            return '<a href="mailto:' . htmlspecialchars($users[$name]['mail']) . '">' .
                htmlspecialchars($fullname ? $users[$name]['name'] : $name) .
                '</a>' . ($fullname ? ' <em>' . htmlspecialchars($name) . '</em>' : '');
        }

        return htmlspecialchars($name);
    }

    /**
     * Generate a URL that links into Chora.
     *
     * @param string $script  Name of the Chora script to link into
     * @param string $uri     The path being browsed.
     * @param array  $args    Key/value pair of any GET parameters to append
     * @param string $anchor  Anchor entity name
     *
     * @return string  The URL, with session information if necessary.
     */
    function url($script = '', $uri = '', $args = array(), $anchor = '')
    {
        global $conf, $acts, $defaultActs;

        $differing = array();
        foreach ($acts as $key => $val) {
            if ($val != $defaultActs[$key]) {
                $differing[$key] = $val;
            }
        }

        $arglist = array_merge($differing, $args);
        $script = $script ? $script . '.php' : 'browse.php';

        if ($conf['options']['urls'] == 'rewrite') {
            if ($script == 'browse.php') {
                $script = $uri;
                if (substr($script, 0, 1) == '/') {
                    $script = substr($script, 1);
                }
            } else {
                $script .= '/' . $uri;
            }
        } else {
            $arglist['f'] = $uri;
        }

        $url = Util::addParameter(Horde::applicationUrl($script), $arglist);
        if (!empty($anchor)) {
            $url .= "#$anchor";
        }

        return $url;
    }

    /**
     * Generates hidden form fields with all required parameters.
     *
     * @param array  $args    Key/value pair of any POST parameters to append
     *
     * @return string  The form fields, with session information if necessary.
     */
    function formInputs($args = array())
    {
        global $conf, $acts, $defaultActs;

        $differing = array();
        foreach ($acts as $key => $val) {
            if ($val != $defaultActs[$key]) {
                $differing[$key] = $val;
            }
        }

        $arglist = array_merge($differing, $args);

        $fields = Util::formInput();
        foreach ($arglist as $key => $val) {
            $fields .= '<input type="hidden" name="' . htmlspecialchars($key)
                . '" value="' . htmlspecialchars($val) . '" />';
        }

        return $fields;
    }

    /**
     * Returns the entries of $sourceroots that the current user has access to.
     *
     * @return array  The sourceroots that the current user has access to.
     */
    function sourceroots()
    {
        global $perms, $sourceroot, $sourceroots;

        $arr = array();
        foreach ($sourceroots as $key => $val) {
            if (!$perms->exists('chora:sourceroots:' . $key) ||
                 $perms->hasPermission('chora:sourceroots:' . $key,
                                       Auth::getAuth(),
                                       PERMS_READ | PERMS_SHOW)) {
                $arr[$key] = $val;
            }
        }

        return $arr;
    }

    /**
     * Generate a list of repositories available from this
     * installation of Chora.
     *
     * @return string  XHTML code representing links to the repositories.
     */
    function repositories()
    {
        $sourceroots = Chora::sourceroots();
        $num_repositories = count($sourceroots);
        if ($num_repositories == 1) {
            return '';
        }

        $arr = array();
        foreach ($sourceroots as $key => $val) {
            if ($GLOBALS['sourceroot'] != $key) {
                $arr[] = '<option value="' .
                    Chora::url('', '', array('rt' => $key)) .
                    '">' . $val['name'] . '</option>';
            }
        }

        return
            '<form action="#" id="repository-picker">'
            . '<select onchange="location.href=this[this.selectedIndex].value">'
            . '<option value="">' . _("Change repositories:") . '</option>'
            . implode(' , ', $arr) . '</select></form>';
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
    function pretty($mime_type, $fp)
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
     * @return boolean whether or not the item is allowed to be displayed
     **/
    function isRestricted($item)
    {
        global $conf, $perms, $sourceroots, $sourceroot;
        static $restricted;

        // First check if the current user has access to this repository.
        if ($perms->exists('chora:sourceroots:' . $sourceroot) &&
            !$perms->hasPermission('chora:sourceroots:' . $sourceroot,
                                   Auth::getAuth(),
                                   PERMS_READ | PERMS_SHOW)) {
            return true;
        }

        if (!isset($restricted)) {
            $restricted = array();
            if (isset($conf['restrictions']) && is_array($conf['restrictions'])) {
                $restricted = $conf['restrictions'];
            }

            foreach ($sourceroots as $key => $val) {
                if ($sourceroot == $key) {
                    if (isset($val['restrictions']) && is_array($val['restrictions'])) {
                        $restricted = array_merge($restricted, $val['restrictions']);
                        break;
                    }
                }
            }
        }

        if (!empty($restricted) && is_array($restricted) && count($restricted)) {
            for ($i = 0; $i < count($restricted); $i++) {
                if (preg_match('|' . str_replace('|', '\|', $restricted[$i]) . '|', $item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Build Chora's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        require_once 'Horde/Menu.php';

        $menu = new Menu();
        $menu->add(Chora::url(), _("_Browse"), 'chora.png');

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     */
    function getFileViews()
    {
        global $where;

        $views = array();
        $current = str_replace('.php', '', basename($_SERVER['PHP_SELF']));

        $views[] = $current == 'browse'
            ? '<em class="widget">' . _("Logs") . '</em>'
            : Horde::widget(Chora::url('', $where), _("Logs"), 'widget', '',
                            '', _("_Logs"));

        if (!empty($GLOBALS['conf']['paths']['cvsps']) ||
            $GLOBALS['VC']->supportsFeature('patchsets')) {
            $views[] = $current == 'patchsets'
                ? '<em class="widget">' . _("Patchsets") . '</em>'
                : Horde::widget(Chora::url('patchsets', $where), _("Patchsets"),
                                'widget', '', '', _("_Patchsets"));
        }

        if ($GLOBALS['VC']->supportsFeature('branches')) {
            if (empty($GLOBALS['conf']['paths']['cvsgraph'])) {
                $views[] = $current == 'history'
                    ? '<em class="widget">' . _("Branches") . '</em>'
                    : Horde::widget(Chora::url('history', $where), _("Branches"),
                                    'widget', '', '', _("_Branches"));
            } else {
                $views[] = $current == 'cvsgraph'
                    ? '<em class="widget">' . _("Branches") . '</em>'
                    : Horde::widget(Chora::url('cvsgraph', $where), _("Branches"),
                                    'widget', '', '', _("_Branches"));
            }
        }

        /* Can't use $current - gives us PATH_INFO information. */
        $views[] = (strpos($_SERVER['PHP_SELF'], '/stats.php/') !== false)
            ? '<em class="widget">' . _("Statistics") . '</em>'
            : Horde::widget(Chora::url('stats', $where), _("Statistics"),
                            'widget', '', '', _("_Statistics"));

        return _("View:") . ' ' . implode(' | ', $views);
    }

    /**
     */
    function formatLogMessage($log)
    {
        global $conf;

        require_once 'Horde/Text/Filter.php';

        $log = Text_Filter::filter($log, 'text2html', array('parselevel' => TEXT_HTML_MICRO, 'charset' => NLS::getCharset(), 'class' => ''));

        if (!empty($conf['tickets']['regexp']) &&
            !empty($conf['tickets']['replacement'])) {
            $log = preg_replace($conf['tickets']['regexp'], $conf['tickets']['replacement'], $log);
        }

        return $log;
    }

    /**
     * Return a list of tags for a given log entry.
     *
     * @param Horde_Vcs_Log $lg  The Horde_Vcs_Log object.
     * @param string $where     The filename.
     *
     * @return array  An array of linked tags.
     */
    function getTags($lg, $where)
    {
        $tags = array();
        foreach ($lg->querySymbolicBranches() as $symb => $bra) {
            $tags[] = '<a href="' . Chora::url('', $where, array('onb' => $bra)) . '">'. htmlspecialchars($symb) . '</a>';
        }
        if ($lg->tags) {
            foreach ($lg->tags as $tag) {
            $tags[] = htmlspecialchars($tag);
            }
        }
        return $tags;
    }

    /**
     * Return branch information for a given revision.
     *
     * @param Horde_Vcs_File $fl  The Horde_Vcs_File object.
     * @param string $rev         The filename.
     *
     * @return array  An 2-member array - branch name and branch revision.
     */
    function getBranch($fl, $rev)
    {
        $branchName = '';
        $rev_ob = $fl->rev->getRevisionObject();
        $branchRev = $rev_ob->strip($rev, 1);
        if (isset($fl->branches[$rev])) {
            $branchName = $fl->branches[$rev];
        } elseif (isset($fl->branches[$branchRev])) {
            $branchName = $fl->branches[$branchRev];
        }
        return array($branchName, $branchRev);
    }

    /**
     * Return formatted date information.
     *
     * @param integer $date  Number of seconds since epoch we wish to display.
     *
     * @return string  The date formatted pursuant to Horde prefs.
     */
    function formatDate($date)
    {
        static $format;

        if (!isset($format)) {
            $format = $GLOBALS['prefs']->getValue('date_format') .
                ($GLOBALS['prefs']->getValue('twenty_four')
                 ? ' %H:%M'
                 : ' %I:%M %p');
        }

        return strftime($format, $date);
    }

}
