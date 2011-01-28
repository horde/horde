#!/usr/bin/env php
<?php
/**
 * Script to test the SyncML implementation.
 *
 * Takes a pre-recorded testcase, stuffs the data into the SyncML server, and
 * then compares the output to see if it matches.
 *
 * See http://wiki.horde.org/SyncHowTo for a description how to create a test
 * case.
 *
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncML
 */

/* Current limitations:
 *
 * - $service is set globally, so syncing multiple databases at once is not
 *   dealt with: should be fixed easily by retrieving service using some
 *   regular expression magic.
 *
 * - Limited to 3 messages per session style. This is more serious.
 *
 * - Currently the test case has to start with a slowsync. Maybe we can remove
 *   this restriction and thus allow test cases with "production phones".
 *   An idea to deal with this: make testsync.php work with *any* recorded
 *   sessions:
 *   - change any incoming auth to syncmltest:syncmltest
 *   - identify twowaysync and create fake anchors for that */

require_once 'SyncML.php';

define('SYNCMLTEST_USERNAME', 'syncmltest');

// Setup default backend parameters:
$syncml_backend_driver = 'Horde';
$syncml_backend_parms = array(
    /* debug output to this dir, must be writeable be web server: */
    'debug_dir' => '/tmp/sync',
    /* log all (wb)xml packets received or sent to debug_dir: */
    'debug_files' => true,
    /* Log everything: */
    'log_level' => 'DEBUG');

/* Get any options. */
if (!isset($argv)) {
    print_usage();
}

/* Get rid of the first arg which is the script name. */
$this_script = array_shift($argv);

while ($arg = array_shift($argv)) {
    if ($arg == '--help') {
        print_usage();
    } elseif (strstr($arg, '--setup')) {
        $testsetuponly = true;
    } elseif (strstr($arg, '--url')) {
        list(, $url) = explode('=', $arg);
    } elseif (strstr($arg, '--dir')) {
        list(, $dir) = explode('=', $arg);
    } elseif (strstr($arg, '--dsn')) {
        list(, $dsn) = explode('=', $arg);
        $syncml_backend_parms['dsn'] = $dsn;
        $syncml_backend_driver = 'Sql';
    } elseif (strstr($arg, '--debug')) {
        if (strstr($arg, '=') !== false) {
            list(, $debuglevel) = explode('=', $arg);
        } else {
            $debuglevel = 5;
        }
    } else {
        print_usage("Unrecognised option $arg");
    }
}

require_once 'Log.php';
require_once 'SyncML/Device.php';
require_once 'SyncML/Device/Sync4j.php';
require_once 'SyncML/Backend.php';

/* Do Horde includes if test for horde backend: */
if ($syncml_backend_driver == 'Horde') {
    require_once dirname(__FILE__) . '/../../../lib/Application.php';
    Horde_Registry::appInit('horde', array('cli' => true, 'session_control' => 'none'));
}

if (!empty($testsetuponly)) {
    $testbackend = SyncML_Backend::factory($syncml_backend_driver,
                                           $syncml_backend_parms);
    $testbackend->testSetup(SYNCMLTEST_USERNAME, 'syncmltest');
    echo "Test setup for user syncmltest done. Now you can start to record a test case.\n";
    exit(0);
}

/* Set this to true to skip cleanup after tests.  */
$skipcleanup = false;

/* mapping from LocUris to UIDs. Currently unused */
$mapping_locuri2uid = array();

/* The actual testing takes place her: */
if (!empty($dir)) {
    test($dir);
} else {
    $d = dir('./');
    while (false !== ($entry = $d->read())) {
        if (preg_match('/^testcase_/', $entry) && is_dir($d->path . $entry)) {
            test($d->path . $entry);
        }
    }
    $d->close();
}

/**
 * Retrieves the reference data for one packet.
 */
function getServer($name, $number)
{
    if (!file_exists($name . '/syncml_server_' . $number . '.xml')) {
        return false;
    }
    return file_get_contents($name . '/syncml_server_' . $number . '.xml');
}

/**
 * Retrieves the client data to be sent to the server
 */
function getClient($name, $number)
{
    if (!file_exists($name . '/syncml_client_' . $number . '.xml')) {
        return false;
    }
    return file_get_contents($name . '/syncml_client_' . $number . '.xml');
}


/**
 * Compares $r and $ref.
 *
 * Exits if any nontrivial differences are found.
 */
function check($name, $r, $ref, $packetnum = 'unknown')
{
    $r   = trim(decodebase64data($r));
    $ref = trim(decodebase64data($ref));

    /* various tweaking: */
    // case issues:
    $search = array(
        '| xmlns="syncml:SYNCML1.1"|i',
        '|<DevID>.*?</DevID>|i',
        '|<\?xml[^>]*>|i',
        '|<!DOCTYPE[^>]*>|i',

        /* Ignore timestamps used by various devices. */
        '/(\r\n|\r|\n)DCREATED.*?(\r\n|\r|\n)/',
        '/(\r\n|\r|\n)LAST-MODIFIED.*?(\r\n|\r|\n)/',
        '/(\r\n|\r|\n)DTSTAMP.*?(\r\n|\r|\n)/',
        '/(\r\n|\r|\n)X-WR-CALNAME.*?(\r\n|\r|\n)/',

        /* Issues with priority, ignore for now. */
        '/(\r\n|\r|\n)PRIORITY.*?(\r\n|\r|\n)/',

        '|<Data>\s*(.*?)\s*</Data>|s',
        '/\r/',
        '/\n/');

    $replace = array(
        ' xmlns="syncml:SYNCML1.1"',
        '<DevID>IGNORED</DevID>',
        '',
        '',

        /* Ignore timestamps used by various devices. */
        '$1',
        '$1',
        '$1',
        '$1',

        /* Issues with priority, ignore for now. */
        '$1PRIORITY: IGNORED$2',

        '<Data>$1</Data>',
        '\r',
        '\n');

    $r   = preg_replace($search, $replace, $r);
    $ref = preg_replace($search, $replace, $ref);

    if (strcasecmp($r, $ref) !== 0) {
        echo "Error in test case $name packet $packetnum\nReference:\n$ref\nResult:\n$r\n";
        for($i = 0; $r[$i] == $ref[$i] && $i <= strlen($r); ++$i) {
            // Noop.
        }
        echo "at position $i\n";
        echo '"' . substr($ref, $i, 10) . '" vs. "' . substr($r, $i, 10) . "\"\n";
        exit(1);
    }
}


/**
 * Simulates a call to the SyncML server by sending data to the server.
 * Returns the result received from the server.
 */
function getResponse($data)
{
    if (!empty($GLOBALS['url'])) {
        /* Call externally using curl. */
        $tmpfile = tempnam('tmp','syncmltest');
        $fh = fopen($tmpfile, 'w');
        fwrite($fh, $data);
        fclose($fh);
        $output = shell_exec(sprintf('curl -s -H "Content-Type: application/vnd.syncml+xml" --data-binary @%s "%s"',
                                     $tmpfile,
                                     $GLOBALS['url']));
        unlink($tmpfile);
        return $output;
    }

    /* Create and setup the test backend */
    $GLOBALS['backend'] = SyncML_Backend::factory(
        $GLOBALS['syncml_backend_driver'],
        $GLOBALS['syncml_backend_parms']);
    $h = new SyncML_ContentHandler();
    $response = $h->process($data, 'application/vnd.syncml+xml');
    $GLOBALS['backend']->close();
    return $response;
}

function getUIDs($data)
{
    // <LocURI>20060130082509.4nz5ng6sm9wk@127.0.0.1</LocURI>
    if (!preg_match('|<Sync>.*</Sync>|s', $data, $m)) {
        return array();
    }
    $data = $m[0];
    // echo $data;
    $count = preg_match_all('|(?<=<LocURI>)\d+[^<]*@[^<]*(?=</LocURI>)|is', $data, $m);
    // if(count($m[0])>0) { var_dump($m[0]); }

    return $m[0];
}


/* Decode sync4j base64 decoded data for readable debug outout. */
function decodebase64data($s)
{
    return  preg_replace_callback('|(?<=<Data>)[0-9a-zA-Z\+\/=]{6,}(?=</Data>)|i',
                                  create_function('$matches','return base64_decode($matches[0]);'),
                                  $s);

}

function convertAnchors(&$ref,$r, $anchor = '')
{
    if ($anchor) {
        $count = preg_match_all('|<Last>(\d+)</Last>|i', $ref, $m);
        if ($count > 0 ) {
            $temp = $m[1][$count-1];
        }
        $ref = str_replace("<Last>$temp</Last>", "<Last>$anchor</Last>" , $ref);
    }
    $count =  preg_match_all('|<Next>(\d+)</Next>|i', $r, $m);
    if ($count > 0 ) {
        $anchor = $m[1][$count-1];
        $count = preg_match_all('|<Next>(\d+)</Next>|i', $ref, $m);
        if ($count > 0 ) {
            $temp = $m[1][$count-1];
            $ref = str_replace("<Next>$temp</Next>", "<Next>$anchor</Next>" , $ref);
        }
    } else {
        $anchor = '';
    }

    return $anchor;
}

