<?php
/**
 * Object representation of a part of a LDAP filter.
 *
 * The purpose of this class is to easily build LDAP filters without having to
 * worry about correct escaping etc.
 *
 * A filter is built using several independent filter objects which are
 * combined afterwards. This object works in two modes, depending how the
 * object is created.
 *
 * If the object is created using the {@link create()} method, then this is a
 * leaf-object. If the object is created using the {@link combine()} method,
 * then this is a container object.
 *
 * LDAP filters are defined in RFC 2254.
 *
 * @see http://www.ietf.org/rfc/rfc2254.txt
 *
 * A short example:
 * <code>
 * $filter0     = Horde_Ldap_Filter::create('stars', 'equals', '***');
 * $filter_not0 = Horde_Ldap_Filter::combine('not', $filter0);
 *
 * $filter1     = Horde_Ldap_Filter::create('gn', 'begins', 'bar');
 * $filter2     = Horde_Ldap_Filter::create('gn', 'ends', 'baz');
 * $filter_comp = Horde_Ldap_Filter::combine('or', array($filter_not0, $filter1, $filter2));
 *
 * echo (string)$filter_comp;
 * // This will output: (|(!(stars=\0x5c0x2a\0x5c0x2a\0x5c0x2a))(gn=bar*)(gn=*baz))
 * // The stars in $filter0 are treaten as real stars unless you disable escaping.
 * </code>
 *
 * @category  Horde
 * @package   Ldap
 * @author    Benedikt Hallinger <beni@php.net>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2009 Benedikt Hallinger
 * @copyright 2010 The Horde Project
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL
 */
class Horde_Ldap_Filter
{
    /**
     * Storage for combination of filters.
     *
     * This variable holds a array of filter objects that should be combined by
     * this filter object.
     *
     * @var array
     */
    protected $_filters = array();

    /**
     * Operator for sub-filters.
     *
     * @var string
     */
    protected $_operator;

    /**
     * Single filter.
     *
     * If this is a leaf filter, the filter representation is store here.
     *
     * @var string
     */
    protected $_filter;

    /**
     * Constructor.
     *
     * Construction of Horde_Ldap_Filter objects should happen through either
     * {@link create()} or {@link combine()} which give you more control.
     * However, you may use the constructor if you already have generated
     * filters.
     *
     * @param array $params List of object parameters
     */
    protected function __construct(array $params)
    {
        foreach ($params as $param => $value) {
            if (in_array($param, array('filter', 'filters', 'operator'))) {
                $this->{'_' . $param} = $value;
            }
        }
    }

    /**
     * Creates a new part of an LDAP filter.
     *
     * The following matching rules exists:
     * - equals:         One of the attributes values is exactly $value.
     *                   Please note that case sensitiviness depends on the
     *                   attributes syntax configured in the server.
     * - begins:         One of the attributes values must begin with $value.
     * - ends:           One of the attributes values must end with $value.
     * - contains:       One of the attributes values must contain $value.
     * - present | any:  The attribute can contain any value but must exist.
     * - greater:        The attributes value is greater than $value.
     * - less:           The attributes value is less than $value.
     * - greaterOrEqual: The attributes value is greater or equal than $value.
     * - lessOrEqual:    The attributes value is less or equal than $value.
     * - approx:         One of the attributes values is similar to $value.
     *
     * If $escape is set to true then $value will be escaped. If set to false
     * then $value will be treaten as a raw filter value string.  You should
     * then escape it yourself using {@link
     * Horde_Ldap_Util::escapeFilterValue()}.
     *
     * Examples:
     * <code>
     * // This will find entries that contain an attribute "sn" that ends with
     * // "foobar":
     * $filter = Horde_Ldap_Filter::create('sn', 'ends', 'foobar');
     *
     * // This will find entries that contain an attribute "sn" that has any
     * // value set:
     * $filter = Horde_Ldap_Filter::create('sn', 'any');
     * </code>
     *
     * @param string  $attribute Name of the attribute the filter should apply
     *                           to.
     * @param string  $match     Matching rule (equals, begins, ends, contains,
     *                           greater, less, greaterOrEqual, lessOrEqual,
     *                           approx, any).
     * @param string  $value     If given, then this is used as a filter value.
     * @param boolean $escape    Should $value be escaped?
     *
     * @return Horde_Ldap_Filter
     * @throws Horde_Ldap_Exception
     */
    public static function create($attribute, $match, $value = '',
                                  $escape = true)
    {
        if ($escape) {
            $array = Horde_Ldap_Util::escapeFilterValue(array($value));
            $value = $array[0];
        }

        switch (Horde_String::lower($match)) {
        case 'equals':
        case '=':
            $filter = '(' . $attribute . '=' . $value . ')';
            break;
        case 'begins':
            $filter = '(' . $attribute . '=' . $value . '*)';
            break;
        case 'ends':
            $filter = '(' . $attribute . '=*' . $value . ')';
            break;
        case 'contains':
            $filter = '(' . $attribute . '=*' . $value . '*)';
            break;
        case 'greater':
        case '>':
            $filter = '(' . $attribute . '>' . $value . ')';
            break;
        case 'less':
        case '>':
            $filter = '(' . $attribute . '<' . $value . ')';
            break;
        case 'greaterorequal':
        case '>=':
            $filter = '(' . $attribute . '>=' . $value . ')';
            break;
        case 'lessorequal':
        case '<=':
            $filter = '(' . $attribute . '<=' . $value . ')';
            break;
        case 'approx':
        case '~=':
            $filter = '(' . $attribute . '~=' . $value . ')';
            break;
        case 'any':
        case 'present':
            $filter = '(' . $attribute . '=*)';
            break;
        default:
            throw new Horde_Ldap_Exception('Matching rule "' . $match . '" unknown');
        }

        return new Horde_Ldap_Filter(array('filter' => $filter));

    }

