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
class Wicked_Page
{
    const MATCH_LEFT = 1;
    const MATCH_RIGHT = 2;
    const MATCH_ENDS = 3;
    const MATCH_ANY = 4;

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
        return !empty($this->_page);
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
     * Returns the page we are currently on.
     *
     * @return Wicked_Page  The current page.
     * @throws Wicked_Exception
     */
    function getCurrentPage()
    {
        return Wicked_Page::getPage(rtrim(Horde_Util::getFormData('page'), '/'),
                                    Horde_Util::getFormData('version'),
                                    Horde_Util::getFormData('referrer'));
    }

    /**
     * Returns the requested page.
     *
     * @return Wicked_Page  The requested page.
     * @throws Wicked_Exception
     */
    function getPage($pagename, $pagever = null, $referrer = null)
    {
        global $conf, $notification, $wicked;

        if (empty($pagename)) {
            $pagename = 'WikiHome';
        }

        $classname = 'Wicked_Page_' . $pagename;
        if ($pagename == basename($pagename) && class_exists($classname)) {
            return new $classname($referrer);
        }

        /* If we have a version, but it is actually the most recent version,
         * ignore it. */
        if (!empty($pagever)) {
            $page = new Wicked_Page_StandardPage($pagename, false, null);
            if ($page->isValid() && $page->version() == $pagever) {
                return $page;
            }
            return new Wicked_Page_StandardHistoryPage($pagename, $pagever);
        }

        $page = new Wicked_Page_StandardPage($pagename);
        if ($page->isValid() || !$page->allows(Wicked::MODE_EDIT)) {
            return $page;
        }

        return new Wicked_Page_AddPage($pagename);
    }

    function versionCreated()
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    function formatVersionCreated()
    {
        try {
            $v = $this->versionCreated();
            if ($v) {
                return strftime($GLOBALS['prefs']->getValue('date_format'), $v);
            }
        } catch (Wicked_Exception $e) {}
        return _("Never");
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
        throw new Wicked_Exception(_("Unsupported"));
    }

    function version()
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    /**
     * Returns the previous version number for this page.
     *
     * @return string  A string containing the previous version or null if this
     *                 is the first version.
     * @throws Wicked_Exception
     */
    function previousVersion()
    {
        global $wicked;

        $this->version();
        $history = $wicked->getHistory($this->pageName());

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
     * Renders this page in display mode.
     *
     * This must be overridden if the page is to be anything like a real page.
     *
     * @throws Wicked_Exception
     */
    function display()
    {
        // Get content first, it might throw an exception.
        $inner = $this->displayContents(false);
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
     * Renders this page for displaying in a block.
     *
     * This must be overridden if the page is to be anything like a real page.
     *
     * @return string  The content.
     * @throws Wicked_Exception
     */
    function block()
    {
        return $this->displayContents(true);
    }

    function displayContents($isBlock)
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    /**
     * Renders this page in remove mode.
     */
    function remove()
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    /**
     * Renders this page in history mode.
     */
    function history()
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    /**
     * Renders this page in diff mode.
     */
    function diff()
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    function &getProcessor($output_format = 'Xhtml')
    {
        if (isset($this->_proc)) {
            return $this->_proc;
        }

        global $wicked, $conf;

        $view_url = Wicked::url('%s')
            ->setRaw(true)
            ->add('referrer', $this->pageName());
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
            throw new Wicked_Exception(_("Unsupported"));
        }
    }

    function isLocked()
    {
        return false;
    }

    function lock()
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    function unlock()
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    function updateText($newtext, $changelog, $minorchange)
    {
        throw new Wicked_Exception(_("Unsupported"));
    }

    function getText()
    {
        throw new Wicked_Exception(_("Unsupported"));
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
        throw new Wicked_Exception(_("Unsupported"));
    }

}
