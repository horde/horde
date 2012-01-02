<?php
/**
 * The factory for the notepads handler.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Mnemo
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache
 * @link     http://www.horde.org/apps/mnemo
 */

/**
 * The factory for the notepads handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @package  Mnemo
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache
 * @link     http://www.horde.org/apps/mnemo
 */
class Mnemo_Factory_Notepads
{
    /**
     * Notepads drivers already created.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return a Mnemo_Notepads instance.
     *
     * @return Mnemo_Notepads
     */
    public function create()
    {
        if (!isset($GLOBALS['conf']['notepads']['driver'])) {
            $driver = 'Default';
        } else {
            $driver = Horde_String::ucfirst($GLOBALS['conf']['notepads']['driver']);
        }
        if (empty($this->_instances[$driver])) {
            $class = 'Mnemo_Notepads_' . $driver;
            if (class_exists($class)) {
                $params = array();
                if (!empty($GLOBALS['conf']['share']['auto_create'])) {
                    $params['auto_create'] = true;
                }
                switch ($driver) {
                case 'Default':
                    $params['identity'] = $this->_injector->getInstance('Horde_Core_Factory_Identity')->create();
                    break;
                }
                $this->_instances[$driver] = new $class(
                    $GLOBALS['mnemo_shares'],
                    $GLOBALS['registry']->getAuth(),
                    $params
                );
            } else {
                throw new Mnemo_Exception(sprintf('Unable to load the definition of %s.', $class));
            }
        }
        return $this->_instances[$driver];
    }
}