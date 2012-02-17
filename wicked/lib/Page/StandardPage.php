<?php
/**
 * Wicked Page class for most pages.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class Wicked_Page_StandardPage extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_DISPLAY => true,
        Wicked::MODE_EDIT => true,
        Wicked::MODE_REMOVE => true,
        Wicked::MODE_HISTORY => true,
        Wicked::MODE_DIFF => true);

    /**
     * A Horde_Locks instance for un-/locking this page.
     *
     * @var Horde_Lock
     */
    protected $_locks = null;

    /**
     * Lock information if this page is currently locked.
     *
     * @var array
     */
    protected $_lock = null;

    /**
     * Constructs a standard page class to represent a wiki page.
     *
     * @param string $pagename The name of the page to represent.
     */
    public function __construct($pagename)
    {
        if (is_array($pagename)) {
            $this->_page = $pagename;
            return;
        }

        $page = null;
        try {
            $page = $GLOBALS['wicked']->retrieveByName($pagename);
        } catch (Wicked_Exception $e) {
            // If we can't load $pagename, see if there's default data for it.
            // Protect against directory traversion.
            $pagepath = realpath(WICKED_BASE . '/data/'
                                 . $GLOBALS['conf']['wicked']['format']);
            $pagefile = realpath($pagepath . '/' . $pagename);
            if ($pagefile &&
                Horde_String::common($pagefile, $pagepath) == $pagepath &&
                substr($pagename, 0, 1) != '.' &&
                file_exists($pagefile)) {
                $text = file_get_contents($pagefile);
                try {
                    $GLOBALS['wicked']->newPage($pagename, $text);
                    try {
                        $page = $GLOBALS['wicked']->retrieveByName($pagename);
                    } catch (Wicked_Exception $e) {
                        $GLOBALS['notification']->push(sprintf(_("Unable to create %s"), $pagename), 'horde.error');
                    }
                } catch (Wicked_Exception $e) {}
            }
        }

        if ($page) {
            $this->_page = $page;
        } else {
            if ($pagename == 'Wiki/Home') {
                $GLOBALS['notification']->push(_("Unable to create Wiki/Home. The wiki is not configured."), 'horde.error');
            }
            $this->_page = array();
        }

        // Make sure 'wicked' permission exists. Set reasonable defaults if
        // necessary.
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        $corePerms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
        if (!$perms->exists('wicked')) {
            $perm = $corePerms->newPermission('wicked');
            $perm->addGuestPermission(Horde_Perms::SHOW | Horde_Perms::READ, false);
            $perm->addDefaultPermission(Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT | Horde_Perms::DELETE, false);
            $perms->addPermission($perm);
        }

        // Make sure 'wicked:pages' exists. Copy from 'wicked' if it does not
        // exist.
        if (!$perms->exists('wicked:pages')) {
            $perm = $corePerms->newPermission('wicked:pages');
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

        if ($GLOBALS['conf']['lock']['driver'] != 'none') {
            $this->supportedModes[Wicked::MODE_LOCKING] = $this->supportedModes[Wicked::MODE_UNLOCKING] = true;
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
    public function allows($mode)
    {
        switch ($mode) {
        case Wicked::MODE_EDIT:
            if ($this->isLocked()) {
                return Wicked::lockUser() == $this->_lock['lock_owner'];
            }
            break;

        case Wicked::MODE_LOCKING:
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

        case Wicked::MODE_UNLOCKING:
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

    /**
     * @throws Wicked_Exception
     */
    public function displayContents($isBlock)
    {
        $wiki = $this->getProcessor();
        $text = $wiki->transform($this->getText());
        $attachments = array();

        if (!$isBlock) {
            $pageId = $GLOBALS['wicked']->getPageId($this->pageName());
            $attachments = $GLOBALS['wicked']->getAttachedFiles($pageId);
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
     *
     * @throws Wicked_Exception
     */
    public function history()
    {
        try {
            $summaries = $GLOBALS['wicked']->getHistory($this->pageName());
        } catch (Wicked_Exception $e) {
            $GLOBALS['notification']->push('Error retrieving histories : ' . $e->getMessage(), 'horde.error');
            throw $e;
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
            $page = new Wicked_Page_StandardHistoryPage($page);
            require WICKED_TEMPLATES . '/history/summary.inc';
        }

        // Footer.
        require WICKED_TEMPLATES . '/history/footer.inc';
    }

    public function isLocked($owner = null)
    {
        if (empty($this->_lock)) {
            return false;
        }
        if (is_null($owner)) {
            return true;
        }
        return $owner != $this->_lock['lock_owner'];
    }

    /**
     * @throws Wicked_Exception
     */
    public function lock()
    {
        if ($this->_locks) {
            $id = $this->_locks->setLock(Wicked::lockUser(), 'wicked', $this->pageName(), $GLOBALS['conf']['wicked']['lock']['time'] * 60, Horde_Lock::TYPE_EXCLUSIVE);
            if ($id) {
                $this->_lock = $this->_locks->getLockInfo($id);
            } else {
                throw new Wicked_Exception(_("The page is already locked."));
            }
        }
    }

    public function unlock()
    {
        if ($this->_locks && $this->_lock) {
            $this->_locks->clearLock($this->_lock['lock_id']);
            unset($this->_lock);
        }
    }

    public function getLockRequestor()
    {
        $requestor = $this->_lock['lock_owner'];
        if ($requestor) {
            $name = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Identity')
                ->create($requestor)
                ->getValue('fullname');
            if (!strlen($name)) {
                $name = $requestor;
            }
            return $name;
        }
        return _("a guest");
    }

    public function getLockTime()
    {
        $time = ceil(($this->_lock['lock_expiry_timestamp'] - time()) / 60);
        return sprintf(ngettext("%d minute", "%d minutes", $time), $time);
    }
    
    /**
     * @throws Wicked_Exception
     */
    public function updateText($newtext, $changelog)
    {
        $version = $this->version();
        $result = $GLOBALS['wicked']->updateText($this->pageName(), $newtext,
                                                 $changelog);

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

    public function pageID()
    {
        return isset($this->_page['page_id']) ? $this->_page['page_id'] : '';
    }

    public function pageName()
    {
        return isset($this->_page['page_name'])
            ? $this->_page['page_name']
            : '';
    }

    public function getText()
    {
        return isset($this->_page['page_text'])
            ? $this->_page['page_text']
            : '';
    }

    public function versionCreated()
    {
        return isset($this->_page['version_created'])
            ? $this->_page['version_created']
            : '';
    }

    public function hits()
    {
        return !empty($this->_page['page_hits'])
            ? $this->_page['page_hits']
            : 0;
    }

    public function changeLog()
    {
        return $this->_page['change_log'];
    }

    public function version()
    {
        if (isset($this->_page['page_version'])) {
            return $this->_page['page_version'];
        } else {
            return '';
        }
    }

    public function diff($version)
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
    public function getDiff($version, $renderer = 'unified')
    {
        if (is_null($version)) {
            $old_page_text = '';
        } else {
            $old_page = $this->getPage($this->pageName(), $version);
            $old_page_text = $old_page->getText();
        }
        $diff = new Horde_Text_Diff('auto',
                                    array(explode("\n", $old_page_text),
                                          explode("\n", $this->getText())));
        $class = 'Horde_Text_Diff_Renderer_' . Horde_String::ucfirst($renderer);
        $renderer = new $class();
        return $renderer->render($diff);
    }

}
