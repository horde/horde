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
 * StandardPage
 */
require_once WICKED_BASE . '/lib/Page/StandardPage.php';

/**
 * Wicked NewPage class.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Wicked
 */
class NewPage extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        WICKED_MODE_DISPLAY => true,
        WICKED_MODE_EDIT => true);

    /**
     * The page that we're creating.
     *
     * @var string
     */
    var $_referrer = null;

    /**
     * Page template to use.
     *
     * @var string
     */
    var $_template = null;

    function NewPage($referrer)
    {
        $this->_referrer = $referrer;
        $this->_template = Horde_Util::getFormData('template');
    }

    /**
     * Retrieve this user's permissions for the referring page.
     *
     * @return integer  The permissions bitmask.
     */
    function getPermissions()
    {
        return parent::getPermissions($this->referrer());
    }

    /**
     * Send them back whence they came if they aren't allowed to edit
     * this page.
     */
    function preDisplay()
    {
        if (!strlen($this->referrer())) {
            $GLOBALS['notification']->push(_("Page name must not be empty"));
            Wicked::url('', true)->redirect();
        }

        if (!$this->allows(WICKED_MODE_EDIT)) {
            Wicked::url($this->referrer(), true)->redirect();
        }
    }

    /**
     * Render this page in Display mode.
     *
     * @return mixed Returns true or PEAR_Error.
     */
    function display()
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

    function pageName()
    {
        return 'NewPage';
    }

    function pageTitle()
    {
        return _("New Page");
    }

    function referrer()
    {
        return $this->_referrer;
    }

    function handleAction()
    {
        global $notification, $wicked;

        if (!$this->allows(WICKED_MODE_EDIT)) {
            $notification->push(sprintf(_("You don't have permission to create \"%s\"."), $this->referrer()));
        } else {
            $text = Horde_Util::getPost('page_text');
            if (empty($text)) {
                $notification->push(_("Pages cannot be empty."), 'horde.error');
                return;
            }

            $result = $wicked->newPage($this->referrer(), $text);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("Create Failed: %s"),
                                            $result->getMessage()), 'horde.error');
            } else {
                $notification->push(_("Page Created"), 'horde.success');
            }
        }

        // Show the newly created page.
        Wicked::url($this->referrer(), true)->redirect();
    }

}
