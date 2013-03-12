<?php
/**
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_Procmail_Comment class represents a Procmail comment.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Procmail_Comment implements Ingo_Script_Item
{
    /**
     * The comment text.
     *
     * @var string
     */
    protected $_comment = '';

    /**
     * Constructs a new procmail comment.
     *
     * @param string $comment   Comment to be generated.
     * @param boolean $disable  Output 'DISABLED' comment?
     * @param boolean $header   Output a 'header' comment?
     */
    public function __construct($comment, $disable = false, $header = false)
    {
        if ($disable) {
            $comment = _("DISABLED: ") . $comment;
        }

        $this->_comment = $header
            ? '##### ' . $comment  . ' #####'
            : '# ' . $comment;
    }

    /**
     * Returns the comment stored by this object.
     *
     * @return string  The comment stored by this object.
     */
    public function generate()
    {
        return $this->_comment;
    }
}
