<?php
/**
 * This class is the Stock form type for clients in the Sesha application.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @since   Sesha 1
 * @package Sesha
 */

class Sesha_Form_Type_Client extends Horde_Form_Type_enum {

    public function init($values = null, $prompt = null)
    {
        global $conf, $registry;

        // Get list of clients, if available.
        if ($registry->hasMethod('clients/getClientSource')) {
            $source = $registry->call('clients/getClientSource');
            if (!empty($source)) {
                $results = $registry->call('clients/searchClients', array(array('')));
                $clientlist = $results[''];
                $clients = array();
                foreach ($clientlist as $client) {
                    $key = isset($client['id']) ? $client['id'] : $client['__key'];
                    $clients[$key] = isset($client[$conf['client']['field']]) ? $client[$conf['client']['field']] : '';
                }
                asort($clients);
                parent::init($clients);
            }
        }
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        $about = array();
        $about['name'] = _("Client");
        return $about;
    }

}
