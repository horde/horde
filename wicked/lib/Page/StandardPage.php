<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */

/**
 * Page class for regular pages.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */
class Wicked_Page_StandardPage extends Wicked_Page
{
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
                file_exists($pagefile) &&
                ($text = file_get_contents($pagefile))) {
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
        $view = $GLOBALS['injector']->createInstance('Horde_View');
        $view->text = $this->getProcessor()->transform($this->getText());
        if ($isBlock) {
            return $view->render('display/standard');
        }

        $view->showTools = true;
        if ($this->allows(Wicked::MODE_EDIT) &&
            !$this->isLocked(Wicked::lockUser())) {
            $view->edit = Horde::widget(array(
                'url' => Wicked::url('EditPage')
                    ->add('referrer', $this->pageName()),
                'title' => _("_Edit"),
                'class' => 'wicked-edit',
            ));
        }
        if ($this->isLocked()) {
            if ($this->allows(Wicked::MODE_UNLOCKING)) {
                $view->unlock = Horde::widget(array(
                    'url' => $this->pageUrl(null, 'unlock')->remove('version'),
                    'title' => _("Un_lock"),
                    'class' => 'wicked-unlock',
                ));
            }
        } else {
            if ($this->allows(Wicked::MODE_LOCKING)) {
                $view->lock = Horde::widget(array(
                    'url' => $this->pageUrl(null, 'lock')->remove('version'),
                    'title' => _("_Lock"),
                    'class' => 'wicked-lock',
                ));
            }
        }
        if ($this->allows(Wicked::MODE_REMOVE)) {
            $params = array('referrer' => $this->pageName());
            if ($this->isOld()) {
                $params['version'] = $this->version();
            }
            $view->remove = Horde::widget(array(
                'url' => Wicked::url('DeletePage')->add($params),
                'title' => _("_Delete"),
                'class' => 'wicked-delete',
            ));
        }
        if ($this->allows(Wicked::MODE_REMOVE) &&
            !$this->isLocked(Wicked::lockUser())) {
            $view->rename = Horde::widget(array(
                'url' => Wicked::url('MergeOrRename')
                    ->add('referrer', $this->pageName()),
                'title' => _("_Merge/Rename")
            ));
        }
        $view->backLinks = Horde::widget(array(
            'url' => Wicked::url('BackLinks')
                ->add('referrer', $this->pageName()),
            'title' => _("_Backlinks")
        ));
        $view->likePages = Horde::widget(array(
            'url' => Wicked::url('LikePages')
                ->add('referrer', $this->pageName()),
            'title' => _("S_imilar Pages")
        ));
        $view->attachedFiles = Horde::widget(array(
            'url' => Wicked::url('AttachedFiles')
                ->add('referrer', $this->pageName()),
            'title' => _("Attachments")
        ));
        if ($this->allows(Wicked::MODE_HISTORY)) {
            $view->changes = Horde::widget(array(
                'url' => $this->pageUrl('history.php')->remove('version'),
                'title' => _("Hi_story")
            ));
        }
        if ($GLOBALS['registry']->isAdmin()) {
            $permsurl = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/admin/perms/edit.php')
                ->add(array(
                    'category' => 'wicked:pages:' . $this->pageId(),
                    'autocreate' => 1,
                    'autocreate_copy' => 'wicked',
                    'autocreate_guest' => Horde_Perms::SHOW | Horde_Perms::READ,
                    'autocreate_default' => Horde_Perms::SHOW | Horde_Perms::READ | Horde_Perms::EDIT | Horde_Perms::DELETE
                ));
            $view->perms = Horde::widget(array(
                'url' => $permsurl,
                'target' => '_blank',
                'title' => _("Permissio_ns")
            ));
        }
        if ($histories = $GLOBALS['session']->get('wicked', 'history')) {
            $view->history = Horde::widget(array(
                'url' => '#',
                'onclick' => 'document.location = document.display.history[document.display.history.selectedIndex].value;',
                'title' => _("Ba_ck to")
            ));
            $view->histories = array();
            foreach ($histories as $history) {
                if (!strlen($history)) {
                    continue;
                }
                $view->histories[(string)Wicked::url($history)] = $history;
            }
        }
        $pageId = $GLOBALS['wicked']->getPageId($this->pageName());
        $attachments = $GLOBALS['wicked']->getAttachedFiles($pageId);
        if (count($attachments)) {
            $view->attachments = array();
            foreach ($attachments as $attachment) {
                $url = $GLOBALS['registry']
                    ->downloadUrl(
                        $attachment['attachment_name'],
                        array(
                            'page' => $this->pageName(),
                            'file' => $attachment['attachment_name'],
                            'version' => $attachment['attachment_version']
                        )
                    );
                $icon = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_MimeViewer')
                    ->getIcon(
                        Horde_Mime_Magic::filenameToMime(
                            $attachment['attachment_name']
                        )
                    );
                $view->attachments[] = Horde::link($url)
                    . '<img src="' . $icon . '" width="16" height="16" alt="" />&nbsp;'
                    . htmlspecialchars($attachment['attachment_name'])
                    . '</a>';
            }
        }
        $view->downloadPlain = Wicked::url($this->pageName())
            ->add(array('actionID' => 'export', 'format' => 'plain'))
            ->link()
            . _("Plain Text") . '</a>';
        $view->downloadHtml = Wicked::url($this->pageName())
            ->add(array('actionID' => 'export', 'format' => 'html'))
            ->link()
            . _("HTML") . '</a>';
        $view->downloadLatex = Wicked::url($this->pageName())
            ->add(array('actionID' => 'export', 'format' => 'tex'))
            ->link()
            . _("Latex") . '</a>';
        $view->downloadRest = Wicked::url($this->pageName())
            ->add(array('actionID' => 'export', 'format' => 'rst'))
            ->link()
            . _("reStructuredText") . '</a>';

        return $view->render('display/standard');
    }

    /**
     * Renders this page in History mode.
     *
     * @return string  The content.
     * @throws Wicked_Exception
     */
    public function history()
    {
        global $injector, $page_output;

        $page_output->addScriptFile('history.js');

        $view = $injector->createInstance('Horde_View');

        // Header.
        $view->formInput = Horde_Util::formInput();
        $view->name = $this->pageName();
        $view->pageLink = $this->pageUrl()->link()
            . htmlspecialchars($this->pageName()) . '</a>';
        $view->refreshLink = $this->pageUrl('history.php')->link()
            . Horde::img('reload.png', _("Reload History")) . '</a>';
        if ($this->allows(Wicked::MODE_REMOVE)) {
            $view->remove = Horde::img('delete.png', _("Delete Version"));
        }
        if ($this->allows(Wicked::MODE_EDIT) &&
            !$this->isLocked(Wicked::lockUser())) {
            $view->edit = Horde::img('edit.png', _("Edit Version"));
            $view->restore = Horde::img('restore.png', _("Restore Version"));
        }
        $content = $view->render('history/header');

        // First item is this page.
        $view->showRestore = false;
        $this->_setViewProperties($view, $this);
        $content .= $view->render('history/summary');

        // Now the rest of the histories.
        $view->showRestore = true;
        foreach ($GLOBALS['wicked']->getHistory($this->pageName()) as $page) {
            $page = new Wicked_Page_StandardHistoryPage($page);
            $this->_setViewProperties($view, $page);
            $view->pversion = $page->version();
            $content .= $view->render('history/summary');
        }

        // Footer.
        return $content . $view->render('history/footer');
    }

    protected function _setViewProperties($view, $page)
    {
        $view->displayLink = $page->pageUrl()
            ->link(array(
                'title' => sprintf(_("Display Version %s"), $page->version())
            ))
            . htmlspecialchars($page->version()) . '</a>';

        $text = sprintf(_("Delete Version %s"), $page->version());
        $view->deleteLink = Wicked::url('DeletePage')
            ->add(array(
                'referrer' => $page->pageName(),
                'version' => $page->version()
            ))
            ->link(array('title' => $text))
            . Horde::img('delete.png', $text) . '</a>';

        $text = sprintf(_("Edit Version %s"), $page->version());
        $view->editLink = Wicked::url('EditPage')
            ->add(array('referrer' => $page->pageName()))
            ->link(array('title' => $text))
            . Horde::img('edit.png', $text) . '</a>';

        $text = sprintf(_("Revert to version %s"), $page->version());
        $view->restoreLink = Wicked::url('RevertPage')
            ->add(array(
                'referrer' => $page->pageName(),
                'version' => $page->version()
            ))
            ->link(array('title' => $text))
            . Horde::img('restore.png', $text) . '</a>';

        $view->author = $page->author();
        $view->date = $page->formatVersionCreated();
        $view->version = $page->version();
        $view->changelog = $page->changeLog();
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

    /**
     * Renders this page in diff mode.
     *
     * @param string $version  The version to diff this page against.
     */
    public function diff($version)
    {
        $view = $GLOBALS['injector']->createInstance('Horde_View');
        $view->link = $this->pageUrl()->link()
            . htmlspecialchars($this->pageName())
            . '</a>';
        $view->version1 = $version;
        $view->version2 = $this->version();
        $view->diff = $this->getDiff($version, 'inline');
        echo $view->render('diff/diff');
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