/**
 * Tests one sync session.
 *
 * Returns true on successful test and false on no (more) test data available
 * for this $startnumber.  Exits if test fails.
 */
function testSession($name, $startnumber, &$anchor)
{
    global $debuglevel;

    $uids = $refuids = array();

    $number = $startnumber;
    while ($ref = getServer($name, $number)) {
        if ($debuglevel >= 2) {
        }
        testPre($name, $number);
        $number++;
    }

    $number = $startnumber;
    while ($ref = getServer($name, $number)) {
        if ($debuglevel >= 2) {
            echo "handling packet $number\n";
        }

        $c = str_replace($refuids, $uids, getClient($name, $number));
        $resp = getResponse($c);

        /* Set anchor from prev sync as last anchor: */
        /* @TODO: this assumes startnumber in first packet */
        if ($number == $startnumber) {
            $anchor = convertAnchors($ref, $resp, $anchor);
        }

        $resp     = sortChanges($resp);
        $ref      = sortChanges($ref);
        $tuids    = getUIDs($resp);
        $trefuids = getUIDs($ref);
        $uids     = array_merge($uids, $tuids);
        $refuids  = array_merge($refuids, $trefuids);
        $ref      = str_replace($refuids, $uids, $ref);

        parse_map($c);
        check($name, $resp, $ref, $number);

        $number++;
    }

    if ($number == $startnumber) {
        // No packet found at all, end of test.
        return false;
    }

    return true;
}

/**
 * Parses and stores the map info sent by the client.
 */
function parse_map($content)
{

/* Example:
<MapItem>
<Target><LocURI>20060610121904.4svcwdpc5lkw@voltaire.local</LocURI></Target>
<Source><LocURI>000000004FCBE97B738E984EAF085560B1DD2D50A4412000</LocURI></Source>
</MapItem>
*/

    global $mapping_locuri2uid;
    if (preg_match_all('|<MapItem>\s*<Target>\s*<LocURI>(.*?)</LocURI>.*?<Source>\s*<LocURI>(.*?)</LocURI>.*?</MapItem>|si', $content, $m, PREG_SET_ORDER)) {
        foreach ($m as $c) {
            $mapping_locuri2uid[$c[2]] = $c[1]; // store UID used by server
        }
    }

}

/**
 * When a test case contains adds/modifies/deletes being sent to the server,
 * these changes must be extracted from the test data and manually performed
 * using the api to achieve the desired behaviour by the server
 *
 * @throws Horde_Exception
 */
