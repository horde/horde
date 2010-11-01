--TEST--
Horde_Mime_Viewer_html: URL dereferer tests
--SKIPIF--
skip: Horde_Mime_Viewer has too many dependencies.
--FILE--
<?php

$dirname = dirname(__FILE__);
require_once $dirname . '/../../../lib/Horde/Mime/Part.php';
require_once $dirname . '/../../../lib/Horde/Mime/Viewer.php';
require_once 'Horde.php';

class Registry {
    function get($param, $app = null)
    {
        if ($param == 'webroot' || $app == 'horde') {
            return '/horde';
        }
        die("Can't emulate Registry. \$param: $param, \$app: $app");
    }
}

class Browser {
    function isBrowser($agent)
    {
        return $agent == 'msie';
    }
}

$conf = array(
    'server' => array(
        'name' => 'www.example.com',
        'port' => 80
        ),
    'use_ssl' => 0
);
$registry = new Registry();
$browser = new Browser();

$tests = array(
    '<A HREF=http://66.102.7.147/>link</A>',
    '<A HREF=http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D>link</A>',
    '<A HREF=ht://www.google.com/>link</A>',
    '<A HREF=http://google.com/>link</A>',
    '<A HREF=http://www.google.com./>link</A>',
    '<A HREF="javascript:document.location=\'http://www.google.com/\'">link</A>',
    '<A HREF=http://www.gohttp://www.google.com/ogle.com/>link</A>'
);

foreach ($tests as $val) {
    $part = new Horde_Mime_Part();
    $part->setType('text/html');
    $part->setContents($val);
    $viewer = Horde_Mime_Viewer::factory($part, 'text/html');
    echo $viewer->render();
}

?>
--EXPECT--
<A href="http://www.example.com/horde/services/go.php?url=http%3A%2F%2F66.102.7.147%2F">link</A>
<A href="http://www.example.com/horde/services/go.php?url=http%3A%2F%2F%2577%2577%2577%252E%2567%256F%256F%2567%256C%2565%252E%2563%256F%256D">link</A>
<A href="http://www.example.com/horde/services/go.php?url=ht%3A%2F%2Fwww.google.com%2F">link</A>
<A href="http://www.example.com/horde/services/go.php?url=http%3A%2F%2Fgoogle.com%2F">link</A>
<A href="http://www.example.com/horde/services/go.php?url=http%3A%2F%2Fwww.google.com.%2F">link</A>
<A href="http://www.example.com/horde/services/go.php?url=XSSCleaneddocument.location%3D%27http%3A%2F%2Fwww.google.com%2F%27">link</A>
<A href="http://www.example.com/horde/services/go.php?url=http%3A%2F%2Fwww.gohttp%3A%2F%2Fwww.google.com%2Fogle.com%2F">link</A>
