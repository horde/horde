<?php

require_once 'Horde/Autoloader/Default.php';

$g = new Horde_Service_Gravatar();
file_put_contents('/tmp/avatar.jpg', $g->fetchAvatar('wrobel@horde.org'));