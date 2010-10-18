<?php

require_once WICKED_BASE . '/lib/Page/StandardPage.php';
require_once WICKED_BASE . '/lib/Sync.php';

/**
 * Wicked SyncPages class.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Wicked
 */
class SyncPages extends Wicked_Page {

    /**
     * Display modes supported by this page.
     */
    var $supportedModes = array(
        Wicked::MODE_CONTENT => true);

    /**
     * Sync driver
     */
    var $_sync;

    /**
     * Constructor
     *
     * @throws Wicked_Exception
     */
    function SyncPages()
    {
        $this->_loadSyncDriver();

        // Do we need to perform any action?
        switch (Horde_Util::getGet('actionID')) {
        case 'sync_download':
            $page = Horde_Util::getGet('sync_page');
            return $this->download($page);

        case 'sync_upload':
            $page = Horde_Util::getGet('sync_page');
            return $this->upload($page);
        }
    }

    /**
     * Renders this page in content mode.
     *
     * @return string  The page content.
     * @throws Wicked_Exception
     */
    function content()
    {
        global $wicked;

        // Used in all cases.
        $form = $this->_syncForm();

        // We have no data to check
        if (empty($_SESSION['wicked']['sync'])) {
            ob_start();
            require WICKED_TEMPLATES . '/sync/header.inc';
            require WICKED_TEMPLATES . '/sync/footer.inc';
            return ob_get_clean();
        }

        // New pages on remote server
        $new_remote = array();
        foreach ($_SESSION['wicked']['sync']['pages'] as $pageName => $info) {
            if (!$wicked->pageExists($pageName)) {
                $new_remote[$pageName] = array(
                    'page_majorversion' => $info['page_majorversion'],
                    'page_minorversion' => $info['page_minorversion'],
                    'page_checksum' => $info['page_checksum'],
                    'version_created' => $info['version_created'],
                    'change_author' => $info['change_author'],
                    'change_log' => $info['change_log']
                );
            }
        }

        // New pages on local server
        $new_local = array();
        $local_pages = $wicked->getPages(false);
        foreach ($local_pages as $pageName) {
            if (isset($_SESSION['wicked']['sync']['pages'][$pageName])) {
                continue;
            }
            $page = Wicked_Page::getPage($pageName);
            $new_local[$pageName] = array(
                'page_majorversion' => $page->_page['page_majorversion'],
                'page_minorversion' => $page->_page['page_minorversion'],
                'page_checksum' => md5($page->getText()),
                'version_created' => $page->_page['version_created'],
                'change_author' => $page->_page['change_author'],
                'change_log' => $page->_page['change_log']
            );
        }

        // Pages with differences
        $sync_pages = array();
        foreach ($local_pages as $pageName) {
            // Is a new page
            if (isset($new_local[$pageName]) ||
                isset($new_remote[$pageName])) {
                continue;
            }

            // Compare checksum
            $page = Wicked_Page::getPage($pageName);
            if (md5($page->getText()) == $_SESSION['wicked']['sync']['pages'][$pageName]['page_checksum']) {
                continue;
            }

            $sync_pages[] = $pageName;
        }

        // Output
        Horde::addScriptFile('tables.js', 'horde', true);
        ob_start();
        require WICKED_TEMPLATES . '/sync/header.inc';
        require WICKED_TEMPLATES . '/sync/list.inc';
        require WICKED_TEMPLATES . '/sync/footer.inc';
        return ob_get_clean();
    }

    /**
     * Renders this page in display or block mode.
     *
     * @return string  The contents.
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
        return 'SyncPages';
    }

    /**
     * Page title
     */
    function pageTitle()
    {
        return _("Sync Pages");
    }

    /**
     * Prepare page link
     */
    function _viewLink($pageName, $local = true)
    {
        if ($local) {
            return  '<a href="' .  Wicked::url($pageName) . '" target="_blank">' . _("View local") . '</a>';
        } else {
            return  '<a href="' .  $_SESSION['wicked']['sync']['display']  . $pageName . '" target="_blank">' . _("View remote") . '</a>';
        }
    }

    /**
     * Get and process sync info form
     *
     * @throws Wicked_Exception
     */
    function _syncForm()
    {
        require_once 'Horde/Form.php';

        $vars = Horde_Variables::getDefaultVariables();
        $form = new Horde_Form($vars, _("Sync data"), 'syncdata');
        $form->setButtons(array(_("Fetch page list"), _("Save login info"), _("Remove login info")), _("Reset"));
        $form->addHidden('', 'page', 'text', true);

        $defaults = array(
            'driver' => 'wicked',
            'prefix' => 'wiki',
            'url' => 'http://wiki.example.org/rpc.php',
            'display' => 'http://wiki.example.org/display.php?page=',
            'edit' => 'http://wiki.example.org/display.php?page=EditPage&referrer=',
            'user' => '',
            'password' => '',
        );

        // Prepare default values
        $stored = @unserialize($GLOBALS['prefs']->getValue('sync_data'));
        if (isset($_GET['__old_sync_select'])) {
            unset($_SESSION['wicked']['sync']);
        }
        if ($vars->get('sync_select') && isset($stored[$vars->get('sync_select')])) {
            $defaults = $stored[$vars->get('sync_select')];
            foreach ($defaults as $k => $v) {
                if ($vars->exists('sync_' . $k)) {
                    $vars->set('sync_' . $k, $v);
                }
            }
        }
        if (!empty($_SESSION['wicked']['sync'])) {
            $defaults = $_SESSION['wicked']['sync'];
        }

        // Add stored info selection
        $enum = array();
        foreach ($stored as $k => $v) {
            $enum[$k] = $v['url'] . ' (' . $v['user'] . ')';
        }
        if (!empty($enum)) {
            require_once 'Horde/Form/Action.php';
            $v = &$form->addVariable(_("Stored"), 'sync_select', 'enum', false, null, false, array($enum, _("Custom")));
            $v->setAction(Horde_Form_Action::factory('submit'));
            $v->setOption('trackchange', true);
        }

        // Add standard form info
        $v = &$form->addVariable(_("Driver"), 'sync_driver', 'enum', true, null, false, array(array('wicked' => 'Wicked'), false));
        $v->setDefault($defaults['driver']);

        $v = &$form->addVariable(_("Prefix"), 'sync_prefix', 'text', true);
        $v->setDefault($defaults['prefix']);

        $v = &$form->addVariable(_("Url"), 'sync_url', 'text', true);
        $v->setDefault($defaults['url']);

        $v = &$form->addVariable(_("User"), 'sync_user', 'text', false, false, _("By default, your login data will be used"));
        $v->setDefault($defaults['user']);

        $form->addVariable(_("Password"), 'sync_password', 'password', false, false, _("By default, your login data will be used"));

        $v = &$form->addVariable(_("Display"), 'sync_display', 'text', false);
        $v->setDefault($defaults['display']);

        $v = &$form->addVariable(_("Edit"), 'sync_edit', 'text', false);
        $v->setDefault($defaults['edit']);

        // Process
        if ($form->validate()) {
            $info = array(
                'driver' => $vars->get('sync_driver'),
                'prefix' => $vars->get('sync_prefix'),
                'url' => $vars->get('sync_url'),
                'display' => $vars->get('sync_display'),
                'edit' => $vars->get('sync_edit'),
                'user' => $vars->get('sync_user'),
                'password' => $vars->get('sync_password'),
                'pages' => array(),
            );

            switch (Horde_Util::getFormData('submitbutton')) {
            case _("Fetch page list"):
                // Load driver
                $_SESSION['wicked']['sync'] = $info;
                $this->_loadSyncDriver();

                // We submitted the form so we should fetch pages
                $pages = $this->_sync->getMultiplePageInfo();
                if (!empty($pages)) {
                    $_SESSION['wicked']['sync']['pages'] = $pages;
                }
                break;

            case _("Save login info"):
                $data = unserialize($GLOBALS['prefs']->getValue('sync_data'));
                $key = md5($info['url'] . $info['user']);
                $data[$key] = $info;
                unset($data[$key]['password'],
                      $data[$key]['pages']);
                $GLOBALS['prefs']->setValue('sync_data', serialize($data));
                $GLOBALS['notification']->push(_("Sync login info was stored"), 'horde.success');

                Wicked::url('SyncPages', true)->redirect();

            case _("Remove login info"):
                $data = unserialize($GLOBALS['prefs']->getValue('sync_data'));
                $key = md5($info['url'] . $info['user']);
                unset($data[$key]);
                $GLOBALS['prefs']->setValue('sync_data', serialize($data));
                $GLOBALS['notification']->push(_("Sync login info was removed."), 'horde.success');

                Wicked::url('SyncPages', true)->redirect();
            }
        }

        Horde::startBuffer();
        $form->renderActive(null, null, null, 'get');
        return Horde::endBuffer();
    }

