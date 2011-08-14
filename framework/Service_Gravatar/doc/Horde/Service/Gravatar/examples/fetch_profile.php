<?php

require_once 'Horde/Autoloader/Default.php';

$g = new Horde_Service_Gravatar();
print $g->fetchProfile('wrobel@horde.org');