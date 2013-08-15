<?php
/**
 * Passwd_Policy - A class for policy validation
 * 
 *
 */

class Passwd_Policy
{

    /**
     * @var $_policy;
     */
    protected $_policy = array();

    /**
     * @param array $policyOptions  The password policies for this backend. Options are:
     *                              - maxLength: (integer) Maximum length of the password.
     *                              - maxSpace: (integer) Maximum number of white space characters.
     *                              - minAlpha: (integer) Minimum number of alphabetic characters.
     *                              - minAlphaNum: (integer) Minimum number of alphanumeric characters.
     *                              - minLength: (integer) Minimum length of the password.
     *                              - minLower: (integer) Minimum number of lowercase characters.
     *                              - minNonAlpha: (integer) Minimum number of non-alphabetic characters
     *                              - minNumeric: (integer) Minimum number of numeric characters (0-9).
     *                              - minSymbol: (integer) Minimum number of punctuation / symbol characters.
     *                              - minUpper: (integer) Minimum number of uppercase characters.
     *
     *                              Alternatively/additionally, the minimum number of character classes can
     *                              be configured by setting 'minClasses'. The valid range is 0 through 4
     */
    public function __construct(array $policyOptions)
    {
        $this->_policy = $policyOptions;
    }

    /**
     * @return array  Return a hash of description strings for each defined policy key
     */
    public function description()
    {
        $policyCaptions = array();
        foreach ($this->_policy as $key => $value) {
            if ($value == 0) {
                continue;
            }
            switch ($key) {
            case 'maxLength':
                $policyCaptions[$key] = _('Maximum length: ') . $value;
            break;
            case 'maxSpace':
                $policyCaptions[$key] = _('Maximum white space characters: ') . $value;
            break;
            case 'minAlpha':
                $policyCaptions[$key] = _('Minimum alphabetic characters: ') . $value;
            break;
            case 'minAlphaNum':
                $policyCaptions[$key] = _('Minimum alphanumeric characters: ') . $value;
            break;
            case 'minLength':
                $policyCaptions[$key] = _('Minimum length: ') . $value;
            break;
            case 'minLower':
                $policyCaptions[$key] = _('Minimum lowercase characters: ') . $value;
            break;
            case 'minNonAlpha':
                $policyCaptions[$key] = _('Minimum non-alphabetic characters: ') . $value;
            break;
            case 'minNumeric':
                $policyCaptions[$key] = _('Minimum numeric characters (0-9): ') . $value;
            break;
            case 'minSymbol':
                $policyCaptions[$key] = _('Minimum punctuation characters: ') . $value;
            break;
            case 'minUpper':
                $policyCaptions[$key] = _('Minimum uppercase characters: ') . $value;
            break;
            case 'minClasses':
                $policyCaptions[$key] = _('Minimum characters: classes') . $value;
            break;
            }

        }
        return $policyCaptions;
    }

    /**
     *  @return array  true or false for each array key
     */
    public function validate($password)
    {
        $result = array();
        foreach ($this->_policy as $key => $value) {
            if ($value == 0) { 
                continue;
            }
            /* check each policy option by itself becaus Horde_Auth stops at the first problem */
            try {
                Horde_Auth::checkPasswordPolicy($password, array($key => $value));
                $result[$key] = true;
            } catch(Horde_Auth_Exception $e) {
                $result[$key] = false;
            }
        }
        return $result;
    }
}


