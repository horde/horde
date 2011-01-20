<?php
/**
 * Script calculate user popularity. Please modify it for your needs.
 *
 * $Id: popularity.php 1009 2008-10-24 09:30:41Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

// Disabled by default
exit;

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('folks', array('cli' => true));

try {
    $db = $injector->getInstance('Horde_Core_Factory_DbPear')->create();
} catch (Horde_Exception $e) {
    $cli->fatal($e);
}

$users = array();
$total = 0; // total points
$totalnum = 0; // total messages

// Count messages recivered
$sql = 'SELECT COUNT(*), user_to FROM letter_inbox WHERE id >=' . (strtotime('-1 month') * 100000000) . ' GROUP BY user_to';
$result = $db->query($sql);
if ($result instanceof PEAR_Error) {
    $cli->fatal($result);
}

while ($row = $result->fetchRow()) {
    $users[$row[1]] = ($row[0] * 0.5);
    $total += ($row[0] * 0.5);
}

// count comments received
$sql = 'SELECT message_count, forum_name FROM agora_forums_folks WHERE message_count > 0 AND message_count < 1000000';
$result = $db->query($sql);
if ($result instanceof PEAR_Error) {
    $cli->fatal($result);
}

while ($row = $result->fetchRow()) {
    $users[$row[1]] = ($row[0] * 0.5);
    $total += ($row[0] * 0.5);
}

// Count user activiy in various app
$apps = array(
'news' => array('query' => 'SELECT DISTINCT user, id FROM news WHERE publish >= (NOW() - INTERVAL 3 MONTH) AND status = 1',
                  'modify' => 2),
'thomas' => array('query' => 'SELECT DISTINCT user_uid, id FROM thomas_blogs WHERE created >= (NOW() - INTERVAL 3 MONTH)',
                  'modify' => 3),
'albums' => array('query' => 'SELECT DISTINCT share_owner, share_id FROM ansel_shares WHERE attribute_date_created >= UNIX_TIMESTAMP(NOW() - INTERVAL 3 MONTH)',
                  'modify' => 3)
);

foreach ($apps as $app => $defs) {
    $result = $db->query($defs['query']);
    if ($result instanceof PEAR_Error) {
        $cli->fatal($result);
    }

    while ($row = $result->fetchRow()) {
        $sql = 'SELECT COUNT(*) FROM agora_forums_' .  $app . ', agora_messages_' .  $app . ' WHERE forum_name = ? AND forum_id = msg.forum_id';
        $row2 = $db->getRow($sql, array($row[0]));
        if ($row2 instanceof PEAR_Error) {
            $cli->fatal($row2);
        }

        @$users[$row[1]] += $row2[0] * $defs['modify'];
        $total += $row2[0] * $defs['modify'];
    }
}

// calclulate users popolarity
reset($users);
$maxp = 0;
while (list($u,$v) = each($users)) {
    if (!empty($u) && !empty($v)) {
        continue;
    }
    if ($v>$maxp) {
        $maxp = $v;
    }
}

// update users popularity... 100% = $maxp
$result = $db->query('UPDATE folks_users SET popularity = 0');
if ($result instanceof PEAR_Error) {
    $cli->fatal($result);
}

reset($users);
while ( list($u,$v) = each($users) ) {
    if (!empty($u) && !empty($v)) {
        continue;
    }

    $p = ceil($v/$maxp*100);

    $result = $db->query('UPDATE folks_users SET popularity = ? WHERE user_uid = ?' , array($u, $p));
    if ($result instanceof PEAR_Error) {
        $cli->fatal($result);
    }

    $cli->message($u . ' ' . $p, 'cli.success');
}
