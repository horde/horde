<?php
/**
 * The factory for the calendars handler.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kronolith
 */
class Kronolith_Factory_Calendars
{
    /**
     * Calendars drivers already created.
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
     * Return a Kronolith_Calendars_Base instance.
     *
     * @return Kronolith_Calendars_Base
     */
    public function create()
    {
        global $conf, $injector, $registry;

        switch ($conf['calendar']['driver']) {
        case 'sql':
            $driver = 'Default';
            break;
        default:
            $driver = Horde_String::ucfirst($conf['calendar']['driver']);
            break;
        }
        if (empty($this->_instances[$driver])) {
            $class = 'Kronolith_Calendars_' . $driver;
            if (class_exists($class)) {
                $params = array();
                switch ($driver) {
                case 'Default':
                    $params['identity'] = $this->_injector
                        ->getInstance('Horde_Core_Factory_Identity')
                        ->create();
                    break;
                }
                $this->_instances[$driver] = new $class(
                    $injector->getInstance('Kronolith_Shares'),
                    $registry->getAuth(),
                    $params
                );
            } else {
                throw new Kronolith_Exception(
                    sprintf('Unable to load the definition of %s.', $class)
                );
            }
        }
        return $this->_instances[$driver];
    }
}
