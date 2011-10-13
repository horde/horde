<?php
/**
 * Example of posting a new Atom entry with Horde_Feed.
 *
 * @package Feed
 */

/* Get a Horde framework include_path set up. */
require 'Horde/Autoloader.php';

/* The base feed URI is the same as the POST URI, so just supply the
 * Horde_Feed_Entry_Atom object with that. */
$entry = new Horde_Feed_Entry_Atom('http://example.com/myFeed');

/* Give the entry its initial values. */
$entry->title = 'Entry 1';
$entry->content = '1.1';
$entry->content['type'] = 'text';

/* Do the initial post. */
try {
    $entry->save();
} catch (Horde_Feed_Exception $e) {
    die('An error occurred posting: ' . $e->getMessage() . "\n");
}

/* $entry will be filled in with any elements returned by the
 * server (id, updated, link rel="edit", etc). */
echo "new id is: {$entry->id()}\n";
echo "entry last updated at: {$entry->updated()}\n";
echo "edit the entry at: {$entry->edit()}\n";


/* Using namespaces: create an entry with myns:updated using the base
 * Horde_Feed_Entry_Atom class. */
$myfeeduri = 'http://www.example.com/nsfeed/';
$entry = new Horde_Feed_Entry_Atom($nsfeeduri);
Horde_Xml_Element::registerNamespace('myns', 'http://www.example.com/myns/');
$entry->{'myns:updated'} = '2005-04-19T15:30';
$entry->save();


/* Using namespaces, but with a custom Entry class: */

/**
 * The custom feed class ensures that when you access this feed, the objects
 * returned are MyEntry objects.
 */
class MyFeed extends Horde_Feed_Atom {

    protected $_entryClassName = 'MyEntry';

}

/**
 * The custom entry class automatically knows the feed URI (optional) and
 * can automatically add extra namespaces.
 */
class MyEntry extends Horde_Feed_Entry_Atom {

    public function __construct($uri = 'http://www.example.com/myfeed/',
                                $xml = null)
    {
        parent::__construct($uri, $xml);

        Horde_Xml_Element::registerNamespace('myns', 'http://www.example.com/myns/');
    }

    public function __get($var)
    {
        switch ($var) {
        case 'myUpdated':
            // Translate myUpdated to myns:updated.
            return parent::__get('myns:updated');
        }

        return parent::__get($var);
    }

    public function __set($var, $value)
    {
        switch ($var) {
        case 'myUpdated':
            // Translate myUpdated to myns:updated.
            return parent::__set('myns:updated', $value);
        }

        return parent::__set($var, $value);
    }

}

// Now we just need to create the class and set myUpdated.
$entry = new MyEntry();
$entry->myUpdated = '2005-04-19T15:30';
$entry->save();
