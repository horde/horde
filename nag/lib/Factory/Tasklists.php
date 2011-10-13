<?php
/**
 * The factory for the tasklists handler.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Nag
 */
class Nag_Factory_Tasklists
{
    /**
     * Tasklists drivers already created.
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
     * Return a Nag_Tasklists instance.
     *
     * @return Nag_Tasklists
     */
    public function create()
    {
        if (!isset($GLOBALS['conf']['tasklists']['driver'])) {
            $driver = 'Default';
        } else {
            $driver = Horde_String::ucfirst($GLOBALS['conf']['tasklists']['driver']);
        }
        if (empty($this->_instances[$driver])) {
            $class = 'Nag_Tasklists_' . $driver;
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
                    $GLOBALS['nag_shares'],
                    $GLOBALS['registry']->getAuth(),
                    $params
                );
            } else {
                throw new Nag_Exception(sprintf('Unable to load the definition of %s.', $class));
            }
        }
        return $this->_instances[$driver];
    }
}