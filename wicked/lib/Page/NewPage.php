<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Wicked
 */

/**
 * Wicked NewPage class.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Wicked
 */
class Wicked_Page_NewPage extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_DISPLAY => true,
        Wicked::MODE_EDIT => true);

    /**
     * The page that we're creating.
     *
     * @var string
     */
    protected $_referrer = null;

    /**
     * Page template to use.
     *
     * @var string
     */
    protected $_template = null;

    public function __construct($referrer)
    {
        $this->_referrer = $referrer;
        $this->_template = Horde_Util::getFormData('template');
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
        if (!strlen($this->referrer())) {
            $GLOBALS['notification']->push(_("Page name must not be empty"));
            Wicked::url('', true)->redirect();
        }

        if (!$this->allows(Wicked::MODE_EDIT)) {
            Wicked::url($this->referrer(), true)->redirect();
        }
    }

    /**
     * Renders this page in display mode.
     *
     * @throws Wicked_Exception
     */
    public function display()
    {
        // Load the page template.
        if ($this->_template) {
            $page = Wicked_Page::getPage($this->_template);
            $page_text = $page->getText();
        } else {
            $page_text = '';
        }

        Horde::addInlineScript(array(
            'if (document.editform && document.editform.page_text) document.editform.changelog.page_text()'
        ), 'dom');

        require WICKED_TEMPLATES . '/edit/new.inc';
        return true;
    }

    public function pageName()
    {
        return 'NewPage';
    }

    public function pageTitle()
    {
        return _("New Page");
    }

    public function referrer()
    {
        return $this->_referrer;
    }

    public function handleAction()
    {
        global $notification, $wicked;

        if (!$this->allows(Wicked::MODE_EDIT)) {
            $notification->push(sprintf(_("You don't have permission to create \"%s\"."), $this->referrer()));
        } else {
            $text = Horde_Util::getPost('page_text');
            if (empty($text)) {
                $notification->push(_("Pages cannot be empty."), 'horde.error');
                return;
            }

            try {
                $result = $wicked->newPage($this->referrer(), $text);
                $notification->push(_("Page Created"), 'horde.success');
            } catch (Wicked_Exception $e) {
                $notification->push(sprintf(_("Create Failed: %s"),
                                            $e->getMessage()), 'horde.error');
            }
        }

        // Show the newly created page.
        Wicked::url($this->referrer(), true)->redirect();
    }

}