    /**
     * Combines two or more filter objects using a logical operator.
     *
     * Example:
     * <code>
     * $filter = Horde_Ldap_Filter::combine('or', array($filter1, $filter2));
     * </code>
     *
     * If the array contains filter strings instead of filter objects, they
     * will be parsed.
     *
     * @param string $operator
     *     The logical operator, either "and", "or", "not" or the logical
     *     equivalents "&", "|", "!".
     * @param array|Horde_Ldap_Filter|string $filters
     *     Array with Horde_Ldap_Filter objects and/or strings or a single
     *     filter when using the "not" operator.
     *
     * @return Horde_Ldap_Filter
     * @throws Horde_Ldap_Exception
     */
    public static function combine($operator, $filters)
    {
        // Substitute named operators with logical operators.
        switch ($operator) {
        case 'and': $operator = '&'; break;
        case 'or':  $operator = '|'; break;
        case 'not': $operator = '!'; break;
        }

        // Tests for sane operation.
        switch ($operator) {
        case '!':
            // Not-combination, here we only accept one filter object or filter
            // string.
            if ($filters instanceof Horde_Ldap_Filter) {
                $filters = array($filters); // force array
            } elseif (is_string($filters)) {
                $filters = array(self::parse($filters));
            } elseif (is_array($filters)) {
                throw new Horde_Ldap_Exception('Operator is "not" but $filter is an array');
            } else {
                throw new Horde_Ldap_Exception('Operator is "not" but $filter is not a valid Horde_Ldap_Filter nor a filter string');
            }
            break;

        case '&':
        case '|':
            if (!is_array($filters) || count($filters) < 2) {
                throw new Horde_Ldap_Exception('Parameter $filters is not an array or contains less than two Horde_Ldap_Filter objects');
            }
        break;

        default:
            throw new Horde_Ldap_Exception('Logical operator is unknown');
        }

        foreach ($filters as $key => $testfilter) {
            // Check for errors.
            if (is_string($testfilter)) {
                // String found, try to parse into an filter object.
                $filters[$key] = self::parse($testfilter);
            } elseif (!($testfilter instanceof Horde_Ldap_Filter)) {
                throw new Horde_Ldap_Exception('Invalid object passed in array $filters!');
            }
        }

        return new Horde_Ldap_Filter(array('filters' => $filters,
                                           'operator' => $operator));
    }

    /**
     * Builds a filter (commonly for objectClass attributes) from different
     * configuration options.
     *
     * @param array $params  Hash with configuration options that build the
     *                       search filter. Possible hash keys:
     *                       - 'filter': An LDAP filter string.
     *                       - 'objectclass' (string): An objectClass name.
     *                       - 'objectclass' (array): A list of objectClass
     *                                                names.
     *
     * @return Horde_Ldap_Filter  A filter matching the specified criteria.
     * @throws Horde_Ldap_Exception
     */
    public static function build(array $params)
    {
        if (!empty($params['filter'])) {
            return Horde_Ldap_Filter::parse($params['filter']);
        }
        if (!is_array($params['objectclass'])) {
            return Horde_Ldap_Filter::create('objectclass', 'equals', $params['objectclass']);
        }
        $filters = array();
        foreach ($params['objectclass'] as $objectclass) {
            $filters[] = Horde_Ldap_Filter::create('objectclass', 'equals', $objectclass);
        }
        if (count($filters) == 1) {
            return $filters[0];
        }
        return Horde_Ldap_Filter::combine('and', $filters);
    }

    /**
     * Parses a string into a Horde_Ldap_Filter object.
     *
     * @todo Leaf-mode: Do we need to escape at all? what about *-chars? Check
     * for the need of encoding values, tackle problems (see code comments).
     *
     * @param string $filter An LDAP filter string.
     *
     * @return Horde_Ldap_Filter
     * @throws Horde_Ldap_Exception
     */
    public static function parse($filter)
    {
        if (!preg_match('/^\((.+?)\)$/', $filter, $matches)) {
            throw new Horde_Ldap_Exception('Invalid filter syntax, filter components must be enclosed in round brackets');
        }

        if (in_array(substr($matches[1], 0, 1), array('!', '|', '&'))) {
            return self::_parseCombination($matches[1]);
        } else {
            return self::_parseLeaf($matches[1]);
        }
    }

