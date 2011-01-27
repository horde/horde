<?php
/**
 * This file contains all Horde_Form extensions required for editing tasks.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Nag
 */
/**
 * The Nag_TaskForm class provides the form for adding and editing a task.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Nag_Form_Renderer_Task extends Horde_Form_Renderer
{
    public $delete;

    /**
     *
     * @param array $params
     * @param boolean $delete
     */
    public function __construct($params = array(), $delete = false)
    {
        parent::__construct($params);
        $this->delete = $delete;
    }

    /**
     *@TODO: visibility needs to be public until Horde_Form refactored
     */
    public function _renderSubmit($submit, $reset)
    {
        ?><div class="control" style="padding:1em;">
            <input class="button leftFloat" name="submitbutton" type="submit" value="<?php echo _("Save") ?>" />
        <?php if ($this->delete): ?>
            <input class="button rightFloat" name="submitbutton" type="submit" value="<?php echo _("Delete this task") ?>" />
        <?php endif; ?>
            <div class="clear"></div>
        </div>
        <?php
    }

}