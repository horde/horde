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
 * Publish-Subscribe handler: unique handle subscribed to a given topic.
 *
 * @category  Horde
 * @package   PubSub
 */
class Horde_PubSub_Handle
{
    /**
     * PHP callback to invoke
     * @var string|array
     */
    protected $_callback;

    /**
     * Topic to which this handle is subscribed
     * @var string
     */
    protected $_topic;

    /**
     * Constructor
     *
     * @param  string $topic Topic to which handle is subscribed
     * @param  string|object $context Function name, class name, or object instance
     * @param  string|null $handler Method name, if $context is a class or object
     */
    public function __construct($topic, $context, $handler = null)
    {
        $this->_topic = $topic;

        if (null === $handler) {
            $this->_callback = $context;
        } else {
            $this->_callback = array($context, $handler);
        }
    }

    /**
     * Get topic to which handle is subscribed
     *
     * @return string
     */
    public function getTopic()
    {
        return $this->_topic;
    }

    /**
     * Retrieve registered callback
     *
     * @return string|array
     */
    public function getCallback()
    {
        return $this->_callback;
    }

    /**
     * Invoke handler
     *
     * @param  array $args Arguments to pass to callback
     * @return void
     */
    public function call(array $args)
    {
        call_user_func_array($this->getCallback(), $args);
    }
}
