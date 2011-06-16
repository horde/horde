<?php
/**
 * Horde_Injector factory to create Ulaform_Action instances.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @author  Vilius Šumskas <vilius@lnk.lt>
 * @package Ulaform
 */
class Ulaform_Factory_Action
{
    /**
     * Instances.
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
     * Return the Ulaform_Action:: instance.
     *
     * @param string $action  The action to use
     *
     * @return Ulaform_Action
     * @throws Ulaform_Exception
     */
    public function create($action)
    {
        $action = basename($action);
        if (!empty($this->_instances[$action])) {
            return $this->_instances[$action];
        }


        $key = $action;
        $action = Horde_String::ucfirst($action);

        $class = 'Ulaform_Action_' . $action;
        if (!class_exists($class)) {
            throw new Ulaform_Exception(sprintf('Unable to load the definition of %s.', $class));
        }

        switch ($class) {
        case 'Ulaform_Action_Sql':
            $params = Horde::getDriverConfig('sql', $action);
            $params = array(
                'db' => $this->_injector->getInstance('Horde_Db_Adapter'),
                'charset' => $params['charset'],
            );
            break;
        case 'Ulaform_Action_Mailto':
        case 'Ulaform_Action_Template':
        }

        $action = new $class($params);
        $this->_instances[$key] = $action;

        return $this->_instances[$key];
    }
}
