<?php
/**
 * Script to update user online status site wide
 * Using in combination with online.sql is recomended
 *
 * Put some ting like this in your cron
 *
 * mysql -u USER -h localhost --password=P DB < /pato/to/folks/scripts/online.sql
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

if (!isset($_COOKIE['online'])) {

    // Add this to your tempalte file
    $sql = 'REPLACE INTO folks_online SET user_uid="' . $GLOBALS['registry']->getAuth() . '", ip_address="' . $_SERVER["REMOTE_ADDR"] . '", time_last_click="' . $_SERVER['REQUEST_TIME'] . '"';
    mysql_unbuffered_query($sql);

    // If not using cront with online.sql
    // $sql = 'UPDATE folks_users SET last_online_on = "' . $_SERVER['REQUEST_TIME'] .'" WHERE user_uid = "' . $GLOBALS['registry']->getAuth() . '"';
    // mysql_unbuffered_query($sql)

    // If not using cront with online.sql
    // $sql = 'DELETE FROM folks_online WHERE time_last_click < UNIX_TIMESTAMP() - 480';
    // mysql_unbuffered_query($sql)

    if (!headers_sent()) {
        setcookie('online', 1, $_SERVER['REQUEST_TIME'] + 480, '/');
    }
}
