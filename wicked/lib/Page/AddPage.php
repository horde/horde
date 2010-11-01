<?php
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
class Wicked_Page_AddPage extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_DISPLAY => true);

    /**
     * The page to confirm creation of.
     *
     * @var string
     */
    protected $_newpage;

    /**
     * Cached search results.
     * @var array
     */
    protected $_results;

    public function __construct($newpage)
    {
        $this->_newpage = $newpage;
        $this->_results = $GLOBALS['wicked']->searchTitles($newpage);
    }

    /**
     * Bail out if there's no page name.
     */
    public function preDisplay()
    {
        if (!strlen($this->referrer())) {
            $GLOBALS['notification']->push(_("Page name must not be empty"));
            Wicked::url('', true)->redirect();
        }
    }

    /**
     * Renders this page in display mode.
     *
     * @throws Wicked_Exception
     */
    public function display()
    {
        try {
            $templates = $GLOBALS['wicked']->getMatchingPages('Template', Wicked_Page::MATCH_ENDS);
        } catch (Wicked_Exception $e) {
            $GLOBALS['notification']->push(sprintf(_("Error retrieving templates: %s"),
                                                   $e->getMessage()), 'horde.error');
            throw $e;
        }

        $search_results = null;
        if ($this->_results) {
            $template = $GLOBALS['injector']->createInstance('Horde_Template');
            $pages = array();
            foreach ($this->_results as $page) {
                if (!empty($page['page_history'])) {
                    $page = new Wicked_Page_StandardHistoryPage($page);
                } else {
                    $page = new Wicked_Page_StandardPage($page);
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
    }

    public function pageName()
    {
        return 'AddPage';
    }

    public function pageTitle()
    {
        return sprintf(_("Add Page: %s"), $this->referrer());
    }

    public function referrer()
    {
        return $this->_newpage;
    }

}
