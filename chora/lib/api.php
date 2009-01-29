<?php
/**
 * Chora external API interface.
 *
 * This file defines Chora's external API interface. Other applications can
 * interact with Chora through this API.
 *
 * @package Chora
 */
@define('CHORA_BASE', dirname(__FILE__) . "/..");

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

function _chora_perms()
{
    static $perms = array();

    if (!empty($perms)) {
        return $perms;
    }

    @define('CHORA_BASE', dirname(__FILE__) . '/..');
    require_once CHORA_BASE . '/config/sourceroots.php';

    $perms['tree']['chora']['sourceroots'] = false;
    $perms['title']['chora:sourceroots'] = _("Repositories");

    // Run through every source repository
    foreach ($sourceroots as $sourceroot => $srconfig) {
        $perms['tree']['chora']['sourceroots'][$sourceroot] = false;
        $perms['title']['chora:sourceroots:' . $sourceroot] = $srconfig['name'];
    }

    return $perms;
}

