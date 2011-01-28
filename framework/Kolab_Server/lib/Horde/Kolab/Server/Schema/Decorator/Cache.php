<?php
/**
 * This class handles the db schema.
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
 * This class handles the db schema.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Schema_Decorator_Cache
implements Horde_Kolab_Server_Schema_Interface
{
    /** Maximum accepted level for the object class hierarchy */
    const MAX_HIERARCHY = 100;

    /**
     * A cache for object attribute definitions.
     *
     * @var array
     */
    protected $attributes;

    /**
     * A link to the composite server handler.
     *
     * @var Horde_Kolab_Server_Composite
     */
    protected $composite;

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
        $this->composite = $composite;
    }

    /**
     * Return the schema for the given objectClass.
     *
     * @param string $objectclass Fetch the schema for this objectClass.
     *
     * @return array The schema for the given objectClass.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    public function getObjectclassSchema($objectclass)
    {
        if (!empty($this->_config['schema_support'])) {
            $schema = $this->_getSchema();
            $info = $schema->get('objectclass', $objectclass);
            $this->_handleError($info, Horde_Kolab_Server_Exception::SYSTEM);
            return $info;
        }
        return parent::getObjectclassSchema($objectclass);
    }

    /**
     * Return the schema for the given attribute.
     *
     * @param string $attribute Fetch the schema for this attribute.
     *
     * @return array The schema for the given attribute.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    public function getAttributeSchema($attribute)
    {
        if (!empty($this->_config['schema_support'])) {
            $schema = $this->_getSchema();
            $info = $schema->get('attribute', $attribute);
            $this->_handleError($info, Horde_Kolab_Server_Exception::SYSTEM);
            return $info;
        }
        return parent::getAttributeSchema($attribute);
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
        if (!isset($this->attributes)) {
            if (isset($this->cache)) {
                register_shutdown_function(array($this, 'shutdown'));
            }
        }
        if (empty($this->attributes[$class])) {

            if (isset($this->cache)) {
                $this->attributes[$class] = @unserialize($cache->get('attributes_' . $class,
                                                                     $this->params['cache_lifetime']));
            }

            if (empty($this->attributes[$class])) {

                $childclass = $class;
                $classes    = array();
                $level      = 0;
                while ($childclass != 'Horde_Kolab_Server_Object'
                       && $level < self::MAX_HIERARCHY) {
                    $classes[]  = $childclass;
                    $childclass = get_parent_class($childclass);
                    $level++;
                }

                /** Finally add the basic object class */
                $classes[] = $childclass;

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

                $types = array('defined', 'required', 'derived', 'collapsed',
                               'defaults', 'locked', 'object_classes');
                foreach ($types as $type) {
                    $$type = array();
                }

                foreach ($classes as $childclass) {
                    $vars = get_class_vars($childclass);
                    if (isset($vars['init_attributes'])) {
                        foreach ($types as $type) {
                            /**
                             * If the user wishes to adhere to the schema
                             * information from the server we will skip the
                             * attributes defined within the object class here.
                             */
                            if (!empty($this->params['schema_override'])
                                && in_array($type, 'defined', 'required')) {
                                continue;
                            }
                            if (isset($vars['init_attributes'][$type])) {
                                $$type = array_merge($$type,
                                                     $vars['init_attributes'][$type]);
                            }
                        }
                    }
                }

                $attrs = array();

                foreach ($object_classes as $object_class) {
                    $info = $this->getObjectclassSchema($object_class);
                    if (isset($info['may'])) {
                        $defined = array_merge($defined, $info['may']);
                    }
                    if (isset($info['must'])) {
                        $defined  = array_merge($defined, $info['must']);
                        $required = array_merge($required, $info['must']);
                    }
                    foreach ($defined as $attribute) {
                        try {
                            $attrs[$attribute] = $this->getAttributeSchema($attribute);
                        } catch (Horde_Kolab_Server_Exception $e) {
                            /**
                             * If the server considers the attribute to be
                             * invalid we mark it.
                             */
                            $attrs[$attribute] = array('invalid' => true);
                        }
                    }
                    foreach ($required as $attribute) {
                        $attrs[$attribute]['required'] = true;
                    }
                    foreach ($locked as $attribute) {
                        $attrs[$attribute]['locked'] = true;
                    }
                    foreach ($defaults as $attribute => $default) {
                        $attrs[$attribute]['default'] = $default;
                    }
                    $attrs[Horde_Kolab_Server_Object::ATTRIBUTE_OC]['default'] = $object_classes;
                }
                foreach ($derived as $key => $attributes) {
                    $supported = true;
                    if (isset($attributes['base'])) {
                        foreach ($attributes['base'] as $attribute) {
                            /**
                             * Usually derived attribute are determined on basis
                             * of one or more attributes. If any of these is not
                             * supported the derived attribute should not be
                             * included into the set of supported attributes.
                             */
                            if (!isset($attrs[$attribute])) {
                                unset($derived[$attribute]);
                                $supported = false;
                                break;
                            }
                        }
                    }
                    if ($supported) {
                        $attrs[$key] = $attributes;
                    }
                }
                $check_collapsed = $collapsed;
                foreach ($check_collapsed as $key => $attributes) {
                    if (isset($attributes['base'])) {
                        foreach ($attributes['base'] as $attribute) {
                            /**
                             * Usually collapsed attribute are determined on basis
                             * of one or more attributes. If any of these is not
                             * supported the collapsed attribute should not be
                             * included into the set of supported attributes.
                             */
                            if (!isset($attrs[$attribute])) {
                                unset($collapsed[$attribute]);
                            }
                        }
                    }
                }
                $this->attributes[$class] = array($attrs,
                                                  array(
                                                      'derived'   => array_keys($derived),
                                                      'collapsed' => $collapsed,
                                                      'locked'    => $locked,
                                                      'required'  => $required));
            }
        }
        return $this->attributes[$class];
    }

    public function getInternalAttributes($class)
    {
    }

    /**
     * Stores the attribute definitions in the cache.
     *
     * @return Horde_Kolab_Server The concrete Horde_Kolab_Server reference.
     */
    public function shutdown()
    {
        if (isset($this->attributes)) {
            if (isset($this->cache)) {
                foreach ($this->attributes as $key => $value) {
                    $this->cache->set('attributes_' . $key, @serialize($value));
                }
            }
        }
    }


}