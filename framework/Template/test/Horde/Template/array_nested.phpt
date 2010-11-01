--TEST--
Nested Array Test
--FILE--
<?php

if (defined('E_DEPRECATED')) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

require dirname(__FILE__) . '/../../../lib/Horde/Template.php';
$template = new Horde_Template(array('basepath' => dirname(__FILE__)));
$categories = array('fruit', 'veggie', 'thing');
$subcats = array('fruit' => array('apple', 'pear'),
                 'veggie' => array('tomato', 'potato', 'carrot', 'onion'),
                 'thing' => array('spoon', 'paperbag', 'tool'));
$template->set('categories', $categories);
foreach ($categories as $c) {
    $template->set('subcat_' . $c, $subcats[$c]);
}
$template->set('keyed', array('widgets' => array(
     'key1' => 'zipit',
     'key2' => 'twisty',
     'key3' => 'doowhopper'
)));
echo $template->fetch('/array_nested.html');

?>
--EXPECT--
fruit
    apple
    pear
veggie
    tomato
    potato
    carrot
    onion
thing
    spoon
    paperbag
    tool
widgets
    zipit
    twisty
    doowhopper
