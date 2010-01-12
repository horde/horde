<?php
/**
 * A factory that generates connections using the Horde_Injector.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * A factory that generates connections using the Horde_Injector.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Factory_Connection_Injector
extends Horde_Kolab_Server_Factory_Connection_Base
{
    /**
     * The injector providing our context.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server_Connection The server connection.
     */
    public function getConnection()
    {
        $factory = $this->_injector->getInstance(
            'Horde_Kolab_Server_Factory_Connection_Interface'
        );
        $factory->setConfiguration(
            $this->_injector->getInstance(
                'Horde_Kolab_Server_Configuration'
            )
        );
        $connection = $factory->getConnection();
        return $connection;
    }
}