<?php
/**
 * Form for displaying a contact
 *
 * @package Turba
 */
class Turba_Form_Contact extends Turba_Form_ContactBase
{
    /**
     * @param array $vars  Array of form variables
     * @param Turba_Object $contact
     */
    public function __construct($vars, Turba_Object $contact, $tabs = true, $title = null)
    {
        global $injector, $notification;

        if (is_null($title)) {
            $title = 'Turba_View_Contact';
        }
        parent::__construct($vars, '', $title);

        /* Get the values through the Turba_Object class. */
        $object = array();

        foreach (array_keys($contact->driver->getCriteria()) as $info_key) {
            $object[$info_key] = $contact->getValue($info_key);
        }
        $vars->set('object', $object);

        $this->_addFields($contact, $tabs);

        /* List files. */
        try {
            /* This throws Horde_Exception if VFS not available. */
            $injector->getInstance('Horde_Core_Factory_Vfs')->create('documents');

            $files = $contact->listFiles();
            $this->addVariable(_("Files"), '__vfs', 'html', false);
            $vars->set('__vfs', implode('<br />', array_map(array($contact, 'vfsEditUrl'), $files)));
        } catch (Turba_Exception $e) {
            $notification->push($files, 'horde.error');
        } catch (Horde_Exception $e) {
            /* Ignore: VFS is not active. */
        }
    }

}
