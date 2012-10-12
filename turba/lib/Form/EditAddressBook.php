<?php
/**
 * Horde_Form for editing address books.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Turba
 */

/**
 * The Turba_Form_EditAddressBook class provides the form for
 * editing an address book.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_Form_EditAddressBook extends Horde_Form
{
    /**
     * Address book being edited
     *
     * @var Horde_Share_Object
     */
    protected $_addressbook;

    public function __construct($vars, Horde_Share_Object $addressbook)
    {
        $this->_addressbook = $addressbook;

        $owner = $addressbook->get('owner') == $GLOBALS['registry']->getAuth() ||
            (is_null($addressbook->get('owner')) &&
             $GLOBALS['registry']->isAdmin());

        parent::__construct(
            $vars,
            $owner
                ? sprintf(_("Edit %s"), $addressbook->get('name'))
                : $addressbook->get('name')
        );

        $this->addHidden('', 'a', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);

        if (!$owner) {
            $v = $this->addVariable(_("Owner"), 'owner', 'text', false);
            $owner_name = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Identity')
                ->create($addressbook->get('owner'))
                ->getValue('fullname');
            if (trim($owner_name) == '') {
                $owner_name = $addressbook->get('owner');
            }
            $v->setDefault($owner_name ? $owner_name : _("System"));
        }

        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        /* Permissions link. */
        if (empty($GLOBALS['conf']['share']['no_sharing']) && $owner) {
            $url = Horde::url($GLOBALS['registry']->get('webroot', 'horde')
                              . '/services/shares/edit.php')
                ->add(array('app' => 'turba', 'share' => $addressbook->getName()));
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
            $this->_addressbook->set('name', $this->_vars->get('name'));
            $this->_addressbook->set('desc', $this->_vars->get('description'));
            try {
                $this->_addressbook->save();
            } catch (Horde_Share_Exception $e) {
                throw new Turba_Exception(sprintf(_("Unable to save address book \"%s\": %s"), $this->_vars->get('name'), $e->getMessage()));
            }
            break;
        case _("Delete"):
            Horde::url('addressbooks/delete.php')
                ->add('a', $this->_vars->a)
                ->redirect();
            break;
        case _("Cancel"):
            Horde::url('', true)->redirect();
            break;
        }
    }

}
