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
 * Displays a list of all pages.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */
class Wicked_Page_AllPages extends Wicked_Page
{
    /**
     * Display modes supported by this page.
     */
    public $supportedModes = array(
        Wicked::MODE_CONTENT => true,
        Wicked::MODE_DISPLAY => true);

    /**
     * Renders this page in content mode.
     *
     * @return string  The page content.
     */
    public function content()
    {
        return $GLOBALS['wicked']->getAllPages();
    }

    /**
     * Renders this page in display or block mode.
     *
     * @return string  The page contents.
     * @throws Wicked_Exception
     */
    public function displayContents($isBlock)
    {
        global $injector, $page_output;

        $pages = array();
        foreach ($this->content() as $page) {
            $page = new Wicked_Page_StandardPage($page);
            $pages[] = $page->toView();
        }

        $page_output->addScriptFile('tables.js', 'horde');

        $view = $injector->createInstance('Horde_View');

        // Show search form and page header.
        return $view->render('pagelist/header')
            . $view->renderPartial('pagelist/page', array('collection' => $pages))
            . $view->render('pagelist/footer');
    }

    public function pageName()
    {
        return 'AllPages';
    }

    public function pageTitle()
    {
        return _("All Pages");
    }

}
