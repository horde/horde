<?php
/**
 * Wicked AllPages class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class Wicked_Page_AllPages extends Wicked_Page {

    /**
     * Display modes supported by this page.
     */
    var $supportedModes = array(
        Wicked::MODE_CONTENT => true,
        Wicked::MODE_DISPLAY => true);

    /**
     * Renders this page in content mode.
     *
     * @return string  The page content.
     */
    function content()
    {
        return $GLOBALS['wicked']->getAllPages();
    }

    /**
     * Renders this page in display or block mode.
     *
     * @return string  The page contents.
     * @throws Wicked_Exception
     */
    function displayContents($isBlock)
    {
        $template = $GLOBALS['injector']->createInstance('Horde_Template');
        $pages = array();
        foreach ($this->content() as $page) {
            $page = new Wicked_Page_StandardPage($page);
            $pages[] = array('author' => $page->author(),
                             'created' => $page->formatVersionCreated(),
                             'name' => $page->pageName(),
                             'context' => false,
                             'url' => $page->pageUrl(),
                             'version' => $page->version(),
                             'class' => '');
        }
        $template->set('pages', $pages, true);
        $template->set('hits', false, true);

        Horde::addScriptFile('tables.js', 'horde', true);

        // Show search form and page header.
        ob_start();
        require WICKED_TEMPLATES . '/pagelist/header.inc';
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/pagelist.html');
        require WICKED_TEMPLATES . '/pagelist/footer.inc';
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    function pageName()
    {
        return 'AllPages';
    }

    function pageTitle()
    {
        return _("All Pages");
    }

}
