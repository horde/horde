<?php
/**
 * Class that can be extended to save arbitrary information as part of a stored
 * object.
 *
 * @author  Stephane Huther <shuther1@free.fr>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package DataTree
 */
class Horde_DataTreeObject {

    /**
     * This object's Horde_DataTree instance.
     *
     * @var Horde_DataTree
     */
    var $datatree;

    /**
     * Key-value hash that will be serialized.
     *
     * @see getData()
     * @var array
     */
    var $data = array();

    /**
     * The unique name of this object.
     * These names have the same requirements as other object names - they must
     * be unique, etc.
     *
     * @var string
     */
    var $name;

    /**
     * If this object has ordering data, store it here.
     *
     * @var integer
     */
    var $order = null;

    /**
     * Horde_DataTreeObject constructor.
     * Just sets the $name parameter.
     *
     * @param string $name  The object name.
     */
    function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * Sets the {@link Horde_DataTree} instance used to retrieve this object.
     *
     * @param Horde_DataTree $datatree  A {@link Horde_DataTree} instance.
     */
    function setDataTree(&$datatree)
    {
        $this->datatree = &$datatree;
    }

    /**
     * Gets the name of this object.
     *
     * @return string The object name.
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of this object.
     *
     * NOTE: Use with caution. This may throw out of sync the cached datatree
     * tables if not used properly.
     *
     * @param string $name  The name to set this object's name to.
     */
    function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the short name of this object.
     * For display purposes only.
     *
     * @return string  The object's short name.
     */
    function getShortName()
    {
        return Horde_DataTree::getShortName($this->name);
    }

    /**
     * Gets the ID of this object.
     *
     * @return string  The object's ID.
     */
    function getId()
    {
        return $this->datatree->getId($this);
    }

    /**
     * Gets the data array.
     *
     * @return array  The internal data array.
     */
    function getData()
    {
        return $this->data;
    }

    /**
     * Sets the data array.
     *
     * @param array  The data array to store internally.
     */
    function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Sets the order of this object in its object collection.
     *
     * @param integer $order
     */
    function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Returns this object's parent.
     *
     * @param string $class   Subclass of Horde_DataTreeObject to use. Defaults to
     *                        Horde_DataTreeObject. Null forces the driver to look
     *                        into the attributes table to determine the
     *                        subclass to use. If none is found it uses
     *                        Horde_DataTreeObject.
     *
     * @return Horde_DataTreeObject  This object's parent
     */
    function &getParent($class = 'Horde_DataTreeObject')
    {
        $id = $this->datatree->getParent($this);
        if (is_a($id, 'PEAR_Error')) {
            return $id;
        }
        return $this->datatree->getObjectById($id, $class);
    }

    /**
     * Returns a child of this object.
     *
     * @param string $name         The child's name.
     * @param boolean $autocreate  If true and no child with the given name
     *                             exists, one gets created.
     */
    function &getChild($name, $autocreate = true)
    {
        $name = $this->getShortName() . ':' . $name;

        /* If the child shouldn't get created, we don't check for its
         * existance to return the "not found" error of
         * getObject(). */
        if (!$autocreate || $this->datatree->exists($name)) {
            $child = &$this->datatree->getObject($name);
        } else {
            $child = new Horde_DataTreeObject($name);
            $child->setDataTree($this->datatree);
            $this->datatree->add($child);
        }

        return $child;
    }

    /**
     * Saves any changes to this object to the backend permanently. New objects
     * are added instead.
     *
     * @return boolean|PEAR_Error  PEAR_Error on failure.
     */
    function save()
    {
        if ($this->datatree->exists($this)) {
            return $this->datatree->updateData($this);
        } else {
            return $this->datatree->add($this);
        }
    }

    /**
     * Delete this object from the backend permanently.
     *
     * @return boolean|PEAR_Error  PEAR_Error on failure.
     */
    function delete()
    {
        return $this->datatree->remove($this);
    }

    /**
     * Gets one of the attributes of the object, or null if it isn't defined.
     *
     * @param string $attribute  The attribute to get.
     *
     * @return mixed  The value of the attribute, or null.
     */
    function get($attribute)
    {
        return isset($this->data[$attribute])
            ? $this->data[$attribute]
            : null;
    }

    /**
     * Sets one of the attributes of the object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     */
    function set($attribute, $value)
    {
        $this->data[$attribute] = $value;
    }

}
