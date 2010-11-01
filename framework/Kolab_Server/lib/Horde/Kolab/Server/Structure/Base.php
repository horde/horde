<?php
/**
 * A simple structural handler for a tree of objects.
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
 * An abstract class definiing methods to deal with an object tree structure.
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
abstract class Horde_Kolab_Server_Structure_Base
implements Horde_Kolab_Server_Structure_Interface
{
    /** Maximum accepted level for the object class hierarchy */
    const MAX_HIERARCHY = 100;

    /**
     * A link to the composite server handler.
     *
     * @var Horde_Kolab_Server_Composite
     */
    private $_composite;

    /**
     * Finds object data matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The criteria for the search.
     * @param array                            $params   Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function find(
        Horde_Kolab_Server_Query_Element_Interface $criteria,
        array $params = array()
    ) {
        $query = new Horde_Kolab_Server_Query_Ldap($criteria, $this);
        return $this->_composite->server->find(
            (string) $query, $params
        );
    }

    /**
     * Finds all object data below a parent matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The criteria for the search.
     * @param string                           $parent   The parent to search below.
     * @param array                            $params   Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function findBelow(
        Horde_Kolab_Server_Query_Element_Interface $criteria,
        $parent,
        array $params = array()
    ) {
        $query = new Horde_Kolab_Server_Query_Ldap($criteria, $this);
        return $this->_composite->server->findBelow(
            (string) $query, $parent, $params
        );
    }

    /**
     * Set the composite server reference for this object.
     *
     * @param Horde_Kolab_Server_Composite $composite A link to the composite
     *                                                server handler.
     *
     * @return NULL
     */
    public function setComposite(
        Horde_Kolab_Server_Composite $composite
    ) {
        $this->_composite = $composite;
    }

    /**
     * Get the composite server reference for this object.
     *
     * @return Horde_Kolab_Server_Composite A link to the composite server
     *                                      handler.
     */
    public function getComposite()
    {
        return $this->_composite;
    }

    /**
     * Return the attributes supported by the given object class.
     *
     * @param string $class Determine the attributes for this class.
     *
     * @return array The supported attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the schema analysis fails.
     */
    public function getExternalAttributes($class)
    {
        $childclass = get_class($class);
        $classes    = array();
        $level      = 0;
        while ($childclass != 'Horde_Kolab_Server_Object_Top'
               && $level < self::MAX_HIERARCHY) {
            $classes[]  = $childclass;
            $childclass = get_parent_class($childclass);
            $level++;
        }

        /** Finally add the basic object class */
        $classes[] = $childclass;

        //@todo: Throw exception here
        if ($level == self::MAX_HIERARCHY) {
            if (isset($this->logger)) {
                $logger->err(sprintf('The maximal level of the object hierarchy has been exceeded for class \"%s\"!',
                                     $class));
            }
        }

        /**
         * Collect attributes from bottom to top.
         */
        $classes = array_reverse($classes);

        $attributes = array();

        foreach ($classes as $childclass) {
            $vars = get_class_vars($childclass);
            if (isset($vars['attributes'])) {
                $attributes = array_merge($vars['attributes'], $attributes);
            }
        }

        return $attributes;
    }


    public function getInternalAttributes($class)
    {
        return $this->mapExternalToInternalAttributes(
            $this->getExternalAttributes($class)
        );
    }

    public function getInternalAttributesForExternal($class, $external)
    {
        return $this->mapExternalToInternalAttributes((array) $external);
    }
}
