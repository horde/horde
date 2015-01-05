<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   IMP
 */

/**
 * Generate contact avatar image by using local addressbook.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contacts_Avatar_Addressbook implements IMP_Contacts_Avatar_Backend
{
    /**
     */
    public function avatarImg($email)
    {
        global $injector, $registry;

        if ($registry->hasMethod('contacts/search')) {
            $contacts = $injector->getInstance('IMP_Contacts');

            try {
                $res = $registry->call('contacts/search', array(
                    $email,
                    array(
                        'customStrict' => array('email'),
                        'fields' => array_fill_keys($contacts->sources, array('email')),
                        'returnFields' => array('photo', 'phototype'),
                        'sources' => $contacts->sources
                    )
                ));

                if (isset($res[$email][0]['photo'])) {
                    try {
                        $img = $injector->getInstance('Horde_Core_Factory_Image')->create();
                        $img->loadString($res[$email][0]['photo']['load']['data']);
                        $img->resize(80, 80, true);

                        $data = $img->raw(true);
                        $type = $img->getContentType();
                    } catch (Horde_Exception $e) {
                        $data = $res[$email][0]['photo']['load']['data'];
                        $type = $res[$email][0]['phototype'];
                    }

                    return array(
                        'desc' => '',
                        'url' => Horde_Url_Data::create($type, $data)
                    );
                }
            } catch (Horde_Exception $e) {}
        }

        return null;
    }

}
