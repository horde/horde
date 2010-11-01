<?php
/**
 * The Ingo_Script_Maildrop_Comment:: class represents a maildrop comment.
 * This is a pretty simple class, but it makes the code in
 * Ingo_Script_Maildrop:: cleaner as it provides a generate() function and can
 * be added to the recipe list the same way as a recipe can be.
 *
 * Copyright 2005-2007 Matt Weyland <mathias@weyland.ch>
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Matt Weyland <mathias@weyland.ch>
 * @package Ingo
 */
class Ingo_Script_Maildrop_Comment
{
    /**
     * The comment text.
     *
     * @var string
     */
    protected $_comment = '';

    /**
     * Constructs a new maildrop comment.
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
            ? '##### ' . $comment . ' #####'
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
