<?php

require_once WICKED_BASE . '/lib/Page/StandardPage.php';

/**
 * Wicked RecentChanges class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class RecentChanges extends Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
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

        $days = (int)Horde_Util::getGet('days', 3);
        $summaries = $wicked->getRecentChanges($days);

        $bydate = array();
        $changes = array();
        foreach ($summaries as $page) {
            $page = new StandardPage($page);

            $createDate = $page->versionCreated();
            $tm = localtime($createDate, true);
            $createDate = mktime(0, 0, 0, $tm['tm_mon'], $tm['tm_mday'],
                                 $tm['tm_year'], $tm['tm_isdst']);

            $version_url = Horde_Util::addParameter($page->pageUrl(), 'version',
                                              $page->version());
            $diff_url = Horde_Util::addParameter(Horde::url('diff.php'),
                                           array('page' => $page->pageName(),
                                                 'v1' => '?',
                                                 'v2' => $page->version()));
            $diff_alt = sprintf(_("Show changes for %s"), $page->version());
            $diff_img = Horde::img('diff.png', $diff_alt);
            $pageInfo = array('author' => $page->author(),
                              'name' => $page->pageName(),
                              'url' => $page->pageUrl(),
                              'version' => $page->version(),
                              'version_url' => $version_url,
                              'version_alt' => sprintf(_("Show version %s"),
                                                       $page->version()),
                              'diff_url' => $diff_url,
                              'diff_alt' => $diff_alt,
                              'diff_img' => $diff_img,
                              'created' => $page->formatVersionCreated(),
                              'change_log' => $page->changeLog());
            $bydate[$createDate][$page->versionCreated()] = $pageInfo;
        }
        krsort($bydate);

        foreach ($bydate as $pageList) {
            krsort($pageList);
            $pageList = array_values($pageList);
            $changes[] = array('date' => $pageList[0]['created'],
                               'pages' => $pageList);
        }

        return $changes;
    }

    /**
     * Render this page in display or block mode.
     *
     * @return mixed  Returns contents or PEAR_Error.
     */
    function displayContents($isBlock)
    {
        global $notification;

        $changes = $this->content();
        if (is_a($changes, 'PEAR_Error')) {
            $notification->push('Error retrieving histories: ' . $summaries->getMessage(), 'horde.error');
            return $changes;
        }

        $template = $GLOBALS['injector']->createInstance('Horde_Template');
        $template->set('changes', $changes);

        return $template->fetch(WICKED_TEMPLATES . '/display/RecentChanges.html');
    }

    function pageName()
    {
        return 'RecentChanges';
    }

    function pageTitle()
    {
        return _("Recent Changes");
    }

}
