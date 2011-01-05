<?php
/**
 * Publish-Subscribe system based on Phly_PubSub
 * (http://weierophinney.net/matthew/archives/199-A-Simple-PHP-Publish-Subscribe-System.html)
 *
 * @category  Horde
 * @package   PubSub
 * @copyright Copyright (C) 2008 - Present, Matthew Weier O'Phinney
 * @author    Matthew Weier O'Phinney <mweierophinney@gmail.com>
 * @license   New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 */

/**
 * Publish-Subscribe system
 *
 * @category  Horde
 * @package   PubSub
 */
class Horde_PubSub
{
    /**
     * Subscribed topics and their handles
     */
    protected static $_topics = array();

    /**
     * Publish to all handlers for a given topic
     *
     * @param  string $topic
     * @param  mixed $args All arguments besides the topic are passed as arguments to the handler
     * @return void
     */
    public static function publish($topic, $args = null)
    {
        if (empty(self::$_topics[$topic])) {
            return;
        }
        $args = func_get_args();
        array_shift($args);
        foreach (self::$_topics[$topic] as $handle) {
            $handle->call($args);
        }
    }

    /**
     * Subscribe to a topic
     *
     * @param  string $topic
     * @param  string|object $context Function name, class name, or object instance
     * @param  null|string $handler If $context is a class or object, the name of the method to call
     * @return Horde_PubSub_Handle Pub-Sub handle (to allow later unsubscribe)
     */
    public static function subscribe($topic, $context, $handler = null)
    {
        if (empty(self::$_topics[$topic])) {
            self::$_topics[$topic] = array();
        }
        $handle = new Horde_PubSub_Handle($topic, $context, $handler);
        if (in_array($handle, self::$_topics[$topic])) {
            $index = array_search($handle, self::$_topics[$topic]);
            return self::$_topics[$topic][$index];
        }
        self::$_topics[$topic][] = $handle;
        return $handle;
    }

    /**
     * Unsubscribe a handler from a topic
     *
     * @param  Horde_PubSub_Handle $handle
     * @return bool Returns true if topic and handle found, and unsubscribed; returns false if either topic or handle not found
     */
    public static function unsubscribe(Horde_PubSub_Handle $handle)
    {
        $topic = $handle->getTopic();
        if (empty(self::$_topics[$topic])) {
            return false;
        }
        if (false === ($index = array_search($handle, self::$_topics[$topic]))) {
            return false;
        }
        unset(self::$_topics[$topic][$index]);
        return true;
    }

    /**
     * Retrieve all registered topics
     *
     * @return array
     */
    public static function getTopics()
    {
        return array_keys(self::$_topics);
    }

    /**
     * Retrieve all handlers for a given topic
     *
     * @param  string $topic
     * @return array Array of Horde_PubSub_Handle objects
     */
    public static function getSubscribedHandles($topic)
    {
        if (empty(self::$_topics[$topic])) {
            return array();
        }
        return self::$_topics[$topic];
    }

    /**
     * Clear all handlers for a given topic
     *
     * @param  string $topic
     * @return void
     */
    public static function clearHandles($topic)
    {
        if (!empty(self::$_topics[$topic])) {
            unset(self::$_topics[$topic]);
        }
    }
}
