<?php
/**
 * Wicked SyncDiff class.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Duck <duck@obala.net>
 * @package Wicked
 */
class Wicked_Page_SyncDiff extends Wicked_Page_SyncPages {

    /**
     * Display modes supported by this page.
     */
    public $supportedModes = array(
        Wicked::MODE_CONTENT => true,
        Wicked::MODE_DISPLAY => true);

    /**
     * Sync driver
     */
    protected $_sync;

    /**
     * Working page
     */
    protected $_pageName;

    public function __construct()
    {
        parent::__construct();
        $this->_pageName = Horde_Util::getGet('sync_page');
    }

    /**
     * Renders this page in content mode.
     *
     * @throws Wicked_Exception
     */
    public function content()
    {
        if (!$this->_loadSyncDriver()) {
            throw new Wicked_Exception(_("Synchronization is disabled"));
        }

        $remote = $this->_sync->getPageSource($this->_pageName);
        $page = Wicked_Page::getPage($this->_pageName);
        $local = $page->getText();

        $inverse = Horde_Util::getGet('inverse', 1);

        if ($inverse) {
            $diff = new Horde_Text_Diff('auto',
                                        array(explode("\n", $local),
                                              explode("\n", $remote)));
            $name1 = _("Local");
            $name2 = _("Remote");
        } else {
            $diff = new Horde_Text_Diff('auto',
                                        array(explode("\n", $remote),
                                              explode("\n", $local)));
            $name1 = _("Remote");
            $name2 = _("Local");
        }

        $renderer = new Horde_Text_Diff_Renderer_Inline();

        $GLOBALS['page_output']->addScriptFile('tables.js', 'horde');

        ob_start();
        require WICKED_TEMPLATES . '/sync/diff.inc';
        return ob_get_clean();
    }

    /**
     * @return string  The page contents.
     * @throws Wicked_Exception
     */
    public function displayContents($isBlock)
    {
        return $this->content();
    }

    /**
     * Page name
     */
    public function pageName()
    {
        return 'SyncDiff';
    }

    /**
     * Page title
     */
    public function pageTitle()
    {
        return _("Sync Diff");
    }

    /**
     * Tries to find out if any version's content is the same on the local and
     * remote servers.
     *
     * @throws Wicked_Exception
     */
    protected function _getSameVersion()
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
