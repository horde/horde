<?php

require_once WICKED_BASE . '/lib/Page/SyncPages.php';

/**
 * Wicked SyncDiff class.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Wicked
 */
class SyncDiff extends SyncPages {

    /**
     * Display modes supported by this page.
     */
    var $supportedModes = array(
        Wicked::MODE_CONTENT => true,
        Wicked::MODE_DISPLAY => true);

    /**
     * Sync driver
     */
    var $_sync;

    /**
     * Working page
     */
    var $_pageName;

    function SyncDiff()
    {
        parent::SyncPages();

        $this->_pageName = Horde_Util::getGet('sync_page');
    }

    /**
     * Render this page in Content mode.
     *
     * @return string  The page content, or PEAR_Error.
     */
    function content()
    {
        if (!$this->_loadSyncDriver()) {
            return PEAR::raiseError(_("Synchronization is disabled"));
        }

        $remote = $this->_sync->getPageSource($this->_pageName);
        if (is_a($remote, 'PEAR_Error')) {
            return $remote;
        }

        $page = Wicked_Page::getPage($this->_pageName);
        if (is_a($page, 'PEAR_Error')) {
            return $page;
        }

        $local = $page->getText();
        if (is_a($local, 'PEAR_Error')) {
            return $local;
        }

        $renderer = 'inline';
        $inverse = Horde_Util::getGet('inverse', 1);

        include_once 'Text/Diff.php';
        include_once 'Text/Diff/Renderer.php';
        include_once 'Text/Diff/Renderer/' . $renderer . '.php';

        if ($inverse) {
            $diff = new Text_Diff(explode("\n", $local),
                                  explode("\n", $remote));
            $name1 = _("Local");
            $name2 = _("Remote");
        } else {
            $diff = new Text_Diff(explode("\n", $remote),
                                  explode("\n", $local));
            $name1 = _("Remote");
            $name2 = _("Local");
        }

        $class = 'Text_Diff_Renderer_' . $renderer;
        $renderer = new $class();

        Horde::addScriptFile('tables.js', 'horde', true);

        ob_start();
        require WICKED_TEMPLATES . '/sync/diff.inc';
        return ob_get_clean();
    }

    /**
     * Render this page in display or block mode.
     *
     * @return mixed  Returns page contents or PEAR_Error
     */
    function displayContents($isBlock)
    {
        global $notification;

        $content = $this->content();
        if (is_a($content, 'PEAR_Error')) {
            $notification->push($content);
        }

        return $content;
    }

    /**
     * Page name
     */
    function pageName()
    {
        return 'SyncDiff';
    }

    /**
     * Page title
     */
    function pageTitle()
    {
        return _("Sync Diff");
    }

    /**
     * Try to find out if any version's content is same on the local and remote
     * servers.
     */
    function _getSameVersion()
    {
        $local = $GLOBALS['wicked']->getHistory($this->_pageName);
        if (is_a($local, 'PEAR_Error')) {
            return $local;
        }

        $info = $this->getLocalPageInfo($this->_pageName);
        if (is_a($info, 'PEAR_Error')) {
            return $info;
        }
        $local[] = $info;

        $remote = $this->_sync->getPageHistory($this->_pageName);
        if (is_a($remote, 'PEAR_Error')) {
            return $remote;
        }

        $info = $this->getRemotePageInfo($this->_pageName);
        if (is_a($info, 'PEAR_Error')) {
            return $info;
        }
        $remote[] = $info;

        $checksums = array();
        foreach (array_keys($local) as $i) {
            if (!isset($local[$i]['page_checksum'])) {
                $local[$i]['page_checksum'] = md5($local[$i]['page_text']);
                unset($local[$i]['page_text']);
            }
            $checksums[$i] = $local[$i]['page_checksum'];
        }

        $result = false;
        foreach ($remote as $history) {
            $version = array_search($history['page_checksum'], $checksums);
            if ($version !== false) {
                $result = array('remote' => $history, 'local' => $local[$version]);
                break;
            }
        }

        return $result;
    }

}
