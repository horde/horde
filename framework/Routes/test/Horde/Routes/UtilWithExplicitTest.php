<?php
/**
 * Horde Routes package
 *
 * This package is heavily inspired by the Python "Routes" library
 * by Ben Bangert (http://routes.groovie.org).  Routes is based
 * largely on ideas from Ruby on Rails (http://www.rubyonrails.org).
 *
 * @author  Maintainable Software, LLC. (http://www.maintainable.com)
 * @author  Mike Naberezny <mike@maintainable.com>
 * @license http://www.horde.org/licenses/bsd BSD
 * @package Routes
 */

require_once dirname(__FILE__) . '/TestHelper.php';

/**
 * @package Routes
 */
class Horde_Routes_UtilWithExplicitTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $m = new Horde_Routes_Mapper(array('explicit' => true));
        $m->connect('archive/:year/:month/:day',
            array('controller' => 'blog',
                  'action' => 'view',
                  'month' => null,
                  'day' => null,
                  'requirements' => array('month' => '\d{1,2}', 'day' => '\d{1,2}')));
        $m->connect('viewpost/:id', array('controller' => 'post', 'action' => 'view', 'id' => null));
        $m->connect(':controller/:action/:id');
        $m->environ = array('SERVER_NAME' => 'www.test.com');
        $this->mapper = $m;
        $this->utils = $m->utils;
    }

    public function testUrlFor()
    {
        $utils = $this->utils;
        $utils->mapperDict = array();

        $this->assertNull($utils->urlFor(array('controller' => 'blog')));
        $this->assertNull($utils->urlFor());
        $this->assertEquals('/blog/view/3',
                            $utils->urlFor(array('controller' => 'blog', 'action' => 'view',
                                                'id' => 3)));
        $this->assertEquals('https://www.test.com/viewpost',
                            $utils->urlFor(array('controller' => 'post', 'action' => 'view',
                                                'protocol' => 'https')));
        $this->assertEquals('http://www.test.org/content/view/2',
                            $utils->urlFor(array('host' => 'www.test.org', 'controller' => 'content',
                                                'action' => 'view', 'id' => 2)));

        $m = $this->mapper;

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect('home', '', array('controller' => 'blog', 'action' => 'splash'));
        $m->connect('category_home', 'category/:section',
                    array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertEquals('/content/splash/2',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'splash',
                                                 'id' => 2)));
    }

    public function testUrlForWithDefaults()
    {
        $utils = $this->utils;
        $utils->mapperDict = array('controller' => 'blog', 'action' => 'view', 'id' => 4);

        $this->assertNull($utils->urlFor());
        $this->assertNull($utils->urlFor(array('controller' => 'post')));
        $this->assertNull($utils->urlFor(array('id' => 2)));
        $this->assertEquals('/viewpost/4',
                            $utils->urlFor(array('controller' => 'post', 'action' => 'view',
                                                'id' => 4)));

        $utils->mapperDict = array('controller' => 'blog', 'action' => 'view', 'year' => 2004);
        $this->assertNull($utils->urlFor(array('month' => 10)));
        $this->assertNull($utils->urlFor(array('month' => 9, 'day' => 2)));
        $this->assertNull($utils->urlFor(array('controller' => 'blog', 'year' => null)));
    }

    public function testUrlForWithMoreDefaults()
    {
        $utils = $this->utils;
        $utils->mapperDict = array('controller' => 'blog', 'action' => 'view', 'id' => 4);

        $this->assertNull($utils->urlFor());
        $this->assertNull($utils->urlFor(array('controller' => 'post')));
        $this->assertNull($utils->urlFor(array('id' => 2)));
        $this->assertEquals('/viewpost/4',
                            $utils->urlFor(array('controller' => 'post', 'action' => 'view',
                                                'id' => 4)));

        $utils->mapperDict = array('controller' => 'blog', 'action' => 'view', 'year' => 2004);
        $this->assertNull($utils->urlFor(array('month' => 10)));
        $this->assertNull($utils->urlFor());
    }

    public function testUrlForWithDefaultsAndQualified()
    {
        $utils = $this->utils;

        $m = $this->mapper;
        $m->connect('home', '', array('controller' => 'blog', 'action' => 'splash'));
        $m->connect('category_home', 'category/:section',
                    array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $environ = array('SCRIPT_NAME' => '', 'SERVER_NAME' => 'www.example.com',
                         'SERVER_PORT' => '80', 'PATH_INFO' => '/blog/view/4');
        Horde_Routes_TestHelper::updateMapper($m, $environ);

        $this->assertNull($utils->urlFor());
        $this->assertNull($utils->urlFor(array('controller' => 'post')));
        $this->assertNull($utils->urlFor(array('id' => 2)));
        $this->assertNull($utils->urlFor(array('qualified' => true, 'controller' => 'blog', 'id' => 4)));
        $this->assertEquals('http://www.example.com/blog/view/4',
                            $utils->urlFor(array('qualified' => true, 'controller' => 'blog',
                                                'action' => 'view', 'id' => 4)));
        $this->assertEquals('/viewpost/4',
                            $utils->urlFor(array('controller' => 'post', 'action' => 'view', 'id' => 4)));

        $environ = array('SCRIPT_NAME' => '', 'HTTP_HOST' => 'www.example.com:8080', 'PATH_INFO' => '/blog/view/4');
        Horde_Routes_TestHelper::updateMapper($m, $environ);

        $this->assertNull($utils->urlFor(array('controller' => 'post')));
        $this->assertEquals('http://www.example.com:8080/blog/view/4',
                            $utils->urlFor(array('qualified' => true, 'controller' => 'blog',
                                                'action' => 'view', 'id' => 4)));
    }

    public function testWithRouteNames()
    {
        $m = $this->mapper;

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect('home', '', array('controller' => 'blog', 'action' => 'splash'));
        $m->connect('category_home', 'category/:section',
                    array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertNull($utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertNull($utils->urlFor(array('controller' => 'content')));
        $this->assertNull($utils->urlFor(array('controller' => 'admin/comments')));
        $this->assertEquals('/category',
                            $utils->urlFor('category_home'));
        $this->assertEquals('/category/food',
                            $utils->urlFor('category_home', array('section' => 'food')));
        $this->assertNull($utils->urlFor('home', array('action' => 'view', 'section' => 'home')));
        $this->assertNull($utils->urlFor('home', array('controller' => 'content')));
        $this->assertEquals('/', $utils->urlFor('home'));
    }

    public function testWithRouteNamesAndDefaults()
    {
        $m = $this->mapper;

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect('home', '', array('controller' => 'blog', 'action' => 'splash'));
        $m->connect('category_home', 'category/:section',
                    array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->connect('building', 'building/:campus/:building/alljacks',
                    array('controller' => 'building', 'action' => 'showjacks'));
        $m->createRegs(array('content', 'blog', 'admin/comments', 'building'));

        $utils->mapperDict = array('controller' => 'building', 'action' => 'showjacks',
                                  'campus' => 'wilma', 'building' => 'port');

        $this->assertNull($utils->urlFor());
        $this->assertEquals('/building/wilma/port/alljacks',
                            $utils->urlFor(array('controller' => 'building', 'action' => 'showjacks',
                                                'campus' => 'wilma', 'building' => 'port')));
        $this->assertEquals('/', $utils->urlFor('home'));
    }

    public function testWithResourceRouteNames()
    {
        $m = new Horde_Routes_Mapper();
        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->resource('message', 'messages',
                     array('member'     => array('mark' => 'GET'),
                           'collection' => array('rss' => 'GET')));
        $m->createRegs(array('messages'));

        $this->assertNull($utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertNull($utils->urlFor(array('controller' => 'content')));
        $this->assertNull($utils->urlFor(array('controller' => 'admin/comments')));
        $this->assertEquals('/messages',
                            $utils->urlFor('messages'));
        $this->assertEquals('/messages/rss',
                            $utils->urlFor('rss_messages'));
        $this->assertEquals('/messages/4',
                            $utils->urlFor('message', array('id' => 4)));
        $this->assertEquals('/messages/4/edit',
                            $utils->urlFor('edit_message', array('id' => 4)));
        $this->assertEquals('/messages/4/mark',
                            $utils->urlFor('mark_message', array('id' => 4)));
        $this->assertEquals('/messages/new',
                            $utils->urlFor('new_message'));
        $this->assertEquals('/messages.xml',
                            $utils->urlFor('formatted_messages', array('format' => 'xml')));
        $this->assertEquals('/messages/rss.xml',
                            $utils->urlFor('formatted_rss_messages', array('format' => 'xml')));
        $this->assertEquals('/messages/4.xml',
                            $utils->urlFor('formatted_message', array('id' => 4, 'format' => 'xml')));
        $this->assertEquals('/messages/4/edit.xml',
                            $utils->urlFor('formatted_edit_message', array('id' => 4, 'format' => 'xml')));
        $this->assertEquals('/messages/4/mark.xml',
                            $utils->urlFor('formatted_mark_message', array('id' => 4, 'format' => 'xml')));
        $this->assertEquals('/messages/new.xml',
                            $utils->urlFor('formatted_new_message', array('format' => 'xml')));
    }

}
