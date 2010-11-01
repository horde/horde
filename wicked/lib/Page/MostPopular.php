<?php
/**
 * Wicked MostPopular class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class Wicked_Page_MostPopular extends Wicked_Page {

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
     * @return string  The page content.
     */
    public function content($numPages = 10)
    {
        return $GLOBALS['wicked']->mostPopular($numPages);
    }

    /**
     * Renders this page in display or block mode.
     *
     * @return string  The content.
     * @throws Wicked_Exception
     */
    public function displayContents($isBlock)
    {
        $template = $GLOBALS['injector']->createInstance('Horde_Template');
        $pages = array();
        foreach ($this->content(10) as $page) {
            $page = new Wicked_Page_StandardPage($page);
            $pages[] = array('author' => $page->author(),
                             'created' => $page->formatVersionCreated(),
                             'name' => $page->pageName(),
                             'context' => false,
                             'hits' => $page->hits(),
                             'url' => $page->pageUrl(),
                             'version' => $page->version());
        }
        $template->set('pages', $pages, true);
        $template->set('hits', true, true);
        $hits = true;

        Horde::addScriptFile('tables.js', 'horde', true);

        ob_start();
        require WICKED_TEMPLATES . '/pagelist/header.inc';
        echo $template->fetch(WICKED_TEMPLATES . '/pagelist/pagelist.html');
        require WICKED_TEMPLATES . '/pagelist/footer.inc';
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    public function pageName()
    {
        return 'MostPopular';
    }

    public function pageTitle()
    {
        return _("Most Popular");
    }

}
