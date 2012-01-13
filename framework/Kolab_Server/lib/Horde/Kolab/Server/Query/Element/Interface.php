<?php
/**
 * Interface for server query elements.
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
 * Interface for server query elements.
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
interface Horde_Kolab_Server_Query_Element_Interface
{
    /**
     * Return the query element name.
     *
     * @return string The name of the query element.
     */
    public function getName();

    /**
     * Return the value of this element.
     *
     * @return mixed The query value.
     */
    public function getValue();

    /**
     * Return the elements of this group.
     *
     * @return mixed The group elements.
     */
    public function getElements();

    /**
     * Convert this element to a query element.
     *
     * @return mixedd The element as query.
     */
    public function convert(
        Horde_Kolab_Server_Query_Interface $writer
    );
}