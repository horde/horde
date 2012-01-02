<?php
/**
 * Horde_Injector factory to create Agora_Driver instances.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @author  Vilius Šumskas <vilius@lnk.lt>
 * @package Agora
 */
class Agora_Factory_Driver
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
     * Return the Agora_Driver:: instance.
     *
     * @param string $scope  Instance scope
     * @param int $forum_id  Forum to link to
     *
     * @return Agora_Driver  The singleton instance.
     * @throws Agora_Exception
     */
    public function create($scope = 'agora', $forum_id = 0)
    {
        if (!isset($this->_instances[$scope])) {
            $driver = $GLOBALS['conf']['threads']['split'] ? 'SplitSql' : 'Sql';
            $params = Horde::getDriverConfig('sql');

            $class = 'Agora_Driver_' . $driver;
            if (!class_exists($class)) {
                throw new Agora_Exception(sprintf('Unable to load the definition of %s.', $class));
            }

            $params = array(
                'db' => $this->_injector->getInstance('Horde_Db_Adapter'),
                'charset' => $params['charset'],
            );

            $driver = new $class($scope, $params);
            $this->_instances[$scope] = $driver;
        }

        if ($forum_id) {
            /* Check if there was a valid forum object to get. */
            try {
                $forum = $this->_instances[$scope]->getForum($forum_id);
            } catch (Horde_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }

            /* Set current forum id and forum data */
            $this->_instances[$scope]->_forum = $forum;
            $this->_instances[$scope]->_forum_id = (int)$forum_id;
        }

        return $this->_instances[$scope];
    }
}
