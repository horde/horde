<?php

$objects['object'] = array(
    'class'       => 'Horde_Kolab_Server_Object',
    'label'       => _("Object"),
    'list_label'  => _("Objects"),
    'attributes'  => array(
        'override' => true,
        'fields' => array(
            'id' => array(
                'title' => _("Object id"),
                'width' => 80,
                'link_view'=> true,
            ),
        ),
    ),
);

$objects['user'] = array(
    'class'       => 'Horde_Kolab_Server_Object_Kolab_User',
    'label'       => _("User"),
    'list_label'  => _("Users"),
    'attributes'  => array(
        'override' => true,
        'fields' => array(
            'sn' => array(
                'title' => _("Last name"),
                'width' => 20,
            ),
            'givenName' => array(
                'title' => _("First name"),
                'width' => 20,
            ),
            'mail' => array(
                'title' => _("E-mail"),
                'width' => 20,
                'link_view'=> true,
            ),
            'uid' => array(
                'title' => _("User ID"),
                'width' => 20,
            ),
        ),
    ),
);

$objects['admin'] = array(
    'class'       => 'Horde_Kolab_Server_Object_Kolab_Administrator',
    'label'       => _("Administrator"),
    'list_label'  => _("Administrators"),
    'attributes'  => array(
    ),
);

$objects['kolabuser'] = array(
    'class'       => 'Horde_Kolab_Server_Object_Kolab_User',
    'preferred'   => true,
    'label'       => _("Kolab user"),
    'list_label'  => _("Kolab users"),
    'list_attributes'  => array(
        'sn' => array(
            'title' => _("Last name"),
            'width' => 20,
        ),
        'givenName' => array(
            'title' => _("First name"),
            'width' => 20,
        ),
        'mail' => array(
            'title' => _("E-mail"),
            'width' => 20,
            'link_view'=> true,
        ),
        'uid' => array(
            'title' => _("User ID"),
            'width' => 20,
        ),
    ),
    'attributes'  => array(
        'hide' => array(
            'objectClass',
            'userPassword',
            'seeAlso',
            'x121Address',
            'registeredAddress',
            'destinationIndicator',
            'preferredDeliveryMethod',
            'telexNumber',
            'teletexTerminalIdentifier',
            'internationaliSDNNumber',
            'kolabEncryptedPassword',
            'kolabHomeMTA',
            'kolabDelegate',
        ),
        'order' => array(
            'mail' => 1,
            'kolabSalutation' => 2,
            'givenName' => 3,
            'middleNames' => 4,
            'sn' => 5,
        ),
        'labels' => array(
            'mail' => _("Account ID"),
        ),
        'fields' => array(
            'kolabSalutation' => array(
                'label' => _("Salutation"),
                'type' => 'enum',
                'params' => array('values' => array(_("Mr.") => _("Mr."),
                                                    _("Mrs.") => _("Mrs.")),
                                  'prompt' => true),
            ),
        ),
    ),
);
