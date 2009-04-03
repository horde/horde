<?php

$objects['Horde_Kolab_Server_Object'] = array(
    'label'       => _("Object"),
    'list_label'  => _("Objects"),
    'attributes'  => array(
        'id' => array(
            'title' => _("Object id"),
            'width' => 80,
            'link_view'=> true,
        ),
    ),
);

$objects['Horde_Kolab_Server_Object_user'] = array(
    'label'       => _("User"),
    'list_label'  => _("Users"),
    'attributes'  => array(
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
);

$objects['Horde_Kolab_Server_Object_administrator'] = array(
    'label'       => _("Administrator"),
    'list_label'  => _("Administrators"),
    'attributes'  => array(
    ),
);
