<?php
/**
 * @package Kolab_Storage
 */

require_once 'Horde/Kolab/Storage/List.php';

$list = Kolab_List::singleton();
var_dump($list->listFolders());
