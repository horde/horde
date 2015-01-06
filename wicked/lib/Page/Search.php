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
 * @author   Ben Chavet <ben@horde.org>
 * @package  Wicked
 */

/**
 * Displays and handles search forms and results.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Ben Chavet <ben@horde.org>
 * @package  Wicked
 */
class Wicked_Page_Search extends Wicked_Page
{
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
     * @return string  The content.
     * @throws Wicked_Exception
     */
    public function display($searchtext)
    {
        global $injector, $notification, $page_output, $wicked;

        $view = $injector->createInstance('Horde_View');

        if (!$searchtext) {
            return $view->render('pagelist/search')
                . $view->render('pagelist/footer');
        }

        /* Prepare exact match section */
        $exact = array();
        $page = new Wicked_Page_StandardPage($searchtext);
        if ($wicked->pageExists($searchtext)) {
            $exact[] = $page->toView();
        } else {
            $exact[] = (object)array(
                'author' => '',
                'context' => Wicked::url($searchtext, false)
                    ->link(array(
                        'title' => sprintf(_("Create %s"), $searchtext)
                    ))
                    . sprintf(_("%s does not exist. You can create it now."), '<strong>' . htmlspecialchars($searchtext) . '</strong>')
                    . '</a>',
                'date' => '',
                'name' => htmlspecialchars($searchtext),
                'timestamp' => 0,
                'version' => '',
                'url' => Wicked::url($searchtext, false)
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

            $titles[] = $page->toView();
        }

        /* Prepare page text matches */
        $pages = array();
        foreach ($this->_results['pages'] as $page) {
            if (!empty($page['page_history'])) {
                $page = new Wicked_Page_StandardHistoryPage($page);
            } else {
                $page = new Wicked_Page_StandardPage($page);
            }
            $object = $page->toView();
            $object->context = $this->getContext($page, $searchtext);
            $pages[] = $object;
        }

        $page_output->addScriptFile('tables.js', 'horde');

        $header = $injector->createInstance('Horde_View');
        $header->th_page = _("Page");
        $header->th_version = _("Current Version");
        $header->th_author = _("Last Author");
        $header->th_updated = _("Last Update");

        $view = $injector->createInstance('Horde_View');

        // Show search form and page header.
        $content = $view->render('pagelist/search');

        // Show exact match.
        $header->title = _("Exact Match");
        $content .= $header->render('pagelist/results_header')
            . $view->renderPartial(
                'pagelist/page',
                array('collection' => $exact)
            )
            . $view->render('pagelist/results_footer');

        // Show page title matches.
        $header->title = _("Page Title Matches");
        $content .= $header->render('pagelist/results_header')
            . $view->renderPartial(
                'pagelist/page',
                array('collection' => $titles)
            )
            . $view->render('pagelist/results_footer');

        // Show page text matches.
        $header->title = _("Page Text Matches");
        $content .= $header->render('pagelist/results_header')
            . $view->renderPartial(
                'pagelist/page',
                array('collection' => $pages)
            )
            . $view->render('pagelist/results_footer');

        return $content;
    }

    public function getContext($page, $searchtext)
    {
        try {
            $text = html_entity_decode(strip_tags($page->displayContents(true)), ENT_QUOTES | ENT_XHTML, 'UTF-8');
        } catch (Wicked_Exception $e) {
            $text = $page->getText();
        }
        if (preg_match('/.{0,100}' . preg_quote($searchtext, '/') . '.{0,100}/i', $text, $context)) {
            return trim(preg_replace('/' . preg_quote($searchtext, '/') . '/i', '<span class="match">\0</span>', htmlspecialchars($context[0])));
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
