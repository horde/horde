<?php
/**
* Copyright 2012-2015 Horde LLC (http://www.horde.org/)
*
* See the enclosed file LICENSE for license information (ASL).  If you
* did not receive this file, see http://www.horde.org/licenses/apache.
*
* @author  Michael Epstein <mepstein@mediabox.cl>
* @author  Jan Schneider <jan@horde.org>
* @package Ingo
*/

/**
* The Ingo_Script_Ispconfig3 class represents an ISPConfig 3 "script
* generator".
*
* @author  Michael Epstein <mepstein@mediabox.cl>
* @author  Jan Schneider <jan@horde.org>
* @package Ingo
*/
class Ingo_Script_Ispconfig3 extends Ingo_Script_Base
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
        'on_demand' => true,
        /* Does the driver support aditional settings? */
        'additional_settings' => false,
        /* Does the driver require a script file to be generated? */
        'script_file' => false
    );

    /**
    * The list of actions allowed (implemented) for this driver.
    *
    * @var array
    */
    protected $_actions = array(
        'Ingo_Rule_User_Discard',
        'Ingo_Rule_User_Move'
    );

    /**
    * The categories of filtering allowed.
    *
    * @var array
    */
    protected $_categories = array(
        'Ingo_Rule_System_Blacklist',
        'Ingo_Rule_System_Forward',
        'Ingo_Rule_System_Vacation',
        'Ingo_Rule_System_Whitelist',
        'Ingo_Rule_User_Filters'
    );

    /**
    * The types of tests allowed (implemented) for this driver.
    *
    * @var array
    */
    protected $_types = array(
        Ingo_Rule_User::TEST_HEADER
    );

    /**
    * The list of tests allowed (implemented) for this driver.
    *
    * @var array
    */
    protected $_tests = array(
        'contains',
        'is',
        'begins with',
        'ends with'
    );

    protected $_categoryFeatures = array(
        'Ingo_Rule_System_Vacation' => array('period', 'reason', 'subject'),
        'Ingo_Rule_System_Forward' => array(null),
        'Ingo_Rule_System_Blacklist' => array(null),
        'Ingo_Rule_System_Whitelist' => array(null),
        'Ingo_Rule_User_Filters' => array(null)
    );

    /**
    * Constructor.
    */
    public function __construct(array $params = array())
    {
        $default_params = array(
            'soap_uri' => 'http://localhost:8080/remote/',
            'soap_user' => 'horde',
            'soap_pass' => '',
            'policy_id' => 5,
            'delete_orphan' => false
        );

        parent::__construct(array_merge($default_params, $params));
    }

    /**
    * @see perform()
    */
    protected function _perform($change)
    {
        // Load rules.
        $rules = Ingo_Storage_FilterIterator_Skip::create(
            $this->_params['storage'],
            $this->_params['skip']
        );

        // Process loaded rules.
        $api = new Ingo_Script_Ispconfig3_Api($this->_params);
        $api->processRules($rules);
    }
}
