--TEST--
Horde::url() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Horde.php';

class Registry {

    function get()
    {
        return '/hordeurl';
    }

}

$registry = new Horde_Registry();
$conf['server']['name'] = 'example.com';

$uris = array(
    'test.php',
    'test.php?foo=1',
    'test.php?foo=1&bar=2',
    'test.php?foo=1&amp;bar=2',
    'test.php?foo=1&amp;bar=2&amp;baz=3'
);

$fulls = array(false, true);
$ssls = array(0, 1, 3);
$ports = array(80, 443);

foreach ($uris as $uri) {
    foreach ($fulls as $full) {
        foreach ($ssls as $ssl) {
            $conf['use_ssl'] = $ssl;
            foreach ($ports as $port) {
                $conf['server']['port'] = $port;
                echo Horde::url($uri, $full, -1) . "\n";
                unset($_COOKIE[session_name()]);
                echo Horde::url($uri, $full, 0) . "\n";
                $_COOKIE[session_name()] = array();
                echo Horde::url($uri, $full, 0) . "\n";
                echo Horde::url($uri, $full, 1) . "\n";
            }
        }
    }
}

?>
--EXPECT--
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
test.php
test.php?PHPSESSID=
http://example.com/hordeurl/test.php
http://example.com/hordeurl/test.php?PHPSESSID=
http://example.com/hordeurl/test.php
http://example.com/hordeurl/test.php?PHPSESSID=
http://example.com:443/hordeurl/test.php
http://example.com:443/hordeurl/test.php?PHPSESSID=
http://example.com:443/hordeurl/test.php
http://example.com:443/hordeurl/test.php?PHPSESSID=
https://example.com:80/hordeurl/test.php
https://example.com:80/hordeurl/test.php?PHPSESSID=
https://example.com:80/hordeurl/test.php
https://example.com:80/hordeurl/test.php?PHPSESSID=
https://example.com/hordeurl/test.php
https://example.com/hordeurl/test.php?PHPSESSID=
https://example.com/hordeurl/test.php
https://example.com/hordeurl/test.php?PHPSESSID=
http://example.com/hordeurl/test.php
http://example.com/hordeurl/test.php?PHPSESSID=
http://example.com/hordeurl/test.php
http://example.com/hordeurl/test.php?PHPSESSID=
http://example.com/hordeurl/test.php
http://example.com/hordeurl/test.php?PHPSESSID=
http://example.com/hordeurl/test.php
http://example.com/hordeurl/test.php?PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
test.php?foo=1
test.php?foo=1&amp;PHPSESSID=
http://example.com/hordeurl/test.php?foo=1
http://example.com/hordeurl/test.php?foo=1&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1
http://example.com/hordeurl/test.php?foo=1&PHPSESSID=
http://example.com:443/hordeurl/test.php?foo=1
http://example.com:443/hordeurl/test.php?foo=1&PHPSESSID=
http://example.com:443/hordeurl/test.php?foo=1
http://example.com:443/hordeurl/test.php?foo=1&PHPSESSID=
https://example.com:80/hordeurl/test.php?foo=1
https://example.com:80/hordeurl/test.php?foo=1&PHPSESSID=
https://example.com:80/hordeurl/test.php?foo=1
https://example.com:80/hordeurl/test.php?foo=1&PHPSESSID=
https://example.com/hordeurl/test.php?foo=1
https://example.com/hordeurl/test.php?foo=1&PHPSESSID=
https://example.com/hordeurl/test.php?foo=1
https://example.com/hordeurl/test.php?foo=1&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1
http://example.com/hordeurl/test.php?foo=1&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1
http://example.com/hordeurl/test.php?foo=1&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1
http://example.com/hordeurl/test.php?foo=1&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1
http://example.com/hordeurl/test.php?foo=1&PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com:443/hordeurl/test.php?foo=1&bar=2
http://example.com:443/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com:443/hordeurl/test.php?foo=1&bar=2
http://example.com:443/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
https://example.com:80/hordeurl/test.php?foo=1&bar=2
https://example.com:80/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
https://example.com:80/hordeurl/test.php?foo=1&bar=2
https://example.com:80/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
https://example.com/hordeurl/test.php?foo=1&bar=2
https://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
https://example.com/hordeurl/test.php?foo=1&bar=2
https://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
test.php?foo=1&amp;bar=2
test.php?foo=1&amp;bar=2&amp;PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com:443/hordeurl/test.php?foo=1&bar=2
http://example.com:443/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com:443/hordeurl/test.php?foo=1&bar=2
http://example.com:443/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
https://example.com:80/hordeurl/test.php?foo=1&bar=2
https://example.com:80/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
https://example.com:80/hordeurl/test.php?foo=1&bar=2
https://example.com:80/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
https://example.com/hordeurl/test.php?foo=1&bar=2
https://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
https://example.com/hordeurl/test.php?foo=1&bar=2
https://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2
http://example.com/hordeurl/test.php?foo=1&bar=2&PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
test.php?foo=1&amp;bar=2&amp;baz=3
test.php?foo=1&amp;bar=2&amp;baz=3&amp;PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
http://example.com:443/hordeurl/test.php?foo=1&bar=2&baz=3
http://example.com:443/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
http://example.com:443/hordeurl/test.php?foo=1&bar=2&baz=3
http://example.com:443/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
https://example.com:80/hordeurl/test.php?foo=1&bar=2&baz=3
https://example.com:80/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
https://example.com:80/hordeurl/test.php?foo=1&bar=2&baz=3
https://example.com:80/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
https://example.com/hordeurl/test.php?foo=1&bar=2&baz=3
https://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
https://example.com/hordeurl/test.php?foo=1&bar=2&baz=3
https://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3
http://example.com/hordeurl/test.php?foo=1&bar=2&baz=3&PHPSESSID=
