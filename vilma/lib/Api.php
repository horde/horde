<?php
/**
 * Vilma external API interface.
 *
 * This file defines Vilma's external API interface. Other applications
 * can interact with Vilma through this API.
 *
 * Copyright 2006-2007 Alkaloid Networks <http://www.alkaloid.net/>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Vilma
 */
class Vilma_Api extends Horde_Registry_Api
{
    public function listDomains()
    {
        $domains = array();
        foreach ($GLOBALS['vilma']->driver->getDomains() as $domain) {
            $domains[] = $domain['domain_name'];
        }
        return $domains;
    }
}
