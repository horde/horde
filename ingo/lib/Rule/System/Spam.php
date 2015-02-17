<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * The spam rule.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 *
 * @property float $level  Spam level.
 * @property string $mailbox  Spam mailbox.
 */
class Ingo_Rule_System_Spam
extends Ingo_Rule
implements Ingo_Rule_System
{
    /**
     * Spam level.
     *
     * @var float
     */
    protected $_level = 5;

    /**
     * Spam mailbox.
     *
     * @var string
     */
    protected $_mailbox = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = _("Spam Filter");
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'level':
            return $this->_level;

        case 'mailbox':
            return $this->_mailbox;
        }
    }

    /**
     */
    public function __set($name, $data)
    {
        switch ($name) {
        case 'level':
            $this->_level = floatval($data);
            break;

        case 'mailbox':
            $this->_mailbox = $data;
            break;
        }
    }

}
