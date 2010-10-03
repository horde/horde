<?php
/**
 * The Ingo_Script:: class provides a common abstracted interface to the
 * script-generation subclasses.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Brent J. Nordquist <bjn@horde.org>
 * @package Ingo
 */
class Ingo_Script
{
    /**
     * Only filter unseen messages.
     */
    const FILTER_UNSEEN = 1;

    /**
     * Only filter seen messages.
     */
    const FILTER_SEEN = 2;

    /**
     * The script class' additional parameters.
     *
     * @var array
     */
    protected $_params = array();

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
     * Can tests be case sensitive?
     *
     * @var boolean
     */
    protected $_casesensitive = false;

    /**
     * Does the driver support setting IMAP flags?
     *
     * @var boolean
     */
    protected $_supportIMAPFlags = false;

    /**
     * Does the driver support the stop-script option?
     *
     * @var boolean
     */
    protected $_supportStopScript = false;

    /**
     * Can this driver perform on demand filtering?
     *
     * @var boolean
     */
    protected $_ondemand = false;

    /**
     * Does the driver require a script file to be generated?
     *
     * @var boolean
     */
    protected $_scriptfile = false;

    /**
     * Attempts to return a concrete instance based on $script.
     *
     * @param string $script  The type of subclass to return.
     * @param array $params   Hash containing additional paramters to be
     *                        passed to the subclass' constructor.
     *
     * @return Ingo_Script  The newly created concrete instance.
     * @throws Ingo_Exception
     */
    static public function factory($script, $params = array())
    {
        $script = Horde_String::ucfirst(basename($script));
        $class = __CLASS__ . '_' . $script;

        if (!isset($params['spam_compare'])) {
            $params['spam_compare'] = $GLOBALS['conf']['spam']['compare'];
        }
        if (!isset($params['spam_header'])) {
            $params['spam_header'] = $GLOBALS['conf']['spam']['header'];
        }
        if (!isset($params['spam_char'])) {
            $params['spam_char'] = $GLOBALS['conf']['spam']['char'];
        }
        if ($script == 'Sieve') {
            if (!isset($params['date_format'])) {
                $params['date_format'] = $GLOBALS['prefs']->getValue('date_format');;
            }
            if (!isset($params['time_format'])) {
                // %R and %r don't work on Windows, but who runs a Sieve
                // backend on a Windows server?
                $params['time_format'] = $GLOBALS['prefs']->getValue('twentyFour') ? '%R' : '%r';
            }
        }

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Ingo_Exception(sprintf(_("Unable to load the definition of %s."), $class));
    }

    /**
     * Constructor.
     *
     * @param array $params  A hash containing parameters needed.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;

        /* Determine if ingo should handle the blacklist. */
        $key = array_search(Ingo_Storage::ACTION_BLACKLIST, $this->_categories);
        if ($key !== false && ($GLOBALS['registry']->hasMethod('mail/blacklistFrom') != 'ingo')) {
            unset($this->_categories[$key]);
        }

        /* Determine if ingo should handle the whitelist. */
        $key = array_search(Ingo_Storage::ACTION_WHITELIST, $this->_categories);
        if ($key !== false && ($GLOBALS['registry']->hasMethod('mail/whitelistFrom') != 'ingo')) {
            unset($this->_categories[$key]);
        }
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
     * Returns if this driver allows case sensitive searches.
     *
     * @return boolean  Does this driver allow case sensitive searches?
     */
    public function caseSensitive()
    {
        return $this->_casesensitive;
    }

    /**
     * Returns if this driver allows IMAP flags to be set.
     *
     * @return boolean  Does this driver allow IMAP flags to be set?
     */
    public function imapFlags()
    {
        return $this->_supportIMAPFlags;
    }

    /**
     * Returns if this driver supports the stop-script option.
     *
     * @return boolean  Does this driver support the stop-script option?
     */
    public function stopScript()
    {
        return $this->_supportStopScript;
    }

    /**
     * Returns a script previously generated with generate().
     *
     * @return string  The script.
     */
    public function toCode()
    {
        return '';
    }

    /**
     * Can this driver generate a script file?
     *
     * @return boolean  True if generate() is available, false if not.
     */
    public function generateAvailable()
    {
        return $this->_scriptfile;
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
     * Can this driver perform on demand filtering?
     *
     * @return boolean  True if perform() is available, false if not.
     */
    public function performAvailable()
    {
        return $this->_ondemand;
    }

    /**
     * Perform the filtering specified in the rules.
     *
     * @param array $params  The parameter array.
     *
     * @return boolean  True if filtering performed, false if not.
     */
    public function perform($params = array())
    {
        return false;
    }

    /**
     * Is the apply() function available?
     *
     * @return boolean  True if apply() is available, false if not.
     */
    public function canApply()
    {
        return $this->performAvailable();
    }

    /**
     * Apply the filters now.
     * This is essentially a wrapper around perform() that allows that
     * function to be called from within Ingo ensuring that all necessary
     * parameters are set.
     *
     * @return boolean  See perform().
     */
    public function apply()
    {
        return $this->perform();
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
