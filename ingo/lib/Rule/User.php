<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * User-defined rule.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 *
 * @property-read boolean $has_flags  True if the rule has any flags set.
 */
class Ingo_Rule_User
extends Ingo_Rule
{
    const COMBINE_ALL = 1;
    const COMBINE_ANY = 2;

    const FLAG_ANSWERED = 1;
    const FLAG_DELETED = 2;
    const FLAG_FLAGGED = 4;
    const FLAG_SEEN = 8;
    const FLAG_AVAILABLE = 16;

    const TEST_HEADER = 1;
    const TEST_SIZE = 2;
    const TEST_BODY = 3;

    const TYPE_TEXT = 1;
    const TYPE_MAILBOX = 2;
    const TYPE_EMPTY = 3;

    public $flags = 0;
    public $label = '';
    public $type = 0;

    public $combine = self::COMBINE_ALL;
    public $conditions = array();
    public $stop = true;
    public $value = '';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = _("New Rule");
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'has_flags':
            return (bool) ($this->flags & ~self::FLAG_AVAILABLE);
        }
    }

    /**
     * Returns information on a given test string.
     *
     * @todo Move to some sort of Test object
     *
     * @param string $action  The test string.
     *
     * @return object  Object with the following values:
     *   - label: (string) The label for this action.
     *   - type: (string) Either 'int', 'none', or 'text'.
     */
    public function getTestInfo($test)
    {
        /* Mapping of gettext strings -> labels. */
        $labels = array(
            'contains' => _("Contains"),
            'not contain' =>  _("Doesn't contain"),
            'is' => _("Is"),
            'not is' => _("Isn't"),
            'begins with' => _("Begins with"),
            'not begins with' => _("Doesn't begin with"),
            'ends with' => _("Ends with"),
            'not ends with' => _("Doesn't end with"),
            'exists' =>  _("Exists"),
            'not exist' => _("Doesn't exist"),
            'regex' => _("Regular expression"),
            'not regex' => _("Doesn't match regular expression"),
            'matches' => _("Matches (with placeholders)"),
            'not matches' => _("Doesn't match (with placeholders)"),
            'less than' => _("Less than"),
            'less than or equal to' => _("Less than or equal to"),
            'greater than' => _("Greater than"),
            'greater than or equal to' => _("Greater than or equal to"),
            'equal' => _("Equal to"),
            'not equal' => _("Not equal to")
        );

        /* The type of tests available. */
        $types = array(
            'int'  => array(
                'less than', 'less than or equal to', 'greater than',
                'greater than or equal to', 'equal', 'not equal'
            ),
            'none' => array(
                'exists', 'not exist'
            ),
            'text' => array(
                'contains', 'not contain', 'is', 'not is', 'begins with',
                'not begins with', 'ends with', 'not ends with', 'regex',
                'not regex', 'matches', 'not matches'
            )
        );

        /* Create the information object. */
        $ob = new stdClass;
        $ob->label = $labels[$test];
        foreach ($types as $key => $val) {
            if (in_array($test, $val)) {
                $ob->type = $key;
                break;
            }
        }

        return $ob;
    }

    /**
     * Output description for a rule.
     *
     * @return string  Text description.
     */
    public function description()
    {
        $condition_size = count($this->conditions) - 1;
        $descrip = '';

        foreach ($this->conditions as $key => $val) {
            $info = $this->getTestInfo($val['match']);
            $descrip .= sprintf("%s %s \"%s\"", _($val['field']), $info->label, $val['value']);

            if (!empty($val['case'])) {
                $descrip .= ' [' . _("Case Sensitive") . ']';
            }

            if ($key < $condition_size) {
                $descrip .= ($this->combine == self::COMBINE_ALL)
                    ? _(" and")
                    : _(" or");
                $descrip .= "\n  ";
            }
        }

        $descrip .= "\n" . $this->label;

        if ($this->value) {
            $descrip .= ': ' . $this->value;
        }

        if ($this->stop) {
            $descrip .= "\n[stop]";
        }

        return trim($descrip);
    }

}
