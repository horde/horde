<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Michael Bunk <mb@computer-leipzig.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */

/**
 * The Ingo_Script_Ispconfig class represents an ISPConfig vacation "script
 * generator".
 *
 * @author  Michael Bunk <mb@computer-leipzig.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Script_Ispconfig extends Ingo_Script_Base
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

    protected $_categoryFeatures = array(
        Ingo_Storage::ACTION_VACATION => array('period', 'reason'),
    );

    /**
     * Generates the script to do the filtering specified in the rules.
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
                    new Ingo_Script_Ispconfig_Vacation(
                        array('vacation' => $this->_params['storage']->retrieve(Ingo_Storage::ACTION_VACATION),
                              'disable' => !empty($filter['disable']))
                    )
                );
                break;
            }
        }
    }
}