    /**
     * Parses combined subfilter strings.
     *
     * Passes subfilters to parse() and combines the objects using the logical
     * operator detected.  Each subfilter could be an arbitary complex
     * subfilter.
     *
     * @param string $filter An LDAP filter string.
     *
     * @return Horde_Ldap_Filter
     * @throws Horde_Ldap_Exception
     */
    protected static function _parseCombination($filter)
    {
        // Extract logical operator and filter arguments.
        $operator = substr($filter, 0, 1);
        $filter = substr($filter, 1);

        // Split $filter into individual subfilters. We cannot use split() for
        // this, because we do not know the complexiness of the
        // subfilter. Thus, we look trough the filter string and just recognize
        // ending filters at the first level. We record the index number of the
        // char and use that information later to split the string.
        $sub_index_pos = array();
        // Previous character looked at.
        $prev_char = '';
        // Denotes the current bracket level we are, >1 is too deep, 1 is ok, 0
        // is outside any subcomponent.
        $level = 0;
        for ($curpos = 0; $curpos < strlen($filter); $curpos++) {
            $cur_char = $filter{$curpos};

            // Rise/lower bracket level.
            if ($cur_char == '(' && $prev_char != '\\') {
                $level++;
            } elseif ($cur_char == ')' && $prev_char != '\\') {
                $level--;
            }

            if ($cur_char == '(' && $prev_char == ')' && $level == 1) {
                // Mark the position for splitting.
                array_push($sub_index_pos, $curpos);
            }
            $prev_char = $cur_char;
        }

        // Now perform the splits. To get the last part too, we need to add the
        // "END" index to the split array.
        array_push($sub_index_pos, strlen($filter));
        $subfilters = array();
        $oldpos = 0;
        foreach ($sub_index_pos as $s_pos) {
            $str_part = substr($filter, $oldpos, $s_pos - $oldpos);
            array_push($subfilters, $str_part);
            $oldpos = $s_pos;
        }

        // Some error checking...
        if (count($subfilters) == 1) {
            // Only one subfilter found.
        } elseif (count($subfilters) > 1) {
            // Several subfilters found.
            if ($operator == '!') {
                throw new Horde_Ldap_Exception('Invalid filter syntax: NOT operator detected but several arguments given');
            }
        } else {
            // This should not happen unless the user specified a wrong filter.
            throw new Horde_Ldap_Exception('Invalid filter syntax: got operator ' . $operator . ' but no argument');
        }

        // Now parse the subfilters into objects and combine them using the
        // operator.
        $subfilters_o = array();
        foreach ($subfilters as $s_s) {
            $o = self::parse($s_s);
            array_push($subfilters_o, self::parse($s_s));
        }
        if (count($subfilters_o) == 1) {
            $subfilters_o = $subfilters_o[0];
        }

        return self::combine($operator, $subfilters_o);
    }

    /**
     * Parses a single leaf component.
     *
     * @param string $filter An LDAP filter string.
     *
     * @return Horde_Ldap_Filter
     * @throws Horde_Ldap_Exception
     */
    protected static function _parseLeaf($filter)
    {
        // Detect multiple leaf components.
        // [TODO] Maybe this will make problems with filters containing
        // brackets inside the value.
        if (strpos($filter, ')(')) {
            throw new Horde_Ldap_Exception('Invalid filter syntax: multiple leaf components detected');
        }

        $filter_parts = preg_split('/(?<!\\\\)(=|=~|>|<|>=|<=)/', $filter, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (count($filter_parts) != 3) {
            throw new Horde_Ldap_Exception('Invalid filter syntax: unknown matching rule used');
        }

        // [TODO]: Do we need to escape at all? what about *-chars user provide
        //         and that should remain special?  I think, those prevent
        //         escaping! We need to check against PERL Net::LDAP!
        // $value_arr = Horde_Ldap_Util::escapeFilterValue(array($filter_parts[2]));
        // $value     = $value_arr[0];

        return new Horde_Ldap_Filter(array('filter' => '(' . $filter_parts[0] . $filter_parts[1] . $filter_parts[2] . ')'));
    }

    /**
     * Returns the string representation of this filter.
     *
     * This method runs through all filter objects and creates the string
     * representation of the filter.
     *
     * @return string
     */
    public function __toString()
    {
        if (!count($this->_filters)) {
            return $this->_filter;
        }

        $return = '';
        foreach ($this->_filters as $filter) {
            $return .= (string)$filter;
        }

        return '(' . $this->_operator . $return . ')';
    }
}
