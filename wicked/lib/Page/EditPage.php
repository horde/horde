<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Wicked

/**
 * Wicked EditPage class.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Wicked
 */
class Wicked_Page_EditPage extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_DISPLAY => true,
        Wicked::MODE_EDIT => true);

    /**
     * The page that we're editing.
     *
     * @var string
     */
    protected $_referrer = null;

    public function __construct($referrer)
    {
        $this->_referrer = $referrer;
        if ($GLOBALS['conf']['lock']['driver'] != 'none') {
            $this->supportedModes[Wicked::MODE_LOCKING] = $this->supportedModes[Wicked::MODE_UNLOCKING] = true;
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
        if ($mode == Wicked::MODE_EDIT) {
            $page = Wicked_Page::getPage($this->referrer());
            if ($page->isLocked(Wicked::lockUser())) {
                return false;
            }
        }
        return parent::allows($mode);
    }

    /**
     * Retrieve this user's permissions for the referring page.
     *
     * @return integer  The permissions bitmask.
     */
    public function getPermissions()
    {
        return parent::getPermissions($this->referrer());
    }

    /**
     * Send them back whence they came if they aren't allowed to edit
     * this page.
     */
    public function preDisplay()
    {
        if (!$this->allows(Wicked::MODE_EDIT)) {
            Wicked::url($this->referrer(), true)->redirect();
        }
        if ($this->allows(Wicked::MODE_LOCKING)) {
            $page = Wicked_Page::getPage($this->referrer());
            if ($page->isLocked()) {
                $page->unlock();
            }
            try {
                $page->lock();
            } catch (Wicked_Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("Page failed to lock: %s"), $e->getMessage()), 'horde.error');
            }
        }
    }

    /**
     * Renders this page in display mode.
     *
     * @throws Wicked_Exception
     */
    public function display()
    {
        $page = Wicked_Page::getPage($this->referrer());
        $page_text = Horde_Util::getFormData('page_text');
        if (is_null($page_text)) {
            $page_text = $page->getText();
        }
        require WICKED_TEMPLATES . '/edit/standard.inc';
    }

    public function pageName()
    {
        return 'EditPage';
    }

    public function pageTitle()
    {
        return _("Edit Page");
    }

    public function referrer()
    {
        return $this->_referrer;
    }

    public function isLocked()
    {
        $page = Wicked_Page::getPage($this->referrer());
        return $page->isLocked();
    }

    public function getLockRequestor()
    {
        $page = Wicked_Page::getPage($this->referrer());
        return $page->getLockRequestor();
    }

    public function getLockTime()
    {
        $page = Wicked_Page::getPage($this->referrer());
        return $page->getLockTime();
    }

    public function handleAction()
    {
        global $notification, $conf;

        $page = Wicked_Page::getPage($this->referrer());
        if (!$this->allows(Wicked::MODE_EDIT)) {
            $notification->push(sprintf(_("You don't have permission to edit \"%s\"."), $page->pageName()));
        } else {
            if (!empty($GLOBALS['conf']['wicked']['captcha']) &&
                !$GLOBALS['registry']->getAuth() &&
                (Horde_String::lower(Horde_Util::getFormData('wicked_captcha')) != Horde_String::lower(Wicked::getCAPTCHA()))) {
                $notification->push(_("Random string did not match."), 'horde.error');
                return;
            }
            $text = Horde_Util::getFormData('page_text');
            $changelog = Horde_Util::getFormData('changelog');
            if ($conf['wicked']['require_change_log'] && empty($changelog)) {
                $notification->push(_("You must provide a change log."), 'horde.error');
                Horde::addInlineScript(array(
                    'if (document.editform && document.editform.changelog) document.editform.changelog.focus()'
                ), 'dom');
                return;
            }
            $minorchange = Horde_Util::getFormData('minor');
            if (trim($text) == trim($page->getText())) {
                $notification->push(_("No changes made"), 'horde.warning');
            } else {
                $page->updateText($text, $changelog, $minorchange);
                $notification->push(_("Page Saved"), 'horde.success');
            }

            if ($page->allows(Wicked::MODE_UNLOCKING)) {
                $page->unlock();
            }
        }

        // Show the newly saved page.
        Wicked::url($this->referrer(), true)->redirect();
    }

}
