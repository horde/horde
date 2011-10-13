<?php

require_once 'Horde/Autoloader/Default.php';

$g = new Horde_Service_Gravatar();
print Horde_Yaml::dump($g->getProfile('wrobel@horde.org'));