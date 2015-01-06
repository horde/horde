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
 * Displays a form to add new pages.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */
class Wicked_Page_AddPage extends Wicked_Page
{
    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_DISPLAY => true
    );

    /**
     * The page to confirm creation of.
     *
     * @var string
     */
    protected $_newpage;

    /**
     * Cached search results.
     *
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
        global $injector, $page_output, $wicked;

        $view = $injector->createInstance('Horde_View');
        $view->action = Wicked::url('NewPage');
        $view->formInput = Horde_Util::formInput();
        $view->referrer = $this->referrer();
        $view->name = $this->pageName();
        if ($this->_results) {
            $page_output->addScriptFile('tables.js', 'horde');
            $view->pages = array();
            foreach ($this->_results as $page) {
                if (!empty($page['page_history'])) {
                    $page = new Wicked_Page_StandardHistoryPage($page);
                } else {
                    $page = new Wicked_Page_StandardPage($page);
                }
                $view->pages[] = $page->toView();
            }
        }
        $view->templates = $wicked->getMatchingPages('Template', Wicked_Page::MATCH_ENDS);
        $view->help = Horde_Help::link('wicked', 'Templates');

        return $view->render('edit/create');
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

    /**
     *
     *
     * @return string
     */
    public function getText()
    {
        // New page, no text to return
        return '';
    }

}
