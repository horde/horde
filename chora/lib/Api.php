<?php
/**
 * Chora external API interface.
 *
 * This file defines Chora's external API interface. Other applications can
 * interact with Chora through this API.
 *
 * @package Chora
 */
class Chora_Api extends Horde_Registry_Api
{
    public function perms()
    {
        static $perms = array();

        if (!empty($perms)) {
            return $perms;
        }

        require_once dirname(__FILE__) . '/../config/sourceroots.php';

        $perms['tree']['chora']['sourceroots'] = false;
        $perms['title']['chora:sourceroots'] = _("Repositories");

        // Run through every source repository
        foreach ($sourceroots as $sourceroot => $srconfig) {
            $perms['tree']['chora']['sourceroots'][$sourceroot] = false;
            $perms['title']['chora:sourceroots:' . $sourceroot] = $srconfig['name'];
        }

        return $perms;
    }

}
