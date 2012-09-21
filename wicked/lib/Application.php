<?php
/**
 * Wicked application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Wicked through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Wicked
 */

/* Determine the base directories. */
if (!defined('WICKED_BASE')) {
    define('WICKED_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(WICKED_BASE . '/config/horde.local.php')) {
        include WICKED_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', WICKED_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Wicked_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H5 (2.0-git)';

    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Wicked_Driver', 'Wicked_Factory_Driver', 'create');
    }

    /**
     * Global variables defined:
     * - $wicked:   The Wicked_Driver object.
     */
    protected function _init()
    {
        $GLOBALS['wicked'] = $GLOBALS['injector']->getInstance('Wicked_Driver');
        $GLOBALS['page_output']->addLinkTag(array(
            'href' => Horde::url('opensearch.php', true, -1),
            'rel' => 'search',
            'title' => $GLOBALS['registry']->get('name') . ' (' . Horde::url('', true) . ')',
            'type' => 'application/opensearchdescription+xml'
        ));
    }

    /**
     */
    public function menu($menu)
    {
        global $conf, $page;

        if (@count($conf['menu']['pages'])) {
            $pages = array('Wiki/Home' => _("_Home"),
                           'Wiki/Usage' => _("_Usage"),
                           'RecentChanges' => _("_Recent Changes"),
                           'AllPages' => _("_All Pages"));
            foreach ($conf['menu']['pages'] as $pagename) {
                /* Determine who we should say referred us. */
                $curpage = isset($page) ? $page->pageName() : null;
                $referrer = Horde_Util::getFormData('referrer', $curpage);

                /* Determine if we should depress the button. We have to do
                 * this on our own because all the buttons go to the same .php
                 * file, just with different args. */
                if (!strstr($_SERVER['PHP_SELF'], 'prefs.php') &&
                    $curpage === $pagename) {
                    $cellclass = 'current';
                } else {
                    $cellclass = '__noselection';
                }

                $url = Wicked::url($pagename)->add('referrer', $referrer);
                $menu->add($url, $pages[$pagename], str_replace('/', '', $pagename) . '.png', null, null, null, $cellclass);
            }
        }
    }

    /**
     */
    public function perms()
    {
        $perms = array(
            'pages' => array(
                'title' => _("Pages")
            )
        );

        foreach (array('AllPages', 'LeastPopular', 'MostPopular', 'RecentChanges') as $val) {
            $perms['pages:' . $val] = array(
                'title' => $val
            );
        }

        try {
            $pages = $GLOBALS['wicked']->getPages();
            sort($pages);
            foreach ($pages as $pagename) {
                $perms['pages:' .$GLOBALS['wicked']->getPageId($pagename)] = array(
                    'title' => $pagename
                );
            }
        } catch (Wicked_Exception $e) {}

        return $perms;
    }

    /* Download data. */

    /**
     */
    public function download(Horde_Variables $vars)
    {
        global $wicked;

        $page = $vars->get('page', 'Wiki/Home');

        $page_id = (($id = $wicked->getPageId($page)) === false)
            ? $page
            : $id;

        $version = $vars->version;
        if (empty($version)) {
            try {
                $attachments = $wicked->getAttachedFiles($page_id);
                foreach ($attachments as $attachment) {
                    if ($attachment['attachment_name'] == $vars->file) {
                        $version = $attachment['attachment_version'];
                    }
                }
            } catch (Wicked_Exception $e) {}

            if (empty($version)) {
                // If we redirect here, we cause an infinite loop with inline
                // attachments.
                header('HTTP/1.1 404 Not Found');
                exit;
            }
        }

        try {
            $data = $wicked->getAttachmentContents($page_id, $vars->file, $version);
            $wicked->logAttachmentDownload($page_id, $vars->file);
        } catch (Wicked_Exception $e) {
            // If we redirect here, we cause an infinite loop with inline
            // attachments.
            header('HTTP/1.1 404 Not Found');
            echo $e->getMessage();
            exit;
        }

        $type = Horde_Mime_Magic::analyzeData($data, isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null);
        if ($type === false) {
            $type = Horde_Mime_Magic::filenameToMime($vars->file, false);
        }

        return array(
            'data' => $data,
            'file' => $vars->file,
            'type' => $type
        );
    }

}
