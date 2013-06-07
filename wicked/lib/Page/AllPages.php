<?php
/**
 * Wicked AllPages class.
 *
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class Wicked_Page_AllPages extends Wicked_Page {

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
        $pages = array();
        foreach ($this->content() as $page) {
            $page = new Wicked_Page_StandardPage($page);
            $pages[] = array(
                'author' => $page->author(),
                'created' => $page->formatVersionCreated(),
                'name' => $page->pageUrl()->link()
                    . htmlspecialchars($page->pageName()) . '</a>',
                'timestamp' => $page->versionCreated(),
                'version' => $page->pageUrl()->link() . $page->version() . '</a>',
            );
        }

        $view = $GLOBALS['injector']->createInstance('Horde_View');
        $view->pages = $pages;

        $GLOBALS['page_output']->addScriptFile('tables.js', 'horde');

        // Show search form and page header.
        ob_start();
        require WICKED_TEMPLATES . '/pagelist/header.inc';
        echo $view->render('pagelist/pagelist');
        require WICKED_TEMPLATES . '/pagelist/footer.inc';
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
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
