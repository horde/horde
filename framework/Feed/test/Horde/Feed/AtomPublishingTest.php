<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Horde_Feed_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

class Horde_Feed_AtomPublishingTest extends PHPUnit_Framework_TestCase {

    private $uri;

    public function setUp()
    {
        $this->uri = 'http://example.com/Feed';
    }

    public function testPost()
    {
        $mock = new Horde_Http_Request_Mock();
        $mock->setResponse(new Horde_Http_Response_Mock('', fopen(dirname(__FILE__) . '/fixtures/AtomPublishingTest-created-entry.xml', 'r'), array('HTTP/1.1 201')));
        $httpClient = new Horde_Http_Client(array('request' => $mock));

        $entry = new Horde_Feed_Entry_Atom(null, $httpClient);

        // Give the entry its initial values.
        $entry->title = 'Entry 1';
        $entry->content = '1.1';
        $entry->content['type'] = 'text';

        // Do the initial post. The base feed URI is the same as the
        // POST URI, so just supply save() with that.
        $entry->save($this->uri);

        // $entry will be filled in with any elements returned by the
        // server (id, updated, link rel="edit", etc).
        $this->assertEquals('1', $entry->id(), 'Expected id to be 1');
        $this->assertEquals('Entry 1', $entry->title(), 'Expected title to be "Entry 1"');
        $this->assertEquals('1.1', $entry->content(), 'Expected content to be "1.1"');
        $this->assertEquals('text', $entry->content['type'], 'Expected content/type to be "text"');
        $this->assertEquals('2005-05-23T16:26:00-08:00', $entry->updated(), 'Expected updated date of 2005-05-23T16:26:00-08:00');
        $this->assertEquals('http://example.com/Feed/1/1/', $entry->link('edit'), 'Expected edit URI of http://example.com/Feed/1/1/');
    }

    public function testEdit()
    {
        $mock = new Horde_Http_Request_Mock();
        $mock->setResponse(new Horde_Http_Response_Mock('', fopen(dirname(__FILE__) . '/fixtures/AtomPublishingTest-updated-entry.xml', 'r'), array('HTTP/1.1 200')));
        $httpClient = new Horde_Http_Client(array('request' => $mock));

        // The base feed URI is the same as the POST URI, so just supply the
        // Horde_Feed_Entry_Atom object with that.
        $contents = file_get_contents(dirname(__FILE__) .  '/fixtures/AtomPublishingTest-before-update.xml');
        $entry = new Horde_Feed_Entry_Atom($contents, $httpClient);

        // Initial state.
        $this->assertEquals('2005-05-23T16:26:00-08:00', $entry->updated(), 'Initial state of updated timestamp does not match');
        $this->assertEquals('http://example.com/Feed/1/1/', $entry->link('edit'), 'Initial state of edit link does not match');

        // Just change the entry's properties directly.
        $entry->content = '1.2';

        // Then save the changes.
        $entry->save();

        // New state.
        $this->assertEquals('1.2', $entry->content(), 'Content change did not stick');
        $this->assertEquals('2005-05-23T16:27:00-08:00', $entry->updated(), 'New updated link is not correct');
        $this->assertEquals('http://example.com/Feed/1/2/', $entry->link('edit'), 'New edit link is not correct');
    }

}
