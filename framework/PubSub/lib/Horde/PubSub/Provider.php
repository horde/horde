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
 * Publish-Subscribe provider
 *
 * Use Horde_PubSub_Provider when you want to create a per-instance plugin
 * system for your objects.
 *
 * @category  Horde
 * @package   PubSub
 */
class Horde_PubSub_Provider
{
    /**
     * Subscribed topics and their handles
     */
    protected $_topics = array();

    /**
     * Publish to all handlers for a given topic
     *
     * @param  string $topic
     * @param  mixed $args All arguments besides the topic are passed as arguments to the handler
     * @return void
     */
    public function publish($topic, $args = null)
    {
        if (empty($this->_topics[$topic])) {
            return;
        }
        $args = func_get_args();
        array_shift($args);
        foreach ($this->_topics[$topic] as $handle) {
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
    public function subscribe($topic, $context, $handler = null)
    {
        if (empty($this->_topics[$topic])) {
            $this->_topics[$topic] = array();
        }
        $handle = new Horde_PubSub_Handle($topic, $context, $handler);
        if (in_array($handle, $this->_topics[$topic])) {
            $index = array_search($handle, $this->_topics[$topic]);
            return $this->_topics[$topic][$index];
        }
        $this->_topics[$topic][] = $handle;
        return $handle;
    }

    /**
     * Unsubscribe a handler from a topic
     *
     * @param  Horde_PubSub_Handle $handle
     * @return bool Returns true if topic and handle found, and unsubscribed; returns false if either topic or handle not found
     */
    public function unsubscribe(Horde_PubSub_Handle $handle)
    {
        $topic = $handle->getTopic();
        if (empty($this->_topics[$topic])) {
            return false;
        }
        if (false === ($index = array_search($handle, $this->_topics[$topic]))) {
            return false;
        }
        unset($this->_topics[$topic][$index]);
        return true;
    }

    /**
     * Retrieve all registered topics
     *
     * @return array
     */
    public function getTopics()
    {
        return array_keys($this->_topics);
    }

    /**
     * Retrieve all handlers for a given topic
     *
     * @param  string $topic
     * @return array Array of Horde_PubSub_Handle objects
     */
    public function getSubscribedHandles($topic)
    {
        if (empty($this->_topics[$topic])) {
            return array();
        }
        return $this->_topics[$topic];
    }

    /**
     * Clear all handlers for a given topic
     *
     * @param  string $topic
     * @return void
     */
    public function clearHandles($topic)
    {
        if (!empty($this->_topics[$topic])) {
            unset($this->_topics[$topic]);
        }
    }
}
