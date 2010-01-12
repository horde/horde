<?php
/**
 * This file contains all Horde_Form extensions required for editing accounts.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/** Horde_Form_Action */
require_once 'Horde/Form/Action.php';

/**
 * The Fima_AccountForm class provides the form for adding and editing an account.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_AccountForm extends Horde_Form {

    function Fima_AccountForm(&$vars, $title = '', $delete = false)
    {
        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'actionID', 'text', true);
        $this->addHidden('', 'account_id', 'text', false);
        $this->addHidden('', 'number', 'text', false);
        $this->addHidden('', 'parent_id', 'text', false);

        $this->addVariable(_("Number"), 'number_new', 'text', true, false, false, array('/\d{1,4}/', 4, 4));
        $this->addVariable(_("Type"), 'type', 'enum', true, false, false, array(Fima::getAccountTypes()));
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("e.o."), 'eo', 'boolean', false);
        $this->addVariable(_("Description"), 'desc', 'longtext', false);
        $this->addVariable(_("Closed"), 'closed', 'boolean', false);

        $buttons = array(_("Save"), _("Save and New"));
        if ($delete) {
            $buttons[] = _("Delete this account");
        }
        $this->setButtons($buttons);
    }

    function renderActive()
    {
        return parent::renderActive(new Fima_AccountForm_Renderer(array('varrenderer_driver' => array('fima', 'fima')),  $this->_submit, 2), $this->_vars, 'account.php', 'post');
    }

}

/**
 * The Fima_AccountDeleteForm class provides the form for deleting an account.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_AccountDeleteForm extends Horde_Form {

    function Fima_AccountDeleteForm(&$vars, $title = '', $edit = false)
    {
        parent::Horde_Form($vars, $title);

        $this->addHidden('', 'actionID', 'text', true);
        $this->addHidden('', 'account_id', 'text', false);
        $this->addHidden('', 'number', 'text', false);
        $this->addHidden('', 'name', 'text', false);
        $this->addHidden('', 'type', 'text', false);

        $this->addVariable(_("Postings"), 'dspostings', 'fima_dspostings', false);
        $this->addVariable(_("Subaccounts"), 'dssubaccounts', 'fima_dssubaccounts', false);

        $buttons = array(_("Delete"));
        if ($edit) {
            $buttons[] = _("Edit this account");
        }
        $this->setButtons($buttons);
    }

    function renderActive()
    {
        return parent::renderActive(new Fima_AccountForm_Renderer(array('varrenderer_driver' => array('fima', 'fima')),  $this->_submit, 1), $this->_vars, 'account.php', 'post');
    }

}
class Fima_AccountForm_Renderer extends Horde_Form_Renderer {

    var $buttons;
    var $buttonspacer;

    function Fima_AccountForm_Renderer($params = array(), $buttons = array(), $buttonspacer = 1)
    {
        parent::Horde_Form_Renderer($params);
        $this->buttons = $buttons;
        $this->buttonspacer = $buttonspacer;
    }

    function _renderSubmit($submit, $reset)
    {
?><div class="control" style="padding:1em;">
<?php foreach($this->buttons as $key => $button): ?>
    <input class="button <?php echo ($key < $this->buttonspacer) ? 'leftFloat' : 'rightFloat' ?>" name="submitbutton" type="submit" value="<?php echo $button ?>" />
<?php endforeach; ?>
    <br class="clear" />
</div>
<?php
    }

}

/**
 * The Horde_Form_Type_fima_dspostings class provides a form field for selecting
 * the handling (delete/shift) of postings when deleting an account.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Horde_Form_Type_fima_dspostings extends Horde_Form_Type {

    function getInfo(&$vars, &$var, &$info)
    {
        $ds = $var->getValue($vars);
        if ($ds['type'] == 'delete') {
            $info = true;
        } elseif ($ds['type'] == 'shift') {
            $info = $ds['account'];
        } else {
            $info = false;
        }
    }

    function isValid(&$var, &$vars, $value, &$message)
    {
        if ($value['type'] == 'shift' && $value['account'] == $vars->get('account_id')) {
            $message = _("Select another account where to shift postings to.");
            return false;
        }

        return true;
    }

}

/**
 * The Horde_Form_Type_fima_dssubaccounts class provides a form field for selecting
 * the handling (delete/shift) of subaccounts when deleting an account.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Horde_Form_Type_fima_dssubaccounts extends Horde_Form_Type {

    function getInfo(&$vars, &$var, &$info)
    {
        $ds = $var->getValue($vars);
        if ($ds['type'] == 'none') {
            $info = false;
        } elseif ($ds['type'] == 'delete') {
            $info = true;
        } elseif ($ds['type'] == 'shift') {
            $info = $ds['account'];
        } else {
            $info = false;
        }
    }

    function isValid(&$var, &$vars, $value, &$message)
    {
        if ($value['type'] == 'shift' && $value['account'] == $vars->get('account_id')) {
            $message = _("Select another account where to shift subaccount postings to.");
            return false;
        }

        return true;
    }

}
