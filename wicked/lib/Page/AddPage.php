<?php

require_once WICKED_BASE . '/lib/Page/StandardPage.php';

/**
 * Wicked AddPage class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class AddPage extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        Wicked::MODE_DISPLAY => true);

    /**
     * The page to confirm creation of.
     *
     * @var string
     */
    var $_newpage;

    /**
     * Cached search results.
     * @var array
     */
    var $_results;

    function AddPage($newpage)
    {
        $this->_newpage = $newpage;
        $this->_results = $GLOBALS['wicked']->searchTitles($newpage);
    }

    /**
     * Bail out if there's no page name.
     */
    function preDisplay()
    {
        if (!strlen($this->referrer())) {
            $GLOBALS['notification']->push(_("Page name must not be empty"));
            Wicked::url('', true)->redirect();
        }
    }

    /**
     * Render this page in Display mode.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function display()
    {
        $templates = $GLOBALS['wicked']->getMatchingPages('Template', WICKED_PAGE_MATCH_ENDS);
        if (is_a($templates, 'PEAR_Error')) {
            $GLOBALS['notification']->push(sprintf(_("Error retrieving templates: %s"),
                                                   $templates->getMessage()), 'horde.error');
            return $templates;
        }

        $search_results = null;
        if ($this->_results) {
            $template = $GLOBALS['injector']->createInstance('Horde_Template');
            $pages = array();
            foreach ($this->_results as $page) {
                if (!empty($page['page_history'])) {
                    $page = new StdHistoryPage($page);
                } else {
                    $page = new StandardPage($page);
                }

                $pages[] = array('author' => $page->author(),
                                 'created' => $page->formatVersionCreated(),
                                 'name' => $page->pageName(),
                                 'context' => false,
                                 'url' => $page->pageUrl(),
                                 'version' => $page->version());
            }
            $template->set('pages', $pages, true);
            $template->set('hits', false, true);
            $search_results = $template->fetch(WICKED_TEMPLATES . '/pagelist/pagelist.html');
        }

        require WICKED_TEMPLATES . '/edit/create.inc';
        return true;
    }

    function pageName()
    {
        return 'AddPage';
    }

    function pageTitle()
    {
        return sprintf(_("Add Page: %s"), $this->referrer());
    }

    function referrer()
    {
        return $this->_newpage;
    }

}
