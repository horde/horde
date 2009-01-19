<?php
/**
 * Babel external API interface.
 *
 * This file defines Babel's external API interface. Other applications can
 * interact with Babel through this API.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */

$_services['perms'] = array(
    'args' => array(),
    // This is actually a hash of hashes
    'type' => '{urn:horde}hash'
);

function _translation_perms()
{

    global $nls, $registry;
    
    static $perms = array();
    if (!empty($perms)) {
	return $perms;
    }

    $perms['tree']['translation']['language'] = array();
    $perms['title']['translation:language'] = _("Languages");
    $perms['type']['translation:language']  = 'none';
    
    foreach($nls['languages'] as $langcode => $langdesc) {
	$perms['tree']['translation']['language'][$langcode] = false;
	$perms['title']['translation:language:' . $langcode] = sprintf("%s (%s)", $langdesc, $langcode);
	$perms['type']['translation:language:' . $langcode] = 'boolean';
    }

    $perms['tree']['translation']['module'] = array();
    $perms['title']['translation:module'] = _("Modules");
    $perms['type']['translation:module']  = 'none';
    
    foreach ($registry->applications as $app => $params) {
	if ($params['status'] == 'heading' || $params['status'] == 'block') {
	    continue;
	}
	
	if (isset($params['fileroot']) && !is_dir($params['fileroot'])) {
	    continue;
	}
	
	if (preg_match('/_reports$/', $app) || preg_match('/_tools$/', $app)) {
	    continue;
	}
	
	$perms['tree']['translation']['module'][$app] = false;
	$perms['title']['translation:module:' . $app] = sprintf("%s (%s)", $params['name'], $app);
	$perms['type']['translation:module:' . $app] = 'boolean';
    }

    $tabdesc['download']   = _("Download");
    $tabdesc['upload']     = _("Upload");
    $tabdesc['stats']      = _("Statistics");
    $tabdesc['view']       = _("View/Edit");
    $tabdesc['viewsource'] = _("View Source");
    $tabdesc['extract']    = _("Extract");
    $tabdesc['make']       = _("Make");
    $tabdesc['commit']     = _("Commit");
    $tabdesc['reset']      = _("Reset");
    
    foreach ($tabdesc as $cat => $desc) {
	$perms['tree']['translation'][$cat] = array();
	$perms['title']['translation:' . $cat] = $desc;
    }
    
    return $perms;
    
}
