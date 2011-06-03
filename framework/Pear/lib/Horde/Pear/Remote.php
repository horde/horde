<?php
/**
 * Remote access to a PEAR server.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Remote access to a PEAR server.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Remote
{
    /**
     * The tool generator for accessing the REST interface of the PEAR server.
     *
     * @var Horde_Pear_Rest_Access
     */
    private $_access;

    /**
     * Constructor
     *
     * @param string                 $server The server name.
     * @param Horde_Pear_Rest_Access $access The accessor to the PEAR server
     *                                       rest interface.
     */
    public function __construct(
        $server = 'pear.horde.org',
        Horde_Pear_Rest_Access $access = null
    )
    {
        if ($access === null) {
            $this->_access = new Horde_Pear_Rest_Access();
        } else {
            $this->_access = $access;
        }
        $this->_access->setServer($server);
    }

    public function listPackages()
    {
        return $this->_access->getPackageList()->listPackages();
    }
}