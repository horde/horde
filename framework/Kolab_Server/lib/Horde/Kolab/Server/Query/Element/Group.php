<?php
/**
 * A grouped query element.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * A grouped query element.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
abstract class Horde_Kolab_Server_Query_Element_Group
implements Horde_Kolab_Server_Query_Element_Interface
{
    /**
     * The group elements.
     *
     * @var array
     */
    protected $_elements;

    /**
     * Constructor.
     *
     * @param array $elements The group elements.
     */
    public function __construct(array $elements)
    {
        $this->_elements = $elements;
    }

    /**
     * Return the query element name.
     *
     * This should never be called for group elements.
     *
     * @return string The name of the query element.
     */
    public function getName()
    {
        throw new Horde_Kolab_Server_Exception('Not supported!');
    }

    /**
     * Return the value of this element.
     *
     * This should never be called for group elements.
     *
     * @return mixed The query value.
     */
    public function getValue()
    {
        throw new Horde_Kolab_Server_Exception('Not supported!');
    }

    /**
     * Return the elements of this group.
     *
     * @return mixed The group elements.
     */
    public function getElements()
    {
        return $this->_elements;
    }
}