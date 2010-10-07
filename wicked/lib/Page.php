<?php
/**
 * Wicked Abtract Page Class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class Wicked_Page {

    /**
     * Display modes supported by this page. Possible modes:
     *
     *   Wicked::MODE_CONTENT
     *   Wicked::MODE_DISPLAY
     *   Wicked::MODE_EDIT
     *   Wicked::MODE_REMOVE
     *   Wicked::MODE_HISTORY
     *   Wicked::MODE_DIFF
     *   Wicked::MODE_LOCKING
     *   Wicked::MODE_UNLOCKING
     *   Wicked::MODE_CREATE
     *
     * @var array
     */
    var $supportedModes = array();

    /**
     * Instance of a Text_Wiki processor.
     *
     * @var Text_Wiki
     */
    var $_proc;

    /**
     * The loaded page info.
     *
     * @var array
     */
    var $_page;

    /**
     * Is this a validly loaded page?
     *
     * @return boolean  True if we've loaded data, false otherwise.
     */
    function isValid()
    {
        return !empty($this->_page) && !is_a($this->_page, 'PEAR_Error');
    }

    /**
     * Retrieve this user's permissions for this page. If a
     * permissions object does not exist, we assume reasonable
     * defaults.
     *
     * @return integer  The permissions bitmask.
     */
    function getPermissions($pageName = null)
    {
        global $wicked;

        if (is_null($pageName)) {
            $pageName = $this->pageName();
        }

        $pageId = $wicked->getPageId($pageName);
        $permName = 'wicked:pages:' . $pageId;
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        if ($pageId !== false && $perms->exists($permName)) {
            return $perms->getPermissions($permName);
        } elseif ($perms->exists('wicked:pages')) {
            return $perms->getPermissions('wicked:pages');
        } else {
            if (!$GLOBALS['registry']->getAuth()) {
                return Horde_Perms::SHOW | Horde_Perms::READ;
            } else {
                return Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT | Horde_Perms::DELETE;
            }
        }
    }

    /**
     * Returns if the page allows a mode. Access rights and user state
     * are taken into consideration.
     *
     * @see $supportedModes
     *
     * @param integer $mode  The mode to check for.
     *
     * @return boolean  True if the mode is allowed.
     */
    function allows($mode)
    {
        global $browser;

        $pagePerms = $this->getPermissions();

        switch ($mode) {
        case Wicked::MODE_CREATE:
            // Special mode for pages that don't exist yet - generic
            // to all pages.
            if ($browser->isRobot()) {
                return false;
            }

            if ($GLOBALS['registry']->isAdmin()) {
                return true;
            }

            $permName = 'wicked:pages';
            $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

            if ($perms->exists($permName)) {
                return $perms->getPermissions($permName) & Horde_Perms::EDIT;
            } else {
                return $GLOBALS['registry']->getAuth();
            }
            break;

        case Wicked::MODE_EDIT:
            if ($browser->isRobot()) {
                return false;
            }

            if ($GLOBALS['registry']->isAdmin()) {
                return true;
            }

            if (($pagePerms & Horde_Perms::EDIT) == 0) {
                return false;
            }
            break;

        case Wicked::MODE_REMOVE:
            if ($browser->isRobot()) {
                return false;
            }

            if ($GLOBALS['registry']->isAdmin()) {
                return true;
            }

            if (($pagePerms & Horde_Perms::DELETE) == 0) {
                return false;
            }
            break;

        // All other modes require READ permissions.
        default:
            if ($GLOBALS['registry']->isAdmin()) {
                return true;
            }

            if (($pagePerms & Horde_Perms::READ) == 0) {
                return false;
            }
            break;
        }

        return $this->supports($mode);
    }

    /**
     * See if the page supports a particular mode.
     * @see $supportedModes
     *
     * @param integer $mode      Which mode to check for
     *
     * @return boolean            True or false
     */
    function supports($mode)
    {
        return !empty($this->supportedModes[$mode]);
    }

    /**
     * Get the page we are currently on.
     *
     * @return  Returns a Page or PEAR_Error.
     */
    function getCurrentPage()
    {
        return Wicked_Page::getPage(rtrim(Horde_Util::getFormData('page'), '/'),
                             Horde_Util::getFormData('version'),
                             Horde_Util::getFormData('referrer'));
    }

    /**
     * Get the page we are currently on.
     *
     * @return mixed  Returns a Page or PEAR_Error.
     */
    function getPage($pagename, $pagever = null, $referrer = null)
    {
        global $conf, $notification, $wicked;

        if (empty($pagename)) {
            $pagename = 'WikiHome';
        }

        $file = WICKED_BASE . '/lib/Page/' . basename($pagename) . '.php';
        if ($pagename == basename($pagename) &&
            file_exists($file)) {
            require_once $file;
            return new $pagename($referrer);
        }

        require_once WICKED_BASE . '/lib/Page/StandardPage.php';

        /* If we have a version, but it is actually the most recent version,
         * ignore it. */
        if (!empty($pagever)) {
            $page = new StandardPage($pagename, false, null);
            if ($page->isValid() && $page->version() == $pagever) {
                return $page;
            }
            require_once WICKED_BASE . '/lib/Page/StandardPage/StdHistoryPage.php';
            return new StdHistoryPage($pagename, $pagever);
        }

        $page = new StandardPage($pagename);
        if ($page->isValid() || !$page->allows(Wicked::MODE_EDIT)) {
            return $page;
        }

        require_once WICKED_BASE . '/lib/Page/AddPage.php';
        return new AddPage($pagename);
    }

    function versionCreated()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function formatVersionCreated()
    {
        global $prefs;
        $v = $this->versionCreated();
        if (is_a($v, 'PEAR_Error') || !$v) {
            return _("Never");
        } else {
            return strftime($prefs->getValue('date_format'), $v);
        }
    }

    function author()
    {
        if (isset($this->_page['change_author'])) {
            $modify = $this->_page['change_author'];
        } else {
            return _("Guest");
        }

        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($modify);
        $name = $identity->getValue('fullname');
        if (!empty($name)) {
            $modify = $name;
        }

        return $modify;
    }

    function hits()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function version()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Retrieve the previous version number for this page
     *
     * @return mixed A string containing the previous version or null if this
     *               is the first version.
     */
    function previousVersion()
    {
        global $wicked;

        $res = $this->version();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $history = $wicked->getHistory($this->pageName());
        if (is_a($history, 'PEAR_Error')) {
            return $history;
        }

        if (count($history) == 0) {
            return null;
        }
        if ($this->isOld()) {
            for ($i = 0; $i < count($history); $i++) {
                $checkver = sprintf('%d.%d', $history[$i]['page_majorversion'],
                                    $history[$i]['page_minorversion']);
                if ($checkver == $this->version()) {
                    if ($i + 1 < count($history)) {
                        $i++;
                        break;
                    } else {
                        return null;
                    }
                }
            }

            if ($i == count($history)) {
                return null;
            }
        } else {
            $i = 0;
        }

        return sprintf('%d.%d', $history[$i]['page_majorversion'],
                       $history[$i]['page_minorversion']);
    }

    function isOld()
    {
        return false;
    }

    /**
     * Render this page in Display mode. You really must override this
     * function if your page is to be anything like a real page.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function display()
    {
        $inner = $this->displayContents(false);
        if (is_a($inner, 'PEAR_Error')) {
            return $inner;
        }
        require WICKED_TEMPLATES . '/display/title.inc';
        echo $inner;
    }

    /**
     * Perform any pre-display checks for permissions, searches,
     * etc. Called before any output is sent so the page can do
     * redirects. If the page wants to take control of flow from here,
     * it can, and is entirely responsible for handling the user
     * (should call exit after redirecting, for example).
     *
     * $param integer $mode    The page render mode.
     * $param array   $params  Any page parameters.
     */
    function preDisplay($mode, $params)
    {
    }

    /**
     * Render this page for displaying in a block. You really must override
     * this function if your page is to be anything like a real page.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function block()
    {
        return $this->displayContents(true);
    }

    function displayContents($isBlock)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Render this page in Remove mode.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function remove()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Render this page in History mode.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function history()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Render this page in Diff mode.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function diff()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function &getProcessor($output_format = 'Xhtml')
    {
        if (isset($this->_proc)) {
            return $this->_proc;
        }

        global $wicked, $conf;

        $view_url = Horde_Util::addParameter(Wicked::url('%s', false, -1), 'referrer', $this->pageName(), false);
        /* Attach the session parameter manually, because we don't want the
         * parameters to be encoded, but don't want full URLs either. */
        if (empty($GLOBALS['conf']['session']['use_only_cookies']) &&
            !isset($_COOKIE[session_name()])) {
            $view_url = Horde_Util::addParameter($view_url, session_name(), session_id(), false);
        }
        $view_url = str_replace(array(urlencode('%s'), urlencode('/')), array('%s', '%' . urlencode('/')), $view_url);

        /* Make sure we have a valid wiki format */
        $format = $conf['wicked']['format'];
        if (!in_array($format, array('BBCode', 'Cowiki', 'Creole', 'Mediawiki', 'Tiki'))) {
            $format = 'Default';
        }

        /* Create format-specific Text_Wiki object */
        $class = 'Text_Wiki_' . $format;
        require_once 'Text/Wiki/' . $format . '.php';
        $this->_proc = new $class();

        /* Use a non-printable delimiter character that is still a valid UTF-8
         * character. See http://pear.php.net/bugs/bug.php?id=12490. */
        $this->_proc->delim = chr(1);

        if ($output_format == 'Xhtml') {
            /* Override rules */
            $this->_proc->insertRule('Image2', 'Image');
            $this->_proc->deleteRule('Image');
            if ($format == 'Default') {
                $this->_proc->insertRule('Code2', 'Code');
                $this->_proc->deleteRule('Code');

                $this->_proc->insertRule('Wikilink2', 'Wikilink');
                $this->_proc->deleteRule('Wikilink');

                $this->_proc->insertRule('Freelink2', 'Freelink');
                $this->_proc->deleteRule('Freelink');

                $this->_proc->insertRule('RegistryLink', 'Toc');
                $this->_proc->insertRule('Attribute', 'RegistryLink');

                $this->_proc->deleteRule('Include');
                $this->_proc->deleteRule('Embed');
            }

            $this->_proc->setFormatConf('Xhtml', 'charset', 'UTF-8');
            $this->_proc->setFormatConf('Xhtml', 'translate', HTML_SPECIALCHARS);
            $create = $this->allows(Wicked::MODE_CREATE) ? 1 : 0;
            $linkConf = array(
                'pages' => $wicked->getPages(),
                'view_url' => $view_url,
                'new_url' => $create ? $view_url : false,
                'new_text_pos' => false,
                'css_new' => 'newpage',
                'ext_chars' => true,
            );

            $this->_proc->setRenderConf('Xhtml', 'Wikilink', $linkConf);
            $this->_proc->setRenderConf('Xhtml', 'Freelink', $linkConf);
            $this->_proc->setRenderConf('Xhtml', 'Wikilink2', $linkConf);
            $this->_proc->setRenderConf('Xhtml', 'Freelink2', $linkConf);
            $this->_proc->setRenderConf('Xhtml', 'Toc',
                                        array('title' => '<h2>' . _("Table of Contents") . '</h2>'));
            $this->_proc->setRenderConf('Xhtml', 'Table',
                                        array('css_table' => 'table',
                                              'css_td' => 'table-cell',
                                              'css_th' => 'table-cell'));

            $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Text_Wiki_Render_Xhtml/', WICKED_BASE . '/lib/Text_Wiki/Render/Xhtml'));
        }

        $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Text_Wiki_Parse/', WICKED_BASE . '/lib/Text_Wiki/Parse/' . $format));

        return $this->_proc;
    }

    function render($mode, $params = null)
    {
        switch ($mode) {
        case Wicked::MODE_CONTENT:
            return $this->content($params);

        case Wicked::MODE_DISPLAY:
            return $this->display($params);

        case Wicked::MODE_BLOCK:
            return $this->block($params);

        case Wicked::MODE_REMOVE:
            return $this->remove();

        case Wicked::MODE_HISTORY:
            return $this->history();

        case Wicked::MODE_DIFF:
            return $this->diff($params);

        default:
            return PEAR::raiseError(_("Unsupported"));
        }
    }

    function isLocked()
    {
        return false;
    }

    function lock()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function unlock()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function updateText($newtext, $changelog, $minorchange)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function getText()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function pageName()
    {
        return null;
    }

    function referrer()
    {
        return null;
    }

    function pageUrl($linkpage = null, $actionId = null)
    {
        $params = array('page' => $this->pageName());
        if ($this->referrer()) {
            $params['referrer'] = $this->referrer();
        }
        if ($actionId) {
            $params['actionID'] = $actionId;
        }

        if (!$linkpage) {
            $url = Wicked::url($this->pageName());
            unset($params['page']);
        } else {
            $url = Horde::url($linkpage);
        }

        return Horde_Util::addParameter($url, $params);
    }

    function pageTitle()
    {
        return $this->pageName();
    }

    function handleAction()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

}
