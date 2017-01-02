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
 * Base rule.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Rule
{
    /**
     * Is this rule disabled?
     *
     * @var boolean
     */
    public $disable = false;

    /**
     * Rule name.
     *
     * @var string
     */
    public $name = '';

    /**
     * Unique ID of the rule.
     *
     * @var string
     */
    public $uid = '';

    /**
     */
    public function __toString()
    {
        return strval($this->uid);
    }

    /**
     * Generate the rule description.
     *
     * @return string  Rule description.
     */
    public function description()
    {
        return $this->name;
    }

}
