<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */

/**
 * Form for displaying a contact
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */
class Turba_Form_Contact extends Turba_Form_ContactBase
{
    /**
     * @param array $vars  Array of form variables
     * @param Turba_Object $contact
     */
    public function __construct(
        $vars, Turba_Object $contact, $tabs = true, $title = null
    )
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

        /* Get tags. */
        if (($tagger = $injector->getInstance('Turba_Tagger')) &&
            !($tagger instanceof Horde_Core_Tagger_Null) &&
            ($uid = $contact->getValue('__uid'))) {
            $object['__tags'] = implode(', ', $tagger->getTags($uid, 'contact'));
        }

        $vars->set('object', $object);

        $this->_addFields($contact, $tabs);

        /* List files. */
        if (!($contact->vfsInit() instanceof Horde_Vfs_Null)) {
            try {
                $files = $contact->listFiles();
                $this->addVariable(_("Files"), '__vfs', 'html', false);
                $vars->set('__vfs', implode('<br />', array_map(array($contact, 'vfsEditUrl'), $files)));
            } catch (Turba_Exception $e) {
                $notification->push($files, 'horde.error');
            }
        }
    }
}
