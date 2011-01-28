<?php
/**
 * Horde Turba wrapper to distinguish between both Kolab driver
 * implementations.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver_Kolab_Wrapper
{
    /**
     * Indicates if the wrapper has connected or not
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * String containing the current addressbook name.
     *
     * @var string
     */
    protected $_addressbook = '';

    /**
     * Our Kolab server connection.
     *
     * @var Kolab
     */
    protected $_kolab = null;

    /**
     * Constructor
     *
     * @param string      $addressbook  The addressbook to load.
     * @param Horde_Kolab $kolab        The Kolab connection object
     */
    public function __construct($addressbook, &$kolab)
    {
        if ($addressbook && $addressbook[0] == '_') {
            $addressbook = substr($addressbook, 1);
        }
        $this->_addressbook = $addressbook;
        $this->_kolab = &$kolab;
    }

    /**
     * Connect to the Kolab backend
     *
     * @param integer $loader  The version of the XML loader.
     *
     * @throws Turba_Exception
     */
    public function connect($loader = 0)
    {
        if (!$this->_connected) {
            $result = $this->_kolab->open($this->_addressbook, $loader);
            if ($result instanceof PEAR_Error) {
                throw new Turba_Exception($result);
            }
        }

        $this->_connected = true;
    }

}
