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
        WICKED_MODE_CONTENT => true);

    /**
     * Sync driver
     */
    var $_sync;

    /**
     * Constructor
     */
    function SyncPages()
    {
        $this->_loadSyncDriver();
        if (is_a($this->_sync, 'PEAR_Error')) {
            return $this->_sync;
        }

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
     * Render this page in Content mode.
     *
     * @return string  The page content, or PEAR_Error.
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
            if (is_a($page, 'PEAR_Error')) {
                return $page;
            }
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
            if (is_a($page, 'PEAR_Error')) {
                return $page;
            } elseif (md5($page->getText()) == $_SESSION['wicked']['sync']['pages'][$pageName]['page_checksum']) {
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
                if (is_a($this->_sync, 'PEAR_Error')) {
                    return $this->_sync;
                }

                // We submitted the form so we should fetch pages
                $pages = $this->_sync->getMultiplePageInfo();
                if (is_a($pages, 'PEAR_Error')) {
                    $GLOBALS['notification']->push($pages);
                } elseif (!empty($pages)) {
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
        if (is_a($page, 'PEAR_Error')) {
            return $page;
        }

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
     */
    function getRemotePageInfo($pageName)
    {
        if (isset($_SESSION['wicked']['sync']['pages'][$pageName])) {
            return $_SESSION['wicked']['sync']['pages'][$pageName];
        } else {
            $info = $this->_sync->getPageInfo($pageName);
            if (!is_a($info, 'PEAR_Error')) {
                $_SESSION['wicked']['sync']['pages'][$pageName] = $info;
            }
            return $info;
        }
    }

    /**
     * Download remote page to local server
     */
    function download($pageName)
    {
        $text = $this->_sync->getPageSource($pageName);
        if (is_a($text, 'PEAR_Error')) {
            return $text;
        }

        $page = Wicked_Page::getPage($pageName);
        if (is_a($page, 'PEAR_Error')) {
            return $page;
        }

        if (!$page->allows(WICKED_MODE_EDIT)) {
            return PEAR::RaiseError(sprintf(_("You don't have permission to edit \"%s\"."), $pageName));
        }

        $content = $page->getText();
        if (is_a($content, 'PEAR_Error')) {
            // Maybe the page does not exists, if not create it
            if ($GLOBALS['wicked']->pageExists($pageName)) {
                return $content;
            } else {
                $result = $GLOBALS['wicked']->newPage($pageName, $text);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }
        } else {
            if (trim($text) == trim($content)) {
                return PEAR::raiseError(_("No changes made"));
            }
            $result = $page->updateText($text, _("Downloaded from remote server"), true);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
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
        if (is_a($page, 'PEAR_Error')) {
            $GLOBALS['notification']->push($page);
            return $page;
        }

        $content = $page->getText();
        if (is_a($content, 'PEAR_Error')) {
            $GLOBALS['notification']->push($content);
            return $content;
        }

        $result = $this->_sync->editPage($pageName, $content, _("Uploaded from remote server"), true);
        if (is_a($result, 'PEAR_Error')) {
            $GLOBALS['notification']->push($result);
            return $content;
        }

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
