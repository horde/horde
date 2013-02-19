<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Horde_Content
 */
class Content_Indexer
{
    /**
     * ElasticSearch client
     * @var Horde_ElasticSearch_Client
     */
    protected $_es;

    /**
     * User manager object
     * @var Content_Users_Manager
     */
    protected $_userManager;

    /**
     * Type management object
     * @var Content_Types_Manager
     */
    protected $_typeManager;

    /**
     * Object manager
     * @var Content_Objects_Manager
     */
    protected $_objectManager;

    /**
     * Constructor
     */
    public function __construct(Horde_ElasticSearch_Client $es,
                                Content_Users_Manager $userManager,
                                Content_Types_Manager $typeManager,
                                Content_Objects_Manager $objectManager)
    {
        $this->_es = $es;
        $this->_userManager = $userManager;
        $this->_typeManager = $typeManager;
        $this->_objectManager = $objectManager;
    }

    public function index($index, $type, $id, $data)
    {
        try {
            $this->_es->add($index, $type, $id, $data);
        } catch (Horde_ElasticSearch_Exception $e) {
            throw new Content_Exception($e);
        }
    }

    public function search($index, $type, $query)
    {
        try {
            return $this->_es->search($index, $type, $query);
        } catch (Horde_ElasticSearch_Exception $e) {
            throw new Content_Exception($e);
        }
    }

    /**
     * Convenience method - if $object is an array, it is taken as an array of
     * 'object' and 'type' to pass to objectManager::ensureObjects() if it's a
     * scalar value, it's taken as the object_id and simply returned.
     */
    protected function _ensureObject($object)
    {
        if (is_array($object)) {
            $object = current($this->_objectManager->ensureObjects(
                $object['object'], (int)current($this->_typeManager->ensureTypes($object['type']))));
        }

        return (int)$object;
    }
}
