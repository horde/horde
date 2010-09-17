<?php
/**
 * Handler for LDAP server queries.
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
 * Handler for LDAP server queries.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Query_Ldap
implements Horde_Kolab_Server_Query_Interface
{
    /**
     * The query criteria.
     *
     * @var Horde_Kolab_Server_Query_Element
     */
    private $_criteria;

    /**
     * The db structure.
     *
     * @var Horde_Kolab_Server_Structure
     */
    private $_structure;

    /**
     * Constructor.
     *
     * @param array $criteria The query criteria.
     */
    public function __construct(
        Horde_Kolab_Server_Query_Element_Interface $criteria,
        Horde_Kolab_Server_Structure_Interface $structure
    ) {
        $this->_criteria  = $criteria;
        $this->_structure = $structure;
    }

    /**
     * Return the query as a string.
     *
     * @return string The query in string format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function __toString()
    {
        try {
            $filter = $this->_criteria->convert($this);
            return (string)$filter;
        } catch (Horde_Kolab_Server_Exception $e) {
            return '';
        }
    }

    /**
     * Convert the equals element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Equals $equals The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertEquals(Horde_Kolab_Server_Query_Element_Equals $equals)
    {
        return $this->_convertSingle($equals, 'equals');
    }

    /**
     * Convert the begins element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Begins $begins The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertBegins(Horde_Kolab_Server_Query_Element_Begins $begins)
    {
        return $this->_convertSingle($begins, 'begins');
    }

    /**
     * Convert the ends element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Ends $ends The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertEnds(Horde_Kolab_Server_Query_Element_Ends $ends)
    {
        return $this->_convertSingle($ends, 'ends');
    }

    /**
     * Convert the contains element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Contains $contains The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertContains(Horde_Kolab_Server_Query_Element_Contains $contains)
    {
        return $this->_convertSingle($contains, 'contains');
    }

    /**
     * Convert the less element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Less $less The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertLess(Horde_Kolab_Server_Query_Element_Less $less)
    {
        return $this->_convertSingle($less, 'less');
    }

    /**
     * Convert the greater element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Grater $grater The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertGreater(Horde_Kolab_Server_Query_Element_Greater $greater)
    {
        return $this->_convertSingle($greater, 'greater');
    }

    /**
     * Convert the approx element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Approx $approx The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertApprox(Horde_Kolab_Server_Query_Element_Approx $approx)
    {
        return $this->_convertSingle($approx, 'approx');
    }

    /**
     * Convert the single element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Single $single   The element to convert.
     * @param string                                  $operator The element operation.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    private function _convertSingle(
        Horde_Kolab_Server_Query_Element_Single $single,
        $operator
    ) {
        try {
            return Horde_Ldap_Filter::create(
                $this->_structure->mapExternalToInternalAttribute(
                    $single->getName()
                ),
                $operator,
                $single->getValue()
            );
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Kolab_Server_Exception(
                $e->getMessage(),
                Horde_Kolab_Server_Exception::INVALID_QUERY,
                $e
            );
        }
    }

    /**
     * Convert the not element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Group $group The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertNot(Horde_Kolab_Server_Query_Element_Not $not)
    {
        $elements = $not->getElements();
        try {
            return Horde_Ldap_Filter::combine('!', $elements[0]->convert($this));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Kolab_Server_Exception(
                $e->getMessage(),
                Horde_Kolab_Server_Exception::INVALID_QUERY,
                $e
            );
        }
    }

    /**
     * Convert the and element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Group $group The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertAnd(Horde_Kolab_Server_Query_Element_And $and)
    {
        return $this->_convertGroup($and, '&');
    }

    /**
     * Convert the or element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Group $group The element to convert.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function convertOr(Horde_Kolab_Server_Query_Element_Group $or)
    {
        return $this->_convertGroup($or, '|');
    }

    /**
     * Convert the group element to query format.
     *
     * @param Horde_Kolab_Server_Query_Element_Group $group    The element to convert.
     * @param string                                 $operator The element operation.
     *
     * @return mixed The query element in query format.
     *
     * @throws Horde_Kolab_Server_Exception If the query is malformed.
     */
    public function _convertGroup(
        Horde_Kolab_Server_Query_Element_Group $group,
        $operator
    ) {
        $filters = array();
        foreach ($group->getElements() as $element) {
            $filters[] = $element->convert($this);
        }
        try {
            return Horde_Ldap_Filter::combine($operator, $filters);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Kolab_Server_Exception(
                $e->getMessage(),
                Horde_Kolab_Server_Exception::INVALID_QUERY,
                $e
            );
        }
    }
}