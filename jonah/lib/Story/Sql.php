<?php
/**
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Luc Saillard <luc.saillard@fr.alcove.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Jonah
 */
class Jonah_Story_Sql extends Jonah_Story
{

    /**
     * Const'r
     *
     * @param Jonah_Driver $driver  The backend driver that this story is
     *                              stored in.
     * @param mixed $storyObject    Backend specific story object
     *                              that this will represent.
     */
    public function __construct($driver, $storyObject = null)
    {

        parent::__construct($driver, $storyObject);
    }

    /**
     * Imports a backend specific story object.
     *
     * @param array $story  Backend specific event object that this object
     *                      will represent.
     */
    public function fromDriver($SQLStory)
    {
        $driver = $this->getDriver();

        $this->title = $driver->convertFromDriver($SQLStory['story_title']);
        $this->id = $SQLStory['story_id'];
        $this->feed = $SQLStory['channel_id'];
        $this->author = $SQLStory['story_author'];

        if (isset($SQLStory['story_desc'])) {
            $this->description = $driver->convertFromDriver($SQLStory['story_desc']);
        }

        $this->bodyType = (int)$SQLStory['story_body_type'];

        if (isset($SQLStory['story_body'])) {
            $this->body = (string)$SQLStory['story_body'];
        }
        if (isset($SQLStory['story_url'])) {
            $this->url = $driver->convertFromDriver($SQLStory['story_url']);
        }
        if (isset($SQLStory['story_permalink'])) {
            $this->private = $driver->convertFromDriver($SQLStory['story_private']);
        }
        if (isset($SQLStory['story_published'])) {
            $this->published = (int)$SQLStory['story_published'];
        }
        $this->updated = (int)$SQLStory['story_updated'];
        $this->read = (int)$SQLStory['story_read'];

        $this->initialized = true;
        $this->stored = true;
    }

    /**
     * Prepares this story to be saved to the backend.
     */
    public function toProperties()
    {
        $driver = $this->getDriver();
        $properties = array();

        /* Basic fields. */
        $properties['channel_id'] = (int)$this->feed;
        $properties['story_author'] = $driver->convertToDriver($this->author);
        $properties['story_title'] = $driver->convertToDriver($this->title);
        $properties['story_desc'] = $driver->convertToDriver($this->description);
        $properties['story_body_type'] = (int)$this->bodyType;
        $properties['story_body'] = (string)$this->body;
        $properties['story_url'] = (string)$this->url;
        $properties['story_permalink'] = $driver->convertToDriver($this->permalink);
        $properties['story_published'] = (int)$this->published;
        $properties['story_updated'] = (int)$this->updated;
        $properties['story_read'] = (int)$this->read;;

        return $properties;
    }

}
