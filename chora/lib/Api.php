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
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * The services provided by this application.
     *
     * @var array
     */
    public $services = array(
        'perms' => array(
            'args' => array(),
            'type' => '{urn:horde}hashHash'
        ),

        'prefsMenu' => array(
            'args' => array(),
            'type' => 'object'
        )
    );

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

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Chora::getMenu();
    }

}
