--TEST--
Horde_Alarm tests.
--SKIPIF--
<?php
$setup = @include dirname(__FILE__) . '/setup.inc';
if (!$setup || empty($params)) {
    echo 'skip No SQL configuration provided.';
}
?>
--FILE--
<?php

include dirname(__FILE__) . '/setup.inc';
require dirname(__FILE__) . '/../../../lib/Horde/Alarm.php';
require 'Horde.php';
require 'Horde/Nls.php';

$alarm = Horde_Alarm::factory('sql', $params);

$now = time();
$date = new Horde_Date($now);
$end = new Horde_Date($now + 3600);
$hash = array('id' => 'personalalarm',
              'user' => 'john',
              'start' => $date,
              'end' => $end,
              'methods' => array(),
              'params' => array(),
              'title' => 'This is a personal alarm.');

var_dump($alarm->set($hash));
var_dump($alarm->exists('personalalarm', 'john'));
$saved = $alarm->get('personalalarm', 'john');
var_dump($saved);
var_dump($saved['start']->compareDateTime($date));
$hash['title'] = 'Changed alarm text';
var_dump($alarm->set($hash));
$date->min--;
$alarm->set(array('id' => 'publicalarm',
                  'start' => $date,
                  'end' => $end,
                  'methods' => array(),
                  'params' => array(),
                  'title' => 'This is a public alarm.'));
var_dump($alarm->listAlarms('john'));
var_dump($alarm->delete('publicalarm', ''));
var_dump($alarm->listAlarms('john'));
$error = $alarm->snooze('personalalarm', 'jane', 30);
var_dump($error->getMessage());
var_dump($alarm->snooze('personalalarm', 'john', 30));
var_dump($alarm->isSnoozed('personalalarm', 'john'));
var_dump($alarm->listAlarms('john'));
var_dump($alarm->listAlarms('john', $end));
var_dump($alarm->set(array('id' => 'noend',
                           'user' => 'john',
                           'start' => $date,
                           'methods' => array('notify'),
                           'params' => array(),
                           'title' => 'This is an alarm without end.')));
var_dump($alarm->listAlarms('john', $end));
var_dump($alarm->delete('noend', 'john'));
var_dump($alarm->delete('personalalarm', 'john'));

?>
--EXPECTF--
int(1)
bool(true)
array(10) {
  ["id"]=>
  string(13) "personalalarm"
  ["user"]=>
  string(4) "john"
  ["start"]=>
  object(horde_date)(7) {
    ["year"]=>
    int(%d%d%d%d)
    ["month"]=>
    int(%d)
    ["mday"]=>
    int(%d)
    ["hour"]=>
    int(%d)
    ["min"]=>
    int(%d)
    ["sec"]=>
    int(%d)
    ["_supportedSpecs"]=>
    string(21) "%CdDeHImMnRStTyYbBpxX"
  }
  ["end"]=>
  object(horde_date)(7) {
    ["year"]=>
    int(%d%d%d%d)
    ["month"]=>
    int(%d)
    ["mday"]=>
    int(%d)
    ["hour"]=>
    int(%d)
    ["min"]=>
    int(%d)
    ["sec"]=>
    int(%d)
    ["_supportedSpecs"]=>
    string(21) "%CdDeHImMnRStTyYbBpxX"
  }
  ["methods"]=>
  array(0) {
  }
  ["params"]=>
  array(0) {
  }
  ["title"]=>
  string(25) "This is a personal alarm."
  ["text"]=>
  NULL
  ["snooze"]=>
  NULL
  ["internal"]=>
  NULL
}
int(0)
int(1)
array(2) {
  [0]=>
  array(10) {
    ["id"]=>
    string(11) "publicalarm"
    ["user"]=>
    string(0) ""
    ["start"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["end"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["methods"]=>
    array(0) {
    }
    ["params"]=>
    array(0) {
    }
    ["title"]=>
    string(23) "This is a public alarm."
    ["text"]=>
    NULL
    ["snooze"]=>
    NULL
    ["internal"]=>
    NULL
  }
  [1]=>
  array(10) {
    ["id"]=>
    string(13) "personalalarm"
    ["user"]=>
    string(4) "john"
    ["start"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["end"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["methods"]=>
    array(0) {
    }
    ["params"]=>
    array(0) {
    }
    ["title"]=>
    string(18) "Changed alarm text"
    ["text"]=>
    NULL
    ["snooze"]=>
    NULL
    ["internal"]=>
    NULL
  }
}
int(1)
array(1) {
  [0]=>
  array(10) {
    ["id"]=>
    string(13) "personalalarm"
    ["user"]=>
    string(4) "john"
    ["start"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["end"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["methods"]=>
    array(0) {
    }
    ["params"]=>
    array(0) {
    }
    ["title"]=>
    string(18) "Changed alarm text"
    ["text"]=>
    NULL
    ["snooze"]=>
    NULL
    ["internal"]=>
    NULL
  }
}
string(15) "Alarm not found"
int(1)
bool(true)
array(0) {
}
array(1) {
  [0]=>
  array(10) {
    ["id"]=>
    string(13) "personalalarm"
    ["user"]=>
    string(4) "john"
    ["start"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["end"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["methods"]=>
    array(0) {
    }
    ["params"]=>
    array(0) {
    }
    ["title"]=>
    string(18) "Changed alarm text"
    ["text"]=>
    NULL
    ["snooze"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["internal"]=>
    NULL
  }
}
int(1)
array(2) {
  [0]=>
  array(10) {
    ["id"]=>
    string(5) "noend"
    ["user"]=>
    string(4) "john"
    ["start"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["end"]=>
    NULL
    ["methods"]=>
    array(1) {
      [0]=>
      string(6) "notify"
    }
    ["params"]=>
    array(0) {
    }
    ["title"]=>
    string(29) "This is an alarm without end."
    ["text"]=>
    NULL
    ["snooze"]=>
    NULL
    ["internal"]=>
    NULL
  }
  [1]=>
  array(10) {
    ["id"]=>
    string(13) "personalalarm"
    ["user"]=>
    string(4) "john"
    ["start"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["end"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["methods"]=>
    array(0) {
    }
    ["params"]=>
    array(0) {
    }
    ["title"]=>
    string(18) "Changed alarm text"
    ["text"]=>
    NULL
    ["snooze"]=>
    object(horde_date)(7) {
      ["year"]=>
      int(%d%d%d%d)
      ["month"]=>
      int(%d)
      ["mday"]=>
      int(%d)
      ["hour"]=>
      int(%d)
      ["min"]=>
      int(%d)
      ["sec"]=>
      int(%d)
      ["_supportedSpecs"]=>
      string(21) "%CdDeHImMnRStTyYbBpxX"
    }
    ["internal"]=>
    NULL
  }
}
int(1)
int(1)
