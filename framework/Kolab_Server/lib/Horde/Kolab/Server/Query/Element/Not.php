<?php
/**
 * A negating element.
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
 * A negating element.
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
class Horde_Kolab_Server_Query_Element_Not
extends Horde_Kolab_Server_Query_Element_Group
{
    /**
     * Constructor.
     *
     * @param array  $elements The group elements.
     */
    public function __construct(
        Horde_Kolab_Server_Query_Element_Interface $element
    ) {
        parent::__construct(array($element));
    }

    /**
     * Convert this element to a query element.
     *
     * @return mixed The element as query.
     */
    public function convert(
        Horde_Kolab_Server_Query_Interface $writer
    ) {
        return $writer->convertNot($this);
    }
}