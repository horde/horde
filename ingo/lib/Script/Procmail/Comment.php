<?php
/**
 * The Ingo_Script_Procmail_Comment:: class represents a Procmail comment.
 * This is a simple class, but it makes the code in Ingo_Script_Procmail::
 * cleaner as it provides a generate() function and can be added to the
 * recipe list the same way as a recipe can be.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Ben Chavet <ben@horde.org>
 * @package Ingo
 */
class Ingo_Script_Procmail_Comment
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
