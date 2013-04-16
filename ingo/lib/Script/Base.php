<?php
/**
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

/**
 * The Ingo_Script_Base class provides a common abstracted interface to the
 * script-generation subclasses.
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
     * Which form fields are supported in each category by this driver?
     *
     * This is an associative array with the keys taken from $_actions, each
     * value is a list of strings with the supported feature names.  An absent
     * key is interpreted as "all features supported".
     *
     * @var array
     */
    protected $_categoryFeatures = array();

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
     * The recipes that make up the code.
     *
     * @var array
     */
    protected $_recipes = array();

    /**
     * Have the recipes been generated yet?
     *
     * @var boolean
     */
    protected $_generated = false;

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
     * Returns the supported form fields for this driver.
     *
     * @return array  An array with the supported field names of the requested category.
     */
    public function availableCategoryFeatures($category)
    {
        return isset($this->_categoryFeatures[$category]) ? $this->_categoryFeatures[$category] : array();
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
     * Generates the scripts to do the filtering specified in the rules.
     *
     * @return array  The scripts.
     */
    public function generate()
    {
        if (!$this->_generated) {
            $this->_generate();
            $this->_generated = true;
        }

        $scripts = array();
        foreach ($this->_recipes as $item) {
            $rule = isset($this->_params['transport'][$item['rule']])
                ? $item['rule']
                : Ingo::RULE_ALL;
            $name = '';
            if (strlen($item['name'])) {
                $name = $item['name'];
            } elseif (isset($this->_params['transport'][$rule]['params']['filename'])) {
                $name = $this->_params['transport'][$rule]['params']['filename'];
            } elseif (isset($this->_params['transport'][$rule]['params']['scriptname'])) {
                $name = $this->_params['transport'][$rule]['params']['scriptname'];
            }

            if (!isset($scripts[$rule . $name])) {
                $scripts[$rule . $name] = array(
                    'transport' => $this->_params['transport'][$rule],
                    'name' => $name,
                    'script' => '',
                    'recipes' => array(),
                );
            }
            $scripts[$rule . $name]['script'] .= $item['object']->generate() . "\n";
            $scripts[$rule . $name]['recipes'][] = $item;
        }
        return array_values($scripts);
    }

    /**
     * Generates the scripts to do the filtering specified in the rules.
     */
    protected function _generate()
    {
    }

    /**
     * Adds an item to the recipe list.
     *
     * @param integer $rule           One of the Ingo::RULE_* constants.
     * @param Ingo_Script_Item $item  An item to add to the recipe list.
     * @param string $name            A script name.
     */
    protected function _addItem($rule, Ingo_Script_Item $item, $name = null)
    {
        $this->_recipes[] = array(
            'rule' => $rule,
            'object' => $item,
            'name' => $name
        );
    }

    /**
     * Inserts an item into the recipe list.
     *
     * @param integer $rule           One of the Ingo::RULE_* constants.
     * @param Ingo_Script_Item $item  An item to add to the recipe list.
     * @param string $name            A script name.
     * @þaram integer $position       Where to add the item.
     */
    protected function _insertItem($rule, Ingo_Script_Item $item, $name = null,
                                   $position = 0)
    {
        array_splice(
            $this->_recipes,
            $position,
            0,
            array(array(
                'rule' => $rule,
                'object' => $item,
                'name' => $name
            ))
        );
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
     * Is the perform() function available right now?
     *
     * This is not a duplication of hasFeature() because drivers might override
     * this to do real-time checks if on-demand filtering is not only available
     * theoretically but practically in this very moment.
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
