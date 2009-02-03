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

for ($i = 1; $i <= 7; $i++) {
    $part = new Horde_Mime_Part();
    $part->setType('text/html');
    $part->setContents(file_get_contents($dirname . '/url' . $i . '.html'));
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
