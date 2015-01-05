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
 * Lists the least popular pages.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */
class Wicked_Page_LeastPopular extends Wicked_Page
{
    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_CONTENT => true,
        Wicked::MODE_DISPLAY => true);

    /**
     * Renders this page in content mode.
     *
     * @param integer $numPages  How many (at most) pages should we return?
     *
     * @return string  The page contents.
     */
    public function content($numPages = 10)
    {
        return $GLOBALS['wicked']->leastPopular($numPages);
    }

    /**
     * Renders this page in display or block mode.
     *
     * @return string  The content.
     * @throws Wicked_Exception
     */
    public function displayContents($isBlock)
    {
        global $injector, $page_output;

        $pages = array();
        foreach ($this->content(10) as $page) {
            $page = new Wicked_Page_StandardPage($page);
            $object = $page->toView();
            $object->hits = $page->hits();
            $pages[] = $object;
        }

        $page_output->addScriptFile('tables.js', 'horde');

        $view = $injector->createInstance('Horde_View');
        $view->hits = true;

        return $view->render('pagelist/header')
            . $view->renderPartial('pagelist/page', array('collection' => $pages))
            . $view->render('pagelist/footer');
    }

    public function pageName()
    {
        return 'LeastPopular';
    }

    public function pageTitle()
    {
        return _("Least Popular");
    }

}
