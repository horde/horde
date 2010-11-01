<?php
/**
 * @author     James Pepin <james@jamespepin.com>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Filters
 */

/**
 * Filters log events using defined constraints on one or more fields of the
 * $event array.
 *
 * @author     James Pepin <james@jamespepin.com>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Filters
 *
 * @todo Implement constraint objects for the different types of filtering ie
 * regex,required,type..etc..  so we can add different constaints ad infinitum.
 */
class Horde_Log_Filter_Constraint implements Horde_Log_Filter
{
    /**
     * Constraint list.
     *
     * @var array
     */
    protected $_constraints = array();

    /**
     * Default constraint coupler.
     *
     * @var Horde_Constraint_Coupler
     * @default Horde_Constraint_And
     */
    protected $_coupler;

    /**
     * Constructor
     *
     * @param Horde_Constraint_Coupler $coupler  The default kind of
     *                                           constraint to use to couple
     *                                           multiple constraints.
     *                                           Defaults to And.
     */
    public function __construct(Horde_Constraint_Coupler $coupler = null)
    {
        $this->_coupler = is_null($coupler)
            ? new Horde_Constraint_And()
            : $coupler;
    }

    /**
     * Add a constraint to the filter
     *
     * @param string $field                 The field to apply the constraint
     *                                      to.
     * @param Horde_Constraint $constraint  The constraint to apply.
     *
     * @return Horde_Log_Filter_Constraint  A reference to $this to allow
     *                                      method chaining.
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
     * @param string $field  The name of the field that should be part of the
     *                       event.
     * @param string $regex  The regular expression to filter by.
     * @return Horde_Log_Filter_Constraint  A reference to $this to allow
     *                                      method chaining.
     */
    public function addRegex($field, $regex)
    {
        return $this->addConstraint($field, new Horde_Constraint_PregMatch($regex));
    }

    /**
     * Add a required field to the filter
     *
     * If the field does not exist on the event, then it is filtered.
     *
     * @param string $field  The name of the field that should be part of the
     *                       event.
     *
     * @return Horde_Log_Filter_Constraint  A reference to $this to allow
     *                                      method chaining.
     */
    public function addRequiredField($field)
    {
        return $this->addConstraint($field, new Horde_Constraint_Not(new Horde_Constraint_Null()));
    }

    /**
     * Adds all arguments passed as required fields
     *
     * @return Horde_Log_Filter_Constraint  A reference to $this to allow
     *                                      method chaining.
     */
    public function addRequiredFields()
    {
        foreach (func_get_args() as $f) {
            $this->addRequiredField($f);
        }

        return $this;
    }

    /**
     * Returns Horde_Log_Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param array $event  Log event.
     *
     * @return boolean  accepted?
     */
    public function accept($event)
    {
        foreach ($this->_constraints as $field => $constraint) {
            $value = isset($event[$field]) ? $event[$field] : null;
            if (!$constraint->evaluate($value)) {
                return Horde_Log_Filter::IGNORE;
            }
        }

        return Horde_Log_Filter::ACCEPT;
    }

}
