<?php
/**
 * Interface for server queries.
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
 * Interface for server queries.
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
interface Horde_Kolab_Server_Query_Interface
{
    /**
     * Return the query as a string.
     *
     * @return string The query in string format.
     */
    public function __toString();

    /**
     * Convert the equals element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Single $single The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertEquals(Horde_Kolab_Server_Query_Element_Equals $equals);

    /**
     * Convert the begins element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Single $single The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertBegins(Horde_Kolab_Server_Query_Element_Begins $begins);

    /**
     * Convert the ends element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Single $single The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertEnds(Horde_Kolab_Server_Query_Element_Ends $ends);

    /**
     * Convert the contains element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Single $single The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertContains(Horde_Kolab_Server_Query_Element_Contains $contains);

    /**
     * Convert the less element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Single $single The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertLess(Horde_Kolab_Server_Query_Element_Less $less);

    /**
     * Convert the greater element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Single $single The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertGreater(Horde_Kolab_Server_Query_Element_Greater $greater);

    /**
     * Convert the approx element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Single $single The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertApprox(Horde_Kolab_Server_Query_Element_Approx $approx);

    /**
     * Convert the not element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Group $group The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertNot(Horde_Kolab_Server_Query_Element_Not $not);

    /**
     * Convert the and element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Group $group The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertAnd(Horde_Kolab_Server_Query_Element_And $and);

    /**
     * Convert the or element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Group $group The element to convert.
     *
     * @return mixed The query element in query format.
     */
    public function convertOr(Horde_Kolab_Server_Query_Element_Group $or);
}