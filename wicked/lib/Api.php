<?php
/**
 * Wicked external API interface.
 *
 * This file defines Wicked's external API interface. Other applications
 * can interact with Wicked through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Wicked
 */
class Wicked_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        'show' => '%application%/display.php?page=|page|&version=|version|#|toc|'
    );

    /**
     * Returns a list of available pages.
     *
     * @param boolean $special Include special pages
     * @param boolean $no_cache Always retreive pages from backed
     *
     * @return array  An array of all available pages.
     */
    public function listPages($special = true, $no_cache = false)
    {
        return $GLOBALS['wicked']->getPages($special, $no_cache);
    }

    /**
     * Return basic page information.
     *
     * @param string $pagename Page name
     *
     * @return array  An array of page parameters.
     * @throws Wicked_Exception
     */
    public function getPageInfo($pagename)
    {
        $page = Wicked_Page::getPage($pagename);
        return array(
            'page_majorversion' => $page->_page['page_majorversion'],
            'page_minorversion' => $page->_page['page_minorversion'],
            'page_checksum' => md5($page->getText()),
            'version_created' => $page->_page['version_created'],
            'change_author' => $page->_page['change_author'],
            'change_log' => $page->_page['change_log'],
        );
    }

    /**
     * Return basic information for multiple pages.
     *
     * @param array $pagenames Page names
     *
     * @return array  An array of arrays of page parameters.
     * @throws Wicked_Exception
     */
    public function getMultiplePageInfo($pagenames = array())
    {
        require_once dirname(__FILE__) . '/base.php';

        if (empty($pagenames)) {
            $pagenames = $GLOBALS['wicked']->getPages(false);
        }

        $info = array();

        foreach ($pagenames as $pagename) {
            $page = Wicked_Page::getPage($pagename);
            $info[$pagename] = array(
                'page_majorversion' => $page->_page['page_majorversion'],
                'page_minorversion' => $page->_page['page_minorversion'],
                'page_checksum' => md5($page->getText()),
                'version_created' => $page->_page['version_created'],
                'change_author' => $page->_page['change_author'],
                'change_log' => $page->_page['change_log']
            );
        }

        return $info;
    }

    /**
     * Return page history.
     *
     * @param string $pagename Page name
     *
     * @return array  An array of page parameters.
     * @throws Wicked_Exception
     */
    public function getPageHistory($pagename)
    {
        $page = Wicked_Page::getPage($pagename);
        $summaries = $GLOBALS['wicked']->getHistory($pagename);

        foreach ($summaries as $i => $summary) {
            $summaries[$i]['page_checksum'] = md5($summary['page_text']);
            unset($summaries[$i]['page_text']);
        }

        return $summaries;
    }

    /**
     * Chech if a page exists
     *
     * @param string $pagename Page name
     *
     * @return boolean
     */
    public function pageExists($pagename)
    {
        return $GLOBALS['wicked']->pageExists($pagename);
    }

    /**
     * Returns a rendered wiki page.
     *
     * @param string $pagename Page to display
     *
     * @return array  Page without CSS link
     * @throws Wicked_Exception
     */
    public function display($pagename)
    {
        $page = Wicked_Page::getPage($pagename);
        $GLOBALS['wicked']->logPageView($page->pageName());
        return $page->displayContents(false);
    }

    /**
     * Returns a rendered wiki page.
     *
     * @param string $pagename Page to display
     * @param string $format Format to render page to (Plain, XHtml)
     *
     * @return array  Rendered page
     * @throws Wicked_Exception
     */
    public function renderPage($pagename, $format = 'Plain')
    {
        $page = Wicked_Page::getPage($pagename);
        $content = $page->getProcessor()->transform($page->getText(), $format);
        $GLOBALS['wicked']->logPageView($page->pageName());
        return $content;
    }

    /**
     * Updates content of a wiki page. If the page does not exist it is
     * created.
     *
     * @param string $pagename Page to edit
     * @param string $text Page content
     * @param string $changelog Description of the change
     * @param boolean $minorchange True if this is a minor change
     *
     * @throws Wicked_Exception
     */
    public function edit($pagename, $text, $changelog = '',
                         $minorchange = false)
    {
        $page = Wicked_Page::getPage($pagename);
        if (!$page->allows(Wicked::MODE_EDIT)) {
            throw new Wicked_Exception(sprintf(_("You don't have permission to edit \"%s\"."), $pagename));
        }
        if ($GLOBALS['conf']['wicked']['require_change_log'] &&
            empty($changelog)) {
            throw new Wicked_Exception(_("You must provide a change log."));
        }

        try {
            $content = $page->getText();
        } catch (Wicked_Exception $e) {
            // Maybe the page does not exists, if not create it
            if ($GLOBALS['wicked']->pageExists($pagename)) {
                throw $e;
            }
            $GLOBALS['wicked']->newPage($pagename, $text);
        }

        if (trim($text) == trim($content)) {
            throw new Wicked_Exception(_("No changes made"));
        }

        $page->updateText($text, $changelog, $minorchange);
    }

    /**
     * Get a list of templates provided by Wicked.  A template is any page
     * whose name begins with "Template"
     *
     * @return arrary  Array on success.
     * @throws Wicked_Exception
     */
    public function listTemplates()
    {
        global $wicked;
        $templates = $wicked->getMatchingPages('Template', WICKED_PAGE_MATCH_ENDS);
        $list = array(array('category' => _("Wiki Templates"),
            'templates' => array()));
        foreach ($templates as $page) {
            $list[0]['templates'][] = array('id' => $page['page_name'],
                'name' => $page['page_name']);
        }
        return $list;
    }

    /**
     * Get a template specified by its name.  This is effectively an alias for
     * getPageSource() since Wicked templates are also normal pages.
     * Wicked templates are pages that include "Template" at the beginning of
     * the name.
     *
     * @param string $name  The name of the template to fetch
     *
     * @return string  Template data.
     * @throws Wicked_Exception
     */
    public function getTemplate($name)
    {
        return $this->getPageSource($name);
    }

    /**
     * Get the wiki source of a page specified by its name.
     *
     * @param string $name     The name of the page to fetch
     * @param string $version  Page version
     *
     * @return string  Page data.
     * @throws Wicked_Exception
     */
    public function getPageSource($pagename, $version = null)
    {
        global $wicked;

        $page = Wicked_Page::getPage($pagename, $version);

        if (!$page->allows(Wicked::MODE_CONTENT)) {
            throw new Wicked_Exception(_("Permission denied."));
        }

        if (!$page->isValid()) {
            throw new Wicked_Exception(_("Invalid page requested."));
        }

        return $page->getText();
    }

    /**
     * Process a completed template to update the named Wiki page.  This
     * method is basically a passthrough to edit().
     *
     * @param string $name   Name of the new or modified page
     * @param string $data   Text content of the populated template
     *
     * @throws Wicked_Exception
     */
    public function saveTemplate($name, $data)
    {
        $this->edit($name, $data, 'Template Auto-fill', false);
    }

    /**
     * Returns the most recently changed pages.
     *
     * @param integer $days  The number of days to look back.
     *
     * @return array  Pages.
     * @throws Wicked_Exception
     */
    public function getRecentChanges($days = 3)
    {
        $info = array();
        foreach ($GLOBALS['wicked']->getRecentChanges($days) as $page) {
            $info[$page['page_name']] = array(
                'page_majorversion' => $page['page_majorversion'],
                'page_minorversion' => $page['page_minorversion'],
                'page_checksum' => md5($page['page_text']),
                'version_created' => $page['version_created'],
                'change_author' => $page['change_author'],
                'change_log' => $page['change_log'],
            );
        }

        return $info;
    }

}
