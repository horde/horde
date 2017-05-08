<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_Customsql class generates SQL scripts out of custom SQL
 * queries.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Customsql extends Ingo_Script_Base
{
    /**
     * A list of driver features.
     *
     * @var array
     */
    protected $_features = array(
        /* Can tests be case sensitive? */
        'case_sensitive' => false,
        /* Does the driver support setting IMAP flags? */
        'imap_flags' => false,
        /* Can this driver perform on demand filtering? */
        'on_demand' => false,
        /* Does the driver require a script file to be generated? */
        'script_file' => true,
        /* Does the driver support the stop-script option? */
        'stop_script' => false,
        /* Does the driver support vacation start and end on time level? */
        'vacation_time' => false,
    );

    /**
     * The categories of filtering allowed.
     *
     * @var array
     */
    protected $_categories = array(
        'Ingo_Rule_System_Vacation'
    );

    /**
     * Which form fields are supported in each category by this driver?
     *
     * This is an associative array with the keys taken from $_categories, each
     * value is a list of strings with the supported feature names.  An absent
     * key is interpreted as "all features supported".
     *
     * @var array
     */
    protected $_categoryFeatures = array(
        'Ingo_Rule_System_Vacation' => array(
            'subject', 'reason'
        )
    );

    /**
     * Generates the scripts to do the filtering specified in the rules.
     */
    protected function _generate()
    {
        $filters = Ingo_Storage_FilterIterator_Skip::create(
            $this->_params['storage'],
            $this->_params['skip']
        );

        foreach ($filters as $rule) {
            switch (get_class($rule)) {
            case 'Ingo_Rule_System_Vacation':
                $this->_addItem(
                    Ingo::RULE_VACATION,
                    new Ingo_Script_String(
                        $this->_placeHolders($this->_params['vacation_unset'],
                                             Ingo::RULE_VACATION)
                    )
                );
                if (!$rule->disable) {
                    $this->_addItem(
                        Ingo::RULE_VACATION,
                        new Ingo_Script_String(
                            $this->_placeHolders($this->_params['vacation_set'],
                                                 Ingo::RULE_VACATION)
                        )
                    );
                }
                break;
            }
        }
    }

    /**
     * Replaces place holders in a query.
     *
     * @param string $query  A SQL query with place holders.
     * @param integer $rule  A Ingo::RULE_* constant.
     *
     * @return string  A valid query.
     */
    protected function _placeHolders($query, $rule)
    {
        $transport = $GLOBALS['injector']
            ->getInstance('Ingo_Factory_Transport')
            ->create(
                isset($this->_params['transport'][$rule])
                    ? $this->_params['transport'][$rule]
                    : $this->_params['transport'][Ingo::RULE_ALL]
            );

        $search = array('%u', '%d');
        $replace = array(
            $transport->quote(Ingo::getUser()),
            $transport->quote(Ingo::getDomain())
        );

        switch ($rule) {
        case Ingo::RULE_VACATION:
            $vacation = $this->_params['storage']->getSystemRule('Ingo_Rule_System_Vacation');
            $search[] = '%m';
            $search[] = '%s';
            $replace[] = $transport->quote($vacation->reason);
            $replace[] = $transport->quote($vacation->subject);
            break;
        }

        return str_replace($search, $replace, $query);
    }
}