    /**
     * Get page info
     *
     * @param boolean $local Get local or remote info
     */
    function getLocalPageInfo($pageName)
    {
        $page = Wicked_Page::getPage($pageName);
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
     * Get page info
     *
     * @param boolean $local Get local or remote info
     *
     * @throws Wicked_Exception
     */
    function getRemotePageInfo($pageName)
    {
        if (!isset($_SESSION['wicked']['sync']['pages'][$pageName])) {
            $_SESSION['wicked']['sync']['pages'][$pageName] = $this->_sync->getPageInfo($pageName);
        }
        return $_SESSION['wicked']['sync']['pages'][$pageName];
    }

    /**
     * Download remote page to local server
     *
     * @throws Wicked_Exception
     */
    function download($pageName)
    {
        $text = $this->_sync->getPageSource($pageName);
        $page = Wicked_Page::getPage($pageName);
        if (!$page->allows(Wicked::MODE_EDIT)) {
            throw new Wicked_Exception(sprintf(_("You don't have permission to edit \"%s\"."), $pageName));
        }

        try {
            $content = $page->getText();
            if (trim($text) == trim($content)) {
                $GLOBALS['notification']->push(_("No changes made"), 'horde.message');
                return;
            }
            $page->updateText($text, _("Downloaded from remote server"), true);
        } catch (Wicked_Exception $e) {
            // Maybe the page does not exists, if not create it
            if ($GLOBALS['wicked']->pageExists($pageName)) {
                throw $e;
            }
            $GLOBALS['wicked']->newPage($pageName, $text);
        }

        $GLOBALS['notification']->push(sprintf(_("Page \"%s\" was sucessfuly downloaded from remote to local wiki."), $pageName), 'horde.success');

        // Show the newly saved page.
        Wicked::url($pageName, true)->redirect();
    }

    /**
     * Upload local page to remote server
     */
    function upload($pageName)
    {
        $page = Wicked_Page::getPage($pageName);
        $content = $page->getText();
        $this->_sync->editPage($pageName, $content, _("Uploaded from remote server"), true);
        $GLOBALS['notification']->push(sprintf(_("Page \"%s\" was sucessfully uploaded from local to remote wiki."), $pageName), 'horde.success');

        // Show the newly updated page.
        Wicked::url($pageName, true)->redirect();
    }

    /**
     * Load sync driver
     */
    function _loadSyncDriver()
    {
        if ($this->_sync) {
            return true;
        } elseif (empty($_SESSION['wicked']['sync']['driver'])) {
            return false;
        }

        $this->_sync = Wicked_Sync::factory($_SESSION['wicked']['sync']['driver'],
                                            $_SESSION['wicked']['sync']);
    }

}
