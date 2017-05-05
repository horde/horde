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
        Ingo_Storage::ACTION_MOVE,
        Ingo_Storage::ACTION_DISCARD
    );

    /**
    * The categories of filtering allowed.
    *
    * @var array
    */
    protected $_categories = array(
        Ingo_Storage::ACTION_BLACKLIST,
        Ingo_Storage::ACTION_WHITELIST,
        Ingo_Storage::ACTION_VACATION,
        Ingo_Storage::ACTION_FORWARD
    );

    /**
    * The types of tests allowed (implemented) for this driver.
    *
    * @var array
    */
    protected $_types = array(
        Ingo_Storage::TYPE_HEADER
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
        Ingo_Storage::ACTION_VACATION => array('period', 'reason', 'subject'),
        Ingo_Storage::ACTION_FORWARD => array(null),
        Ingo_Storage::ACTION_BLACKLIST => array(null),
        Ingo_Storage::ACTION_WHITELIST => array(null),
        Ingo_Storage::ACTION_FILTERS => array(null)
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
        $filters = $this->_params['storage']->retrieve(Ingo_Storage::ACTION_FILTERS);
        $rules = $filters->getFilterList($this->_params['skip']);

        // Process loaded rules.
        $api = new Ingo_Script_Ispconfig3_Api($this->_params);
        $api->processRules($rules);
    }
}
