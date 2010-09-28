<?php

require_once WICKED_BASE . '/lib/Page/StandardPage.php';

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
class AllPages extends Wicked_Page {

    /**
     * Display modes supported by this page.
     */
    var $supportedModes = array(
        WICKED_MODE_CONTENT => true,
        WICKED_MODE_DISPLAY => true);

    /**
     * Render this page in Content mode.
     *
     * @return string  The page content, or PEAR_Error.
     */
    function content()
    {
        global $wicked;

        return $wicked->getAllPages();
    }

    /**
     * Render this page in display or block mode.
     *
     * @return mixed  Returns page contents or PEAR_Error
     */
    function displayContents($isBlock)
    {
        global $notification;

        $summaries = $this->content();
        if (is_a($summaries, 'PEAR_Error')) {
            $notification->push('Error retrieving summaries : ' .
                                $summaries->getMessage(), 'horde.error');
            return $summaries;
        }

        $template = $GLOBALS['injector']->createInstance('Horde_Template');
        $pages = array();
        foreach ($summaries as $page) {
            $page = new StandardPage($page);
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