function testPre($name, $number)
{
    global $debuglevel;

    $ref0 = getClient($name, $number);

    // Extract database (in horde: service).
    if (preg_match('|<Alert>.*?<Target>\s*<LocURI>([^>]*)</LocURI>.*?</Alert>|si', $ref0, $m)) {
        $GLOBALS['service'] = $m[1];
    }

    if (!preg_match('|<SyncHdr>.*?<Source>\s*<LocURI>(.*?)</LocURI>.*?</SyncHdr>|si', $ref0, $m)) {
        echo $ref0;
        throw new Horde_Exception('Unable to find device id');
    }
    $device_id = $m[1];

    // Start backend session if not already done.
    if ($GLOBALS['testbackend']->getSyncDeviceID() != $device_id) {
        $GLOBALS['testbackend']->sessionStart($device_id, null, SYNCML_BACKENDMODE_TEST);
    }

    // This makes a login even when a logout has occured when the session got
    // deleted.
    $GLOBALS['testbackend']->setUser(SYNCMLTEST_USERNAME);

    $ref1 = getServer($name, $number + 1);
    if (!$ref1) {
        return;
    }

    $ref1 = str_replace(array('<![CDATA[', ']]>', '<?xml version="1.0"?><!DOCTYPE SyncML PUBLIC "-//SYNCML//DTD SyncML 1.1//EN" "http://www.syncml.org/docs/syncml_represent_v11_20020213.dtd">'),
                        '', $ref1);

    // Check for Adds.
    if (preg_match_all('|<Add>.*?<type[^>]*>(.*?)</type>.*?<LocURI[^>]*>(.*?)</LocURI>.*?<data[^>]*>(.*?)</data>.*?</Add|si', $ref1, $m, PREG_SET_ORDER)) {
        foreach ($m as $c) {
            list(, $contentType, $locuri, $data) = $c;
            // Some Sync4j tweaking.
            switch (Horde_String::lower($contentType)) {
            case 'text/x-s4j-sifn' :
                $data = SyncML_Device_sync4j::sif2vnote(base64_decode($data));
                $contentType = 'text/x-vnote';
                $service = 'notes';
                break;

            case 'text/x-s4j-sifc' :
                $data = SyncML_Device_sync4j::sif2vcard(base64_decode($data));
                $contentType = 'text/x-vcard';
                $service = 'contacts';
                break;

            case 'text/x-s4j-sife' :
                $data = SyncML_Device_sync4j::sif2vevent(base64_decode($data));
                $contentType = 'text/calendar';
                $service = 'calendar';
                break;

            case 'text/x-s4j-sift' :
                $data = SyncML_Device_sync4j::sif2vtodo(base64_decode($data));
                $contentType = 'text/calendar';
                $service = 'tasks';
                break;

            case 'text/x-vcalendar':
            case 'text/calendar':
                if (preg_match('/(\r\n|\r|\n)BEGIN:\s*VTODO/', $data)) {
                    $service = 'tasks';
                } else {
                    $service = 'calendar';
                }
                break;

            default:
                throw new Horde_Exception("Unable to find service for contentType=$contentType");
            }

            $result = $GLOBALS['testbackend']->addEntry($service, $data, $contentType);
            if (is_a($result, 'PEAR_Error')) {
                echo "error importing data into $service:\n$data\n";
                throw new Horde_Exception_Prior($result);
            }

            if ($debuglevel >= 2) {
                echo "simulated $service add of $result as $locuri!\n";
                echo '   at ' . date('Y-m-d H:i:s') . "\n";
                if ($debuglevel >= 10) {
                    echo "data: $data\nsuid=$result\n";
                }
            }

            // Store UID used by server.
            $GLOBALS['mapping_locuri2uid'][$locuri] = $result;
        }
    }

    // Check for Replaces.
    if (preg_match_all('|<Replace>.*?<type[^>]*>(.*?)</type>.*?<LocURI[^>]*>(.*?)</LocURI>.*?<data[^>]*>(.*?)</data>.*?</Replace|si', $ref1, $m, PREG_SET_ORDER)) {
        foreach ($m as $c) {
            list(, $contentType, $locuri, $data) = $c;
            // Some Sync4j tweaking.
            switch (Horde_String::lower($contentType)) {
            case 'sif/note' :
            case 'text/x-s4j-sifn' :
                $data = SyncML_Device_sync4j::sif2vnote(base64_decode($data));
                $contentType = 'text/x-vnote';
                $service = 'notes';
                break;

            case 'sif/contact' :
            case 'text/x-s4j-sifc' :
                $data = SyncML_Device_sync4j::sif2vcard(base64_decode($data));
                $contentType = 'text/x-vcard';
                $service = 'contacts';
                break;

            case 'sif/calendar' :
            case 'text/x-s4j-sife' :
                $data = SyncML_Device_sync4j::sif2vevent(base64_decode($data));
                $contentType = 'text/calendar';
                $service = 'calendar';
                break;

            case 'sif/task' :
            case 'text/x-s4j-sift' :
                $data = SyncML_Device_sync4j::sif2vtodo(base64_decode($data));
                $contentType = 'text/calendar';
                $service = 'tasks';
                break;

            case 'text/x-vcalendar':
            case 'text/calendar':
                if (preg_match('/(\r\n|\r|\n)BEGIN:\s*VTODO/', $data)) {
                    $service = 'tasks';
                } else {
                    $service = 'calendar';
                }
                break;

            default:
                throw new Horde_Exception("Unable to find service for contentType=$contentType");
            }

            /* Get SUID. */
            $suid = $GLOBALS['testbackend']->getSuid($service, $locuri);
            if (empty($suid)) {
                throw new Horde_Exception("Unable to find SUID for CUID $locuri for simulating replace");
            }

            $result = $GLOBALS['testbackend']->replaceEntry($service, $data, $contentType, $suid);
            if (is_a($result, 'PEAR_Error')) {
                echo "Error replacing data $locuri suid=$suid!\n";
                throw new Horde_Exception_Prior($result);
            }

            if ($debuglevel >= 2) {
                echo "simulated $service replace of $locuri suid=$suid!\n";
                if ($debuglevel >= 10) {
                    echo "data: $data\nnew id=$result\n";
                }
            }
        }
    }

    // Check for Deletes.
    // <Delete><CmdID>5</CmdID><Item><Target><LocURI>1798147</LocURI></Target></Item></Delete>
    if (preg_match_all('|<Delete>.*?<Target>\s*<LocURI>(.*?)</LocURI>|si', $ref1, $m, PREG_SET_ORDER)) {
        foreach ($m as $d) {
            list(, $locuri) = $d;

            /* Get SUID. */
            $service = $GLOBALS['service'];
            $suid = $GLOBALS['testbackend']->getSuid($service, $locuri);
            if (empty($suid)) {
                // Maybe we have a handletaskincalendar.
                if ($service == 'calendar') {
                    if ($debuglevel >= 2) {
                        echo "special tasks delete...\n";
                    }
                    $service = 'tasks';
                    $suid = $GLOBALS['testbackend']->getSuid($service, $locuri);
                }
            }
            if (empty($suid)) {
                throw new Horde_Exception("Unable to find SUID for CUID $locuri for simulating $service delete");
            }

            $result = $GLOBALS['testbackend']->deleteEntry($service, $suid);
            // @TODO: simulate a delete by just faking some history data.
            if (is_a($result, 'PEAR_Error')) {
                echo "Error deleting data $locuri!";
                throw new Horde_Exception_Prior($result);
            }
            if ($debuglevel >= 2) {
                echo "simulated $service delete of $suid!\n";
            }
        }
    }
}

