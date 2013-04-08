<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
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
        /* Does the driver support the stop-script option? */
        'stop_script' => false,
        /* Can this driver perform on demand filtering? */
        'on_demand' => false,
        /* Does the driver require a script file to be generated? */
        'script_file' => true,
    );

    /**
     * The categories of filtering allowed.
     *
     * @var array
     */
    protected $_categories = array(
        Ingo_Storage::ACTION_VACATION,
    );

    /**
     * Which form fields are supported in each category by this driver?
     *
     * This is an associative array with the keys taken from $_actions, each
     * value is a list of strings with the supported feature names.  An absent
     * key is interpreted as "all features supported".
     *
     * @var array
     */
    protected $_categoryFeatures = array(
        Ingo_Storage::ACTION_VACATION => array(
            'subject', 'reason'
        )
    );

    /**
     * Generates the scripts to do the filtering specified in the rules.
     */
    protected function _generate()
    {
        $filters = $this->_params['storage']
             ->retrieve(Ingo_Storage::ACTION_FILTERS);
        foreach ($filters->getFilterList($this->_params['skip']) as $filter) {
            switch ($filter['action']) {
            case Ingo_Storage::ACTION_VACATION:
                $this->_addItem(
                    Ingo::RULE_VACATION,
                    new Ingo_Script_String(
                        $this->_placeHolders($this->_params['vacation_unset'],
                                             Ingo::RULE_VACATION)
                    )
                );
                if (!empty($filter['disable'])) {
                    break;
                }
                $this->_addItem(
                    Ingo::RULE_VACATION,
                    new Ingo_Script_String(
                        $this->_placeHolders($this->_params['vacation_set'],
                                             Ingo::RULE_VACATION)
                    )
                );
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
            $vacation = $this->_params['storage']
              ->retrieve(Ingo_Storage::ACTION_VACATION);
            $search[] = '%m';
            $search[] = '%s';
            $replace[] = $transport->quote($vacation->getVacationReason());
            $replace[] = $transport->quote($vacation->getVacationSubject());
            break;
        }

        return str_replace($search, $replace, $query);
    }
}
