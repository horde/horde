<?php
/**
 * Wicked Page class for most pages.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class StandardPage extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        WICKED_MODE_DISPLAY => true,
        WICKED_MODE_EDIT => true,
        WICKED_MODE_REMOVE => true,
        WICKED_MODE_HISTORY => true,
        WICKED_MODE_DIFF => true);

    /**
     * A Horde_Locks instance for un-/locking this page.
     *
     * @var Horde_Lock
     */
    var $_locks = null;

    /**
     * Lock information if this page is currently locked.
     *
     * @var array
     */
    var $_lock = null;

    /**
     * Constructs a standard page class to represent a wiki page.
     *
     * @param string $pagename The name of the page to represent.
     */
    function StandardPage($pagename)
    {
        if (is_array($pagename)) {
            $this->_page = $pagename;
            return;
        }

        global $wicked, $notification;
        $page = $wicked->retrieveByName($pagename);

        // Make sure 'wicked' permission exists. Set reasonable defaults if
        // necessary.
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists('wicked')) {
            $perm = $perms->newPermission('wicked');
            $perm->addGuestPermission(Horde_Perms::SHOW | Horde_Perms::READ, false);
            $perm->addDefaultPermission(Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT | Horde_Perms::DELETE, false);
            $perms->addPermission($perm);
        }

        // Make sure 'wicked:pages' exists. Copy from 'wicked' if it does not
        // exist.
        if (!$perms->exists('wicked:pages')) {
            $perm = $perms->newPermission('wicked:pages');
            $copyFrom = $perms->getPermission('wicked');
            $perm->addGuestPermission($copyFrom->getGuestPermissions(), false);
            $perm->addDefaultPermission($copyFrom->getDefaultPermissions(), false);
            $perm->addCreatorPermission($copyFrom->getCreatorPermissions(), false);
            foreach ($copyFrom->getUserPermissions() as $user => $uperm) {
                $perm->addUserPermission($user, $uperm, false);
            }
            foreach ($copyFrom->getGroupPermissions() as $group => $gperm) {
                $perm->addGroupPermission($group, $gperm, false);
            }
            $perms->addPermission($perm);
        }

        // If we can't load $pagename, see if there's default data for it.
        if (is_a($page, 'PEAR_Error')) {
            $pagefile = WICKED_BASE . '/scripts/data/' . basename($pagename);
            if ($pagename == basename($pagename) &&
                file_exists($pagefile)) {
                $text = file_get_contents($pagefile);
                $result = $wicked->newPage($pagename, $text);
                if (!is_a($result, 'PEAR_Error')) {
                    $page = $wicked->retrieveByName($pagename);
                    if (is_a($page, 'PEAR_Error')) {
                        $notification->push(sprintf(_("Unable to create %s"), $pagename), 'horde.error');
                    }
                }
            }
        }

        if (is_a($page, 'PEAR_Error')) {
            if ($pagename == 'WikiHome') {
                $notification->push(_("Unable to create WikiHome. The wiki is not configured."), 'horde.error');
            }
            $this->_page = array();
        } else {
            $this->_page = $page;
        }

        if ($GLOBALS['conf']['lock']['driver'] != 'none') {
            $this->supportedModes[WICKED_MODE_LOCKING] = $this->supportedModes[WICKED_MODE_UNLOCKING] = true;
            $this->_locks = $GLOBALS['injector']->getInstance('Horde_Lock');
            $locks = $this->_locks->getLocks('wicked', $pagename, Horde_Lock::TYPE_EXCLUSIVE);
            if ($locks) {
                $this->_lock = reset($locks);
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
        switch ($mode) {
        case WICKED_MODE_EDIT:
            if ($this->isLocked()) {
                return Wicked::lockUser() == $this->_lock['lock_owner'];
            }
            break;

        case WICKED_MODE_LOCKING:
            if ($GLOBALS['browser']->isRobot()) {
                return false;
            }
            if ($GLOBALS['registry']->isAdmin()) {
                return true;
            }
            if (($this->getPermissions() & Horde_Perms::EDIT) == 0) {
                return false;
            }
            break;

        case WICKED_MODE_UNLOCKING:
            if ($GLOBALS['registry']->isAdmin()) {
                return true;
            }
            if ($this->_lock) {
                return Wicked::lockUser() == $this->_lock['lock_owner'];
            }
            return false;
        }
        return parent::allows($mode);
    }

    function displayContents($isBlock)
    {
        global $wicked;

        $wiki = $this->getProcessor();
        $text = $wiki->transform($this->getText());
        $attachments = array();

        if (!$isBlock) {
            $pageId = $wicked->getPageId($this->pageName());
            if (!is_a($pageId, 'PEAR_Error')) {
                $attachments = $wicked->getAttachedFiles($wicked->getPageId($this->pageName()));
                if (is_a($attachments, 'PEAR_Error')) {
                    $attachments = array();
                }
            }

            if (count($attachments)) {
                global $mime_drivers, $mime_drivers_map;
                $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
                extract($result);
            }
        }

        ob_start();
        require WICKED_TEMPLATES . '/display/standard.inc';
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    /**
     * Renders this page in History mode.
     */
    function history()
    {
        global $wicked, $notification;
        require_once WICKED_BASE . '/lib/Page/StandardPage/StdHistoryPage.php';

        $summaries = $wicked->getHistory($this->pageName());
        if (is_a($summaries, 'PEAR_Error')) {
            $notification->push('Error retrieving histories : ' . $summaries->getMessage(), 'horde.error');
            return $summaries;
        }

        // Header.
        $show_restore = !$this->isLocked();
        $allow_diff = true;
        $show_edit = true;
        require WICKED_TEMPLATES . '/history/header.inc';
        $style = 'text';

        // First item is this page.
        $show_restore = false;
        $page = $this;
        $i = 0;
        require WICKED_TEMPLATES . '/history/summary.inc';

        // Now the rest of the histories.
        $show_restore = !$this->isLocked();
        $show_edit = false;
        foreach ($summaries as $page) {
            $i++;
            $page = new StdHistoryPage($page);
            require WICKED_TEMPLATES . '/history/summary.inc';
        }

        // Footer.
        require WICKED_TEMPLATES . '/history/footer.inc';
    }

    function isLocked($owner = null)
    {
        if (empty($this->_lock)) {
            return false;
        }
        if (is_null($owner)) {
            return true;
        }
        return $owner != $this->_lock['lock_owner'];
    }

    function lock()
    {
        if ($this->_locks) {
            $id = $this->_locks->setLock(Wicked::lockUser(), 'wicked', $this->pageName(), $GLOBALS['conf']['wicked']['lock']['time'] * 60, Horde_Lock::TYPE_EXCLUSIVE);
            if ($id) {
                $this->_lock = $this->_locks->getLockInfo($id);
            } else {
                return PEAR::raiseError(_("The page is already locked."));
            }
        }
    }

    function unlock()
    {
        if ($this->_locks && $this->_lock) {
            $this->_locks->clearLock($this->_lock['lock_id']);
            unset($this->_lock);
        }
    }

    function getLockRequestor()
    {
        $requestor = $this->_lock['lock_owner'];
        if ($requestor) {
            $name = $GLOBALS['injector']
                ->getInstance('Horde_Prefs_Identity')
                ->getIdentity($requestor)
                ->getValue('fullname');
            if (!strlen($name)) {
                $name = $requestor;
            }
            return $name;
        }
        return _("a guest");
    }

    function getLockTime()
    {
        $time = ceil(($this->_lock['lock_expiry_timestamp'] - time()) / 60);
        return sprintf(ngettext("%d minute", "%d minutes", $time), $time);
    }

    function updateText($newtext, $changelog, $minorchange)
    {
        $version = $this->version();
        $result = $GLOBALS['wicked']->updateText($this->pageName(), $newtext,
                                                 $changelog, $minorchange);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $url = Wicked::url($this->pageName(), true, -1);
        $new_page = $this->getPage($this->pageName());

        $message = "Modified page: $url\n"
            . 'New Revision:  ' . $new_page->version() . "\n"
            . ($changelog ? 'Change log:  ' . $changelog . "\n" : '')
            . "\n"
            . $new_page->getDiff($version);
        Wicked::mail($message,
                     array('Subject' => '[' . $GLOBALS['registry']->get('name')
                           . '] changed: ' . $this->pageName()));

        $this->_page['page_text'] = $newtext;
    }

    function pageID()
    {
        return isset($this->_page['page_id']) ? $this->_page['page_id'] : '';
    }

    function pageName()
    {
        return isset($this->_page['page_name'])
            ? $this->_page['page_name']
            : '';
    }

    function getText()
    {
        return isset($this->_page['page_text'])
            ? $this->_page['page_text']
            : '';
    }

    function versionCreated()
    {
        return isset($this->_page['version_created'])
            ? $this->_page['version_created']
            : '';
    }

    function hits()
    {
        return !empty($this->_page['page_hits'])
            ? $this->_page['page_hits']
            : 0;
    }

    function changeLog()
    {
        return $this->_page['change_log'];
    }

    function version()
    {
        if (isset($this->_page['page_majorversion']) &&
            isset($this->_page['page_minorversion'])) {
            return $this->_page['page_majorversion'] . '.' .
                $this->_page['page_minorversion'];
        } else {
            return '';
        }
    }

    function diff($version)
    {
        require WICKED_TEMPLATES . '/diff/diff.inc';
    }

    /**
     * Produces a diff for this page.
     *
     * @param string $version   Previous version, or null if diffing with
     *                          `before the beginning' (empty).
     * @param string $renderer  The diff renderer.
     */
    function getDiff($version, $renderer = 'unified')
    {
        if (is_null($version)) {
            $old_page_text = '';
        } else {
            $old_page = $this->getPage($this->pageName(), $version);
            $old_page_text = $old_page->getText();
        }

        include_once 'Text/Diff.php';
        include_once 'Text/Diff/Renderer.php';
        include_once 'Text/Diff/Renderer/' . $renderer . '.php';

        $diff = new Text_Diff(explode("\n", $old_page_text),
                              explode("\n", $this->getText()));

        $class = 'Text_Diff_Renderer_' . $renderer;
        $renderer = new $class();
        return $renderer->render($diff);
    }

}
