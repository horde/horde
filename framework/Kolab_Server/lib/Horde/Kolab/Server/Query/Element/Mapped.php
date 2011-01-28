<?php
/**
 * A mapped query element.
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
 * A mapped query element.
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
class Horde_Kolab_Server_Query_Element_Mapped
implements Horde_Kolab_Server_Query_Element_Interface
{
    /**
     * Delegated element.
     *
     * @var Horde_Kolab_Server_Query_Element
     */
    private $_element;

    /**
     * Name mapper.
     *
     * @var Horde_Kolab_Server_Mapped
     */
    private $_mapper;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server_Query_Element $element The mapped element.
     * @param Horde_Kolab_Server_Mapped        $mapper  The mapping handler.
     */
    public function __construct(
        Horde_Kolab_Server_Query_Element_Interface $element,
        Horde_Kolab_Server_Decorator_Map $mapper
    ) {
        $this->_element = $element;
        $this->_mapper  = $mapper;
    }

    /**
     * Return the query element name.
     *
     * @return string The name of the query element.
     */
    public function getName()
    {
        return $this->_mapper->mapField($this->_element->getName());
    }

    /**
     * Return the value of this element.
     *
     * @return mixed The query value.
     */
    public function getValue()
    {
        return $this->_element->getValue();
    }

    /**
     * Return the elements of this group.
     *
     * @return mixed The group elements.
     */
    public function getElements()
    {
        $elements = array();
        foreach ($this->_element->getElements() as $element) {
            $elements[] = new Horde_Kolab_Server_Query_Element_Mapped(
                $element, $this->_mapper
            );
        }
        return $elements;
    }

    /**
     * Convert this element to a string.
     *
     * @return string The query string of the element.
     */
    public function convert(
        Horde_Kolab_Server_Query_Interface $writer
    ) {
        return $this->_element->convert($writer);
    }
}