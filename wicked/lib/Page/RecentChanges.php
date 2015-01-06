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
 * Lists the most recently changed pages.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */
class Wicked_Page_RecentChanges extends Wicked_Page
{
    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_CONTENT => true,
        Wicked::MODE_DISPLAY => true
    );

    /**
     * Renders this page in content mode.
     *
     * @return string  The page content.
     * @throws Wicked_Exception
     */
    public function content()
    {
        global $wicked;

        $days = (int)Horde_Util::getGet('days', 3);
        $summaries = $wicked->getRecentChanges($days);

        if (count($summaries) < 10) {
            $summaries = $wicked->mostRecent(10);
        }

        $bydate = array();
        $changes = array();
        foreach ($summaries as $page) {
            $page = new Wicked_Page_StandardPage($page);

            $createDate = $page->versionCreated();
            $tm = localtime($createDate, true);
            $createDate = mktime(0, 0, 0, $tm['tm_mon'], $tm['tm_mday'],
                                 $tm['tm_year'], $tm['tm_isdst']);

            $version_url = $page->pageUrl()->add('version', $page->version());
            $diff_url = Horde::url('diff.php')->add(array(
                'page' => $page->pageName(),
                'v1' => '?',
                'v2' => $page->version()
            ));
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
            $bydate[$createDate][$page->versionCreated()][$page->version()] = $pageInfo;
        }
        krsort($bydate);

        foreach ($bydate as $bysecond) {
            $day = array();
            krsort($bysecond);
            foreach ($bysecond as $pageList) {
                krsort($pageList);
                $day = array_merge($day, array_values($pageList));
            }
            $changes[] = array('date' => $day[0]['created'],
                               'pages' => $day);
        }

        return $changes;
    }

    /**
     * Renders this page in display or block mode.
     *
     * @return string  The contents.
     * @throws Wicked_Exception
     */
    public function displayContents($isBlock)
    {
        $view = $GLOBALS['injector']->createInstance('Horde_View');
        $view->changes = $this->content();
        return $view->render('display/RecentChanges');
    }

    public function pageName()
    {
        return 'RecentChanges';
    }

    public function pageTitle()
    {
        return _("Recent Changes");
    }

}
