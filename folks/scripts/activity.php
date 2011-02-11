<?php
/**
 * Script calculate user acrivity. Please modify it for your needs.
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

// Count user activiy in various app
$apps = array(
'letter' => array('query' => 'SELECT COUNT(*), user_from FROM letter_inbox WHERE letter_inbox.id >= ' . strtotime('-3 month') * 100000000 . ' GROUP BY letter_inbox.user_from',
                  'modify' => 0.3),
'agora' => array('query' => 'SELECT COUNT(*), msg.message_author FROM agora_forums AS idx, agora_messages AS msg WHERE idx.forum_id = msg.forum_id AND msg.message_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 1 MONTH) GROUP BY message_author ORDER BY counter DESC',
                  'modify' => 2),
'news' => array('query' => 'SELECT COUNT(*), news.user FROM news WHERE news.publish >= (NOW() - INTERVAL 3 MONTH) AND news.status = 1 GROUP BY news.user',
                  'modify' => 18),
'thomas' => array('query' => 'SELECT COUNT(*), thomas_blogs.user_uid FROM thomas_blogs WHERE thomas_blogs.created >= (NOW() - INTERVAL 3 MONTH) AND thomas_blogs.status = 1 GROUP BY thomas_blogs.user_uid',
                  'modify' => 10),
'classifieds' => array('query' => 'SELECT COUNT(*), classified_ads.user_uid FROM classified_ads WHERE classified_ads.ad_validto <= UNIX_TIMESTAMP() GROUP BY classified_ads.user_uid',
                  'modify' => 5),
'ansel' => array('query' => 'SELECT COUNT(*) AS counter, ansel_shares.share_owner FROM ansel_shares WHERE ansel_shares.attribute_date_created >= UNIX_TIMESTAMP(NOW() - INTERVAL 3 MONTH) GROUP BY ansel_shares.share_owner ORDER BY counter DESC',
                  'modify' => 5),
);

// Get application activities
foreach ($apps as $app) {

    // Try query
    $result = $db->query($app['query']);
    if ($result instanceof PEAR_Error) {
        $cli->fatal($result);
    }

    // Add total counts
    $totalnum += $result->numRows();

    // Process query
    while ($row = $result->fetchRow()) {
        $users[$row[1]] = $row[0] * $app['modify'];
        $total += $row[0]  * $app['modify'];
    }
}

// Get comments activires
$comments = array('news' => 5,
                'thomas' => 2,
                'schedul' => 2,
                'oscar' => 1,
                'ansel' => 1,
                'folks' => 1,
                'genie' => 1);

foreach ($comments as $comment_app => $comment_factor) {

    $sql = 'SELECT COUNT(*), msg.message_author '
            . ' FROM agora_forums_' . $comment_app. ' AS idx, agora_messages_' . $comment_app. ' AS msg  '
            . ' WHERE idx.forum_id = msg.forum_id  '
            . ' AND msg.message_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 1 MONTH)  '
            . ' GROUP BY message_author  '
            . ' ORDER BY counter DESC';

    // Try query
    $result = $db->query($sql);
    if ($query instanceof PEAR_Error) {
        $cli->fatal($query);
    }

    // Add total counts
    $totalnum += $result->numRows();

    while ($row = $result->fetchRow()) {
        $users[$row[1]] = ($row[0] * $comment_factor);
        $total += ($row[0] * $comment_factor);
    }

}

// find max user activity
reset($users);
$maxp = 0;
while (list($u,$v) = each($users)) {
    if (!empty($u) && !empty($v)) {
        if ($v>$maxp) {
            $maxp = $v;
        }
    }
}

// update users popularity... 100% = $maxp
$result = $db->query('UPDATE folks_users SET activity = 0');
if ($result instanceof PEAR_Error) {
    $cli->fatal($result);
}

reset($users);
while (list($u,$v) = each($users)) {

    if (empty($u) && empty($v)) {
        continue;
    }

    $p = ceil($v / $maxp * 100);

    $result = $db->query('UPDATE folks_users SET activity = ? WHERE user_uid = ?' , array($u, $p));
    if ($result instanceof PEAR_Error) {
        $cli->fatal($result);
    }

    $cli->message($u . ' ' . $p, 'cli.success');
}
