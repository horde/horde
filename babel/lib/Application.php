<?php
/**
 * Babel application API.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */
class Babel_Application extends Horde_Registry_Application
{
    public $version = 'H4 (0.1-git)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        global $registry;

        $perms = array(
            'language' => array(
                'title' => _("Languages"),
                'type' => 'none'
            ),
            'module' => array(
                'title' => _("Modules"),
                'type' => 'none'
            )
        );

        foreach($registry->nlsconfig['languages'] as $langcode => $langdesc) {
            $perms['language:' . $langcode] = array(
        	    'title' => sprintf("%s (%s)", $langdesc, $langcode),
                'type' => 'boolean'
            );
        }


        foreach ($registry->applications as $app => $params) {
        	if (in_array($params['status'], array('block', 'heading')) ||
        	    (isset($params['fileroot']) && !is_dir($params['fileroot'])) ||
        	    preg_match('/_[tools|reports]$/', $app)) {
        	    continue;
        	}

            $perms['module:' . $app] = array(
                'title' => sprintf("%s (%s)", $params['name'], $app),
                'type' => 'boolean'
            );
        }

        $tabdesc = array(
            'download' => _("Download"),
            'upload' => _("Upload"),
            'stats' => _("Statistics"),
            'view' => _("View/Edit"),
            'viewsource' => _("View Source"),
            'extract' => _("Extract"),
            'make' => _("Make"),
            'commit' => _("Commit"),
            'reset' => _("Reset")
        );

        foreach ($tabdesc as $cat => $desc) {
            $perms[$cat] = array(
                'title' => $desc
            );
        }

        return $perms;
    }

}
