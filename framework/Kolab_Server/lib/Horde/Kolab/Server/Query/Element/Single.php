<?php
/**
 * A single query element.
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
 * A single query element.
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
abstract class Horde_Kolab_Server_Query_Element_Single
implements Horde_Kolab_Server_Query_Element_Interface
{
    /**
     * The element name.
     *
     * @var string
     */
    protected $_name;

    /**
     * The comparison value.
     *
     * @var mixed
     */
    protected $_value;

    /**
     * Constructor.
     *
     * @param string $name  The element name.
     * @param mixed  $value The comparison value.
     */
    public function __construct($name, $value)
    {
        $this->_name  = $name;
        $this->_value = $value;
    }

    /**
     * Return the query element name.
     *
     * @return string The name of the query element.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the value of this element.
     *
     * @return mixed The query value.
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Return the elements of this group.
     *
     * This should never be called for single elements.
     *
     * @return mixed The group elements.
     */
    public function getElements()
    {
        throw new Exception('Not supported!');
    }
}