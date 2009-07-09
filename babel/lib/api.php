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

function _babel_perms()
{

    global $registry;
    
    static $perms = array();
    if (!empty($perms)) {
	return $perms;
    }

    $perms['tree']['babel']['language'] = array();
    $perms['title']['babel:language'] = _("Languages");
    $perms['type']['babel:language']  = 'none';
    
    foreach(Horde_Nls::$config['languages'] as $langcode => $langdesc) {
	$perms['tree']['babel']['language'][$langcode] = false;
	$perms['title']['babel:language:' . $langcode] = sprintf("%s (%s)", $langdesc, $langcode);
	$perms['type']['babel:language:' . $langcode] = 'boolean';
    }

    $perms['tree']['babel']['module'] = array();
    $perms['title']['babel:module'] = _("Modules");
    $perms['type']['babel:module']  = 'none';
    
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
	
	$perms['tree']['babel']['module'][$app] = false;
	$perms['title']['babel:module:' . $app] = sprintf("%s (%s)", $params['name'], $app);
	$perms['type']['babel:module:' . $app] = 'boolean';
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
	$perms['tree']['babel'][$cat] = array();
	$perms['title']['babel:' . $cat] = $desc;
    }
    
    return $perms;
    
}