/**
 * Executes one test case.
 *
 * A test cases consists of various pre-recorded .xml packets in directory
 * $name.
 */
function test($name)
{
    system($GLOBALS['this_script'] . ' --setup');
    $GLOBALS['testbackend'] = SyncML_Backend::factory(
        $GLOBALS['syncml_backend_driver'],
        $GLOBALS['syncml_backend_parms']);
    $GLOBALS['testbackend']->testStart(SYNCMLTEST_USERNAME, 'syncmltest');

    $packetNum = 10;
    $anchor = '';
    while (testsession($name, $packetNum, $anchor) === true) {
        $packetNum += 10;
    }

    /* Cleanup */
    if (!$GLOBALS['skipcleanup']) {
        $GLOBALS['testbackend'] ->testTearDown();
    }

    echo "testcase $name: passed\n";
}


/**
 * We can't know in which ordeer changes (Add|Replace|Delete) changes are
 * reported by the backend. One time it may list change1 and then change2,
 * another time first change2 and then change1. So we just sort them to get
 * a comparable result. The LocURIs must be ignored for the sort as we
 * fake them during the test.
 *
 * @throws Horde_Exception
 */
function sortChanges($content)
{
    $bak = $content;

    if (preg_match_all('!<(?:Add|Replace|Delete)>.*?</(?:Add|Replace|Delete)>!si', $content, $ma)) {
        $eles = $ma[0];
        preg_match('!^(.*?)<(?:Add|Replace|Delete)>.*</(?:Add|Replace|Delete)>(.*?)$!si', $content, $m);
        //        var_dump($eles);
        //        var_dump($m);
        usort($eles, 'cmp');
        $r = $m[1] . implode('',$eles) . $m[2];
        if (strlen($r) != strlen($bak)) {
            echo "error!\nbefore: $bak\nafter:  $r\n";
            var_dump($m);
            throw new Horde_Exception('failed');
        }
        // the CmdID may no longer fit. So we have to remove this:
        $r = preg_replace('|<CmdID>[^<]*</CmdID>|','<CmdID>IGNORED</CmdID>', $r);
        //echo 'sorted: ' . implode('',$eles) . "\n";
        return $r;
    }

    return $content;
}


function cmp($a, $b)
{
    if (preg_match('|<Data>.*?</Data>|si', $a, $m)) {
        $a = $m[0];
        //echo "MATCH: $a\n";
    } else {
        $a = preg_replace('|<LocURI>.*?<LocURI>|s','', $a);
    }
    if (preg_match('|<Data>.*?</Data>|si', $b, $m)) {
        $b = $m[0];
    } else {
        $b = preg_replace('|<LocURI>.*?<LocURI>|s','', $b);
    }

    if ($a == $b) {
        return 0;
    }

    return ($a < $b) ? -1 : 1;
}

function print_usage($message = '')
{
    if (!empty($message)) {
        echo "testsync.php: $message\n";
    }

    echo <<<USAGE
Usage: testsync.php [OPTIONS]

Possible options:
  --url=RPCURL  Use curl to simulate access to URL for rpc.php. If not
                specified, rpc.php is called internally.
  --dir=DIR     Run test with data in directory DIR. If not spefied use
                all directories starting with testcase_.
  --setup       Don not run any tests. Just create test user syncmltest with
                clean database. This does the setup before recording a test
                case.
  --debug       Produce some debug output.

USAGE;
    exit;
}
