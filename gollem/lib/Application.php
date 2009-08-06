<?php
/**
 * Gollem application API.
 *
 * This file defines Gollem's external API interface. Other
 * applications can interact with Gollem through this API.
 *
 * @author  Amith Varghese (amith@xalan.com)
 * @author  Michael Slusarz (slusarz@curecanti.org)
 * @author  Ben Klang (bklang@alkaloid.net)
 * @package Gollem
 */
class Gollem_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';
}
