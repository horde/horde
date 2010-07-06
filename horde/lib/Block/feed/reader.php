<?php
/**
 * PHP 5 specific code for Horde_Block_Horde_feed segregated in a file
 * that isn't included by default to avoid fatal errors on PHP 4.
 *
 * @package Horde_Block
 */
class Horde_Block_Horde_feed_reader {

    public static function read($uri, $interval)
    {
        require_once 'Horde/Autoloader.php';

        $key = md5($uri);

        $GLOBALS['cache'] = $GLOBALS['injector']->getInstance('Horde_Cache');

        $feed = $GLOBALS['cache']->get($key, $interval);
        if (!empty($feed)) {
            return unserialize($feed);
        }

        try {
            $client = $GLOBALS['injector']
              ->getInstance('Horde_Http_Client')
              ->getClient();
            $feed = Horde_Feed::readUri($uri, $client);
            $GLOBALS['cache']->set($key, serialize($feed));
            return $feed;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
