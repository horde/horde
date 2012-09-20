<?php
/**
 * Horde_Form for editing notepads.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 */
/**
 * The Mnemo_Form_EditNotepadclass provides the form for editing a notepad.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Mnemo
 */
class Mnemo_Form_EditNotepad extends Horde_Form
{
    /**
     * Notepad being edited
     */
    protected $_notepad;

    public function __construct($vars, $notepad)
    {
        $this->_notepad = $notepad;

        $owner = $notepad->get('owner') == $GLOBALS['registry']->getAuth() ||
            (is_null($notepad->get('owner')) &&
             $GLOBALS['registry']->isAdmin());

        parent::__construct(
            $vars,
            $owner
                ? sprintf(_("Edit %s"), $notepad->get('name'))
                : $notepad->get('name')
        );

        $this->addHidden('', 'n', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);

        if (!$owner) {
            $v = $this->addVariable(_("Owner"), 'owner', 'text', false);
            $owner_name = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Identity')
                ->create($notepad->get('owner'))
                ->getValue('fullname');
            if (trim($owner_name) == '') {
                $owner_name = $notepad->get('owner');
            }
            $v->setDefault($owner_name ? $owner_name : _("System"));
        }

        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        /* Permissions link. */
        if (empty($GLOBALS['conf']['share']['no_sharing']) && $owner) {
            $url = Horde::url($GLOBALS['registry']->get('webroot', 'horde')
                              . '/services/shares/edit.php')
                ->add(array('app' => 'mnemo', 'share' => $notepad->getName()));
            $this->addVariable(
                 '', '', 'link', false, false, null,
                 array(array(
                     'url' => $url,
                     'text' => _("Change Permissions"),
                     'onclick' => Horde::popupJs(
                          $url,
                          array('params' => array('urlencode' => true)))
                          . 'return false;',
                     'class' => 'horde-button',
                     'target' => '_blank')
                 )
            );
        }

        $this->setButtons(array(
            _("Save"),
            array('class' => 'horde-delete', 'value' => _("Delete")),
            array('class' => 'horde-cancel', 'value' => _("Cancel"))
        ));
    }

    public function execute()
    {
        switch ($this->_vars->submitbutton) {
        case _("Save"):
            $this->_notepad->set('name', $this->_vars->get('name'));
            $this->_notepad->set('desc', $this->_vars->get('description'));
            $this->_notepad->save();
            break;
        case _("Delete"):
            Horde::url('notepads/delete.php')
                ->add('n', $this->_vars->n)
                ->redirect();
            break;
        case _("Cancel"):
            Horde::url('', true)->redirect();
            break;
        }
    }

}
