<?php
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
class Wicked_Page_SyncDiff extends Wicked_Page_SyncPages {

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

    function __construct()
    {
        parent::__construct();
        $this->_pageName = Horde_Util::getGet('sync_page');
    }

    /**
     * Renders this page in content mode.
     *
     * @throws Wicked_Exception
     */
    function content()
    {
        if (!$this->_loadSyncDriver()) {
            throw new Wicked_Exception(_("Synchronization is disabled"));
        }

        $remote = $this->_sync->getPageSource($this->_pageName);
        $page = Wicked_Page::getPage($this->_pageName);
        $local = $page->getText();

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
     * @return string  The page contents.
     * @throws Wicked_Exception
     */
    function displayContents($isBlock)
    {
        return $this->content();
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
     * Tries to find out if any version's content is the same on the local and
     * remote servers.
     *
     * @throws Wicked_Exception
     */
    function _getSameVersion()
    {
        $local = $GLOBALS['wicked']->getHistory($this->_pageName);
        $info = $this->getLocalPageInfo($this->_pageName);
        $local[] = $info;
        $remote = $this->_sync->getPageHistory($this->_pageName);
        $info = $this->getRemotePageInfo($this->_pageName);
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
