<?php
/**
 * Wicked SearchAll class.
 *
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Ben Chavet <ben@horde.org>
 * @package Wicked
 */
class Wicked_Page_Search extends Wicked_Page {

    /**
     * Display modes supported by this page.
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_CONTENT => true,
        Wicked::MODE_DISPLAY => true
    );

    /**
     * Cached search results.
     *
     * @var array
     */
    protected $_results = array();

    /**
     * Renders this page in content mode.
     *
     * @param string $searchtext  The title to search for.
     *
     * @return string  The page content.
     */
    public function content($searchtext = '')
    {
        if (empty($searchtext)) {
            return array();
        }
        return array(
            'titles' => $GLOBALS['wicked']->searchTitles($searchtext),
            'pages' => $GLOBALS['wicked']->searchText($searchtext, false)
        );
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
    public function preDisplay($mode, $params)
    {
        $this->_results = $this->content($params);
    }

    /**
     * Renders this page in display mode.
     *
     * @param string $searchtext  The title to search for.
     *
     * @throws Wicked_Exception
     */
    public function display($searchtext)
    {
        global $notification;

        if (!$searchtext) {
            require WICKED_TEMPLATES . '/pagelist/search.inc';
            require WICKED_TEMPLATES . '/pagelist/footer.inc';
            return true;
        }

        /* Prepare exact match section */
        $exact = array();
        $page = new Wicked_Page_StandardPage($searchtext);
        if ($GLOBALS['wicked']->pageExists($searchtext)) {
            $exact[] = array(
                'author' => $page->author(),
                'created' => $page->formatVersionCreated(),
                'name' => $page->pageUrl()->link()
                    . htmlspecialchars($page->pageName()) . '</a>',
                'timestamp' => $page->versionCreated(),
                'version' => $page->pageUrl()->link() . $page->version() . '</a>',
            );
        } else {
            $exact[] = array(
                'author' => '',
                'created' => '',
                'name' => htmlspecialchars($searchtext),
                'context' => sprintf(
                    _("%s does not exist. You can create it now."),
                    '<strong>' . htmlspecialchars($searchtext) . '</strong>'
                ),
            );
        }

        /* Prepare page title matches */
        $titles = array();
        foreach ($this->_results['titles'] as $page) {
            if (!empty($page['page_history'])) {
                $page = new Wicked_Page_StandardHistoryPage($page);
            } else {
                $page = new Wicked_Page_StandardPage($page);
            }

            $titles[] = array(
                'author' => $page->author(),
                'created' => $page->formatVersionCreated(),
                'name' => $page->pageUrl()->link()
                    . htmlspecialchars($page->pageName()) . '</a>',
                'timestamp' => $page->versionCreated(),
                'version' => $page->pageUrl()->link() . $page->version() . '</a>',
            );
        }

        /* Prepare page text matches */
        $pages = array();
        foreach ($this->_results['pages'] as $page) {
            if (!empty($page['page_history'])) {
                $page = new Wicked_Page_StandardHistoryPage($page);
            } else {
                $page = new Wicked_Page_StandardPage($page);
            }

            $pages[] = array(
                'author' => $page->author(),
                'context' => $this->getContext($page, $searchtext),
                'created' => $page->formatVersionCreated(),
                'name' => $page->pageUrl()->link()
                    . htmlspecialchars($page->pageName()) . '</a>',
                'timestamp' => $page->versionCreated(),
                'version' => $page->pageUrl()->link() . $page->version() . '</a>',
            );
        }

        $GLOBALS['page_output']->addScriptFile('tables.js', 'horde');

        $header = $GLOBALS['injector']->createInstance('Horde_View');
        $header->th_page = _("Page");
        $header->th_version = _("Current Version");
        $header->th_author = _("Last Author");
        $header->th_updated = _("Last Update");

        $view = $GLOBALS['injector']->createInstance('Horde_View');

        // Show search form and page header.
        require WICKED_TEMPLATES . '/pagelist/search.inc';

        // Show exact match.
        $header->title = _("Exact Match");
        $view->pages = $exact;
        echo $header->render('pagelist/results_header');
        echo $view->render('pagelist/pagelist');
        require WICKED_TEMPLATES . '/pagelist/results_footer.inc';

        // Show page title matches.
        $header->title = _("Page Title Matches");
        $view->pages = $titles;
        echo $header->render('pagelist/results_header');
        echo $view->render('pagelist/pagelist');
        require WICKED_TEMPLATES . '/pagelist/results_footer.inc';

        // Show page text matches.
        $header->title = _("Page Text Matches");
        $view->pages = $pages;
        echo $header->render('pagelist/results_header');
        echo $view->render('pagelist/pagelist');
        require WICKED_TEMPLATES . '/pagelist/results_footer.inc';
    }

    public function getContext($page, $searchtext)
    {
        try {
            $text = strip_tags($page->displayContents(false));
        } catch (Wicked_Exception $e) {
            $text = $page->getText();
        }
        if (preg_match('/.{0,100}' . preg_quote($searchtext, '/') . '.{0,100}/i', $text, $context)) {
            return trim(preg_replace('/' . preg_quote($searchtext, '/') . '/i', '<span class="match">' . htmlspecialchars($searchtext) . '</span>', htmlspecialchars($context[0])));
        }
        return '';
    }

    public function pageName()
    {
        return 'Search';
    }

    public function pageTitle()
    {
        return _("Search");
    }

}
