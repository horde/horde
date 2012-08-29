<?php
/**
 * Pastie_Driver:: defines an API for implementing storage backends for
 * Pastie.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Pastie
 */
abstract class Pastie_Driver
{
    /**
     * Pastie Driver constructor
     * @param array $params  an array of driver connection parameters
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }
}
