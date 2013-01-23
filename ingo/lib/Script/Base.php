<?php
/**
 * The Ingo_Script_Base class provides a common abstracted interface to the
 * script-generation subclasses.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Brent J. Nordquist <bjn@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
abstract class Ingo_Script_Base
{
    /**
     * The script class' additional parameters.
     *
     * @var array
     */
    protected $_params = array();

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
        'script_file' => false,
    );

    /**
     * The list of actions allowed (implemented) for this driver.
     * This SHOULD be defined in each subclass.
     *
     * @var array
     */
    protected $_actions = array();

    /**
     * The categories of filtering allowed.
     * This SHOULD be defined in each subclass.
     *
     * @var array
     */
    protected $_categories = array();

    /**
     * The list of tests allowed (implemented) for this driver.
     * This SHOULD be defined in each subclass.
     *
     * @var array
     */
    protected $_tests = array();

    /**
     * The types of tests allowed (implemented) for this driver.
     * This SHOULD be defined in each subclass.
     *
     * @var array
     */
    protected $_types = array();

    /**
     * A list of any special types that this driver supports.
     *
     * @var array
     */
    protected $_special_types = array();

    /**
     * Constructor.
     *
     * @param array $params  A hash containing parameters needed.
     */
    public function __construct(array $params = array())
    {
        global $registry;

        $this->setParams($params);

        /* Determine if ingo should handle the blacklist. */
        if ((($key = array_search(Ingo_Storage::ACTION_BLACKLIST, $this->_categories)) !== false) &&
            ($registry->hasMethod('mail/blacklistFrom') != 'ingo')) {
            unset($this->_categories[$key]);
        }

        /* Determine if ingo should handle the whitelist. */
        if ((($key = array_search(Ingo_Storage::ACTION_WHITELIST, $this->_categories)) !== false) &&
            ($registry->hasMethod('mail/whitelistFrom') != 'ingo')) {
            unset($this->_categories[$key]);
        }
    }

    /**
     * Updates the parameters.
     *
     * @param array $params  A hash containing parameters.
     *
     * @return Ingo_Script  This object, for chaining.
     */
    public function setParams(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
        return $this;
    }

    /**
     * Returns whether the script driver supports a certain feature.
     *
     * @see $_features
     *
     * @param string $feature  A feature name.
     *
     * @return boolean  True if this feature is supported.
     */
    public function hasFeature($feature)
    {
        return !empty($this->_features[$feature]);
    }

    /**
     * Returns a regular expression that should catch mails coming from most
     * daemons, mailing list, newsletters, and other bulk.
     *
     * This is the expression used for procmail's FROM_DAEMON, including all
     * mailinglist headers.
     *
     * @return string  A regular expression.
     */
    public function excludeRegexp()
    {
        return '(^(Mailing-List:|List-(Id|Help|Unsubscribe|Subscribe|Owner|Post|Archive):|Precedence:.*(junk|bulk|list)|To: Multiple recipients of|(((Resent-)?(From|Sender)|X-Envelope-From):|>?From)([^>]*[^(.%@a-z0-9])?(Post(ma?(st(e?r)?|n)|office)|(send)?Mail(er)?|daemon|m(mdf|ajordomo)|n?uucp|LIST(SERV|proc)|NETSERV|o(wner|ps)|r(e(quest|sponse)|oot)|b(ounce|bs\.smtp)|echo|mirror|s(erv(ices?|er)|mtp(error)?|ystem)|A(dmin(istrator)?|MMGR|utoanswer))(([^).!:a-z0-9][-_a-z0-9]*)?[%@>\t ][^<)]*(\(.*\).*)?)?$([^>]|$)))';
    }

    /**
     * Returns the available actions for this driver.
     *
     * @return array  The list of available actions.
     */
    public function availableActions()
    {
        return $this->_actions;
    }

    /**
     * Returns the available categories for this driver.
     *
     * @return array  The list of categories.
     */
    public function availableCategories()
    {
        return $this->_categories;
    }

    /**
     * Returns the available tests for this driver.
     *
     * @return array  The list of tests actions.
     */
    public function availableTests()
    {
        return $this->_tests;
    }

    /**
     * Returns the available test types for this driver.
     *
     * @return array  The list of test types.
     */
    public function availableTypes()
    {
        return $this->_types;
    }

    /**
     * Returns any test types that are special for this driver.
     *
     * @return array  The list of special types
     */
    public function specialTypes()
    {
        return $this->_special_types;
    }

    /**
     * Generates the script to do the filtering specified in
     * the rules.
     *
     * @return string  The script.
     */
    public function generate()
    {
        return '';
    }

    /**
     * Returns any additional scripts that need to be sent to the transport
     * layer.
     *
     * @return array  A list of scripts with script names as keys and script
     *                code as values.
     */
    public function additionalScripts()
    {
        return array();
    }

    /**
     * Performs the filtering specified in the rules.
     *
     * @param integer $change  The timestamp of the latest rule change during
     *                         the current session.
     */
    public function perform($change)
    {
    }

    /**
     * Is the perform() function available?
     *
     * @return boolean  True if perform() is available, false if not.
     */
    public function canPerform()
    {
        return $this->hasFeature('on_demand');
    }

    /**
     * Is this a valid rule?
     *
     * @param integer $type  The rule type.
     *
     * @return boolean  Whether the rule is valid or not for this driver.
     */
    protected function _validRule($type)
    {
        return (!empty($type) && in_array($type, array_merge($this->_categories, $this->_actions)));
    }

}
