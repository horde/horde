<?php
/**
 * @category Horde
 * @package  Horde_Log
 * @subpackage Filters
 * @author James Pepin <james@jamespepin.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */

/**
 * Filters log events using defined constraints on one or more fields of the
 * $event array
 *
 * @category Horde
 * @package  Horde_Log
 * @subpackage Filters
 * @author James Pepin <james@jamespepin.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 *
 * @todo Implement constraint objects for the different types of filtering ie
 * regex,required,type..etc..  so we can add different constaints ad infinitum.
 */
class Horde_Log_Filter_Constraint implements Horde_Log_Filter
{
    /**
     * @var array
     */
    protected $_constraints = array();

    /**
     * @var Horde_Constraint_Coupler
     * @default Horde_Constraint_And
     */
    protected $_coupler;

    /**
     * Constructor
     *
     * @param Horde_Constraint_Coupler $coupler The default kind of constraint
     * to use to couple multiple constraints. Defaults to And.
     */
    public function __construct(Horde_Constraint_Coupler $coupler = null)
    {
        if (is_null($coupler)) {
            $coupler = new Horde_Constraint_And();
        }
        $this->_coupler = $coupler;
    }

    /**
     * Add a constraint to the filter
     *
     * @param string $field  The field to apply the constraint to
     * @param Horde_Constraint $constraint  The constraint to apply
     *
     * @return Horde_Log_Filter_Constraint A reference to $this to allow method chaining
     */
    public function addConstraint($field, Horde_Constraint $constraint)
    {
        if (!isset($this->_constraints[$field])) {
            $this->_constraints[$field] = clone($this->_coupler);
        }
        $this->_constraints[$field]->addConstraint($constraint);
        return $this;
    }

    /**
     * Add a regular expression to filter by
     *
     * Takes a field name and a regex, if the regex does not match then the
     * event is filtered.
     *
     * @param string $field The name of the field that should be part of the event
     * @param string $regex The regular expression to filter by
     * @return Horde_Log_Filter_Constraint A reference to $this to allow method chaining
     */
    public function addRegex($field, $regex)
    {
        $constraint = new Horde_Constraint_PregMatch($regex);
        return $this->addConstraint($field, $constraint);
    }

    /**
     * Add a required field to the filter
     *
     * If the field does not exist on the event, then it is filtered.
     *
     * @param string $field The name of the field that should be part of the event
     * @return Horde_Log_Filter_Constraint A reference to $this to allow method chaining
     */
    public function addRequiredField($field)
    {
        $notNull = new Horde_Constraint_Not(new Horde_Constraint_Null());
        return $this->addConstraint($field, $notNull);
    }

    /**
     * Adds all arguments passed as required fields
     *
     * @return Horde_Log_Filter_Constraint A reference to $this to allow method chaining
     */
    public function addRequiredFields()
    {
        $fields = func_get_args();
        foreach ($fields as $f) {
            $this->addRequiredField($f);
        }
        return $this;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param  array    $event    Log event
     * @return boolean            accepted?
     */
    public function accept($event)
    {
        foreach ($this->_constraints as $field => $constraint) {
            $value = isset($event[$field]) ? $event[$field] : null;
            if (!$constraint->evaluate($value)) {
                return false;
            }
        }
        return true;
    }
}
