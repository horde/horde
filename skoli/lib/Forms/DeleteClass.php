<?php
/**
 * Horde_Form for deleting classs.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Skoli
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Skoli_DeleteClassForm class provides the form for
 * deleting a class.
 *
 * @author  Martin Blumenthal <tinu@humbapa.ch>
 * @package Skoli
 */
class Skoli_DeleteClassForm extends Horde_Form {

    /**
     * Class being deleted
     */
    var $_class;

    function Skoli_DeleteClassForm(&$vars, &$class)
    {
        $this->_class = &$class;
        parent::Horde_Form($vars, sprintf(_("Delete %s"), $class->get('name')));

        $this->addHidden('', 'c', 'text', true);
        $this->addVariable(sprintf(_("Really delete the class \"%s\"? This cannot be undone and all data on this class will be permanently removed."), $this->_class->get('name')), 'desc', 'description', false);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            return false;
        }

        if ($this->_class->get('owner') != Horde_Auth::getAuth()) {
            return PEAR::raiseError(_("Permission denied"));
        }

        // Delete the class.
        $storage = &Skoli_Driver::singleton($this->_class->getName());
        $result = $storage->deleteAll();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to delete \"%s\": %s"), $this->_class->get('name'), $result->getMessage()));
        } else {
            // Remove share and all groups/permissions.
            $result = $GLOBALS['skoli_shares']->removeShare($this->_class);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            } else {
               // Remove class from the display list if it exists
               $key = array_search($this->_class->getName(), $GLOBALS['display_classes']);
               if ($key !== false) {
                   unset($GLOBALS['display_classes'][$key]);
                   $GLOBALS['prefs']->setValue('display_classes', serialize($GLOBALS['display_classes']));
               }
            }
        }

        return true;
    }

}
