<?php
/**
 * Form for editing/updating a contact.
 *
 * @package Turba
 */
class Turba_Form_EditContact extends Turba_Form_ContactBase
{
    /**
     *
     * @var Turba_Object
     */
    protected $_contact;

    /**
     * @param Turba_Object $contact
     * @param array $vars
     */
    public function __construct($vars, Turba_Object $contact)
    {
        global $conf;

        parent::__construct($vars, '', 'Turba_View_EditContact');
        $this->_contact = $contact;

        $this->setButtons(_("Save"));
        $this->addHidden('', 'url', 'text', false);
        $this->addHidden('', 'source', 'text', true);
        $this->addHidden('', 'key', 'text', false);

        parent::_addFields($this->_contact);

        $this->addVariable(_("Add file"), 'vfs', 'file', false);

        $object_values = $vars->get('object');
        $object_keys = array_keys($contact->attributes);
        foreach ($object_keys as $info_key) {
            if (!isset($object_values[$info_key])) {
                $object_values[$info_key] = $contact->getValue($info_key);
            }
        }
        $vars->set('object', $object_values);
        $vars->set('source', $contact->getSource());
    }

    public function execute()
    {
        global $conf, $notification;

        if (!$this->validate($this->_vars)) {
            throw new Turba_Exception('Invalid');
        }

        /* Form valid, save data. */
        $this->getInfo($this->_vars, $info);

        /* Update the contact. */
        foreach ($info['object'] as $info_key => $info_val) {
            if ($info_key != '__key') {
                if ($GLOBALS['attributes'][$info_key]['type'] == 'image' && !empty($info_val['file'])) {
                    $this->_contact->setValue($info_key, file_get_contents($info_val['file']));
                    if (isset($info_val['type'])) {
                        $this->_contact->setValue($info_key . 'type', $info_val['type']);
                    }
                } else {
                    $this->_contact->setValue($info_key, $info_val);
                }
            }
        }

        try {
            $this->_contact->store();
        } catch (Turba_Exception $e) {
            Horde::logMessage($e, 'ERR');
            $notification->push(_("There was an error saving the contact. Contact your system administrator for further help."), 'horde.error');
            throw $e;
        }

        if (isset($info['vfs'])) {
            try {
                $this->_contact->addFile($info['vfs']);
                $notification->push(sprintf(_("\"%s\" updated."), $this->_contact->getValue('name')), 'horde.success');
            } catch (Turba_Exception $e) {
                $notification->push(sprintf(_("\"%s\" updated, but saving the uploaded file failed: %s"), $this->_contact->getValue('name'), $e->getMessage()), 'horde.warning');
            }
        } else {
            $notification->push(sprintf(_("\"%s\" updated."), $this->_contact->getValue('name')), 'horde.success');
        }

        return true;
    }

    /**
     */
    public function renderActive($renderer, $vars, $action, $method)
    {
        parent::renderActive($renderer, $vars, $action, $method);

        if ($this->_contact->isGroup()) {
            $edit_url = Horde::url('browse.php')->add(array(
                'key' => $this->_contact->getValue('__key'),
                'source' => $this->_contact->getSource()
            ));

            echo '<div class="editGroupMembers">' .
                Horde::link($edit_url) . '<span class="iconImg groupImg"></span>' . _("Edit/View Group Members") . '</a>' .
                '</div>';
        }
    }

}
