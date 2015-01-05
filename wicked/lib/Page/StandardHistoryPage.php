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
 * Page class for old versions of pages.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */
class Wicked_Page_StandardHistoryPage extends Wicked_Page_StandardPage
{
    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_DISPLAY => true,
        Wicked::MODE_EDIT => false,
        Wicked::MODE_REMOVE => true,
        Wicked::MODE_HISTORY => true,
        Wicked::MODE_DIFF => true,
        Wicked::MODE_LOCKING => false,
        Wicked::MODE_UNLOCKING => false);

    /**
     * Construct a standard history page class to represent an old
     * version of a wiki page.
     *
     * @param string  $pagename    The name of the page to load.
     * @param integer $version     The version of the page to load.
     *
     * @throws Wicked_Exception
     */
    public function __construct($pagename, $version = null)
    {
        if (empty($version)) {
            parent::__construct($pagename);
            return;
        }

        // Retrieve the version.
        $pages = $GLOBALS['wicked']->retrieveHistory($pagename, $version);

        // If it didnt find one, return an error.
        if (empty($pages[0])) {
            throw new Wicked_Exception(_("History page not found"));
        }

        $this->_page = $pages[0];
    }

    public function isOld()
    {
        return true;
    }

    public function pageUrl($linkpage = null, $actionId = null)
    {
        return parent::pageUrl($linkpage, $actionId)->add('version', $this->version());
    }

}
