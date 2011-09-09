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

/**
 * @package Routes
 */
class Horde_Routes_GenerationTest extends PHPUnit_Framework_TestCase
{

    public function testAllStaticNoReqs()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('hello/world');

        $this->assertEquals('/hello/world', $m->generate());
    }

    public function testBasicDynamic()
    {
        foreach (array('hi/:fred', 'hi/:(fred)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);

            $this->assertEquals('/hi/index', $m->generate(array('fred' => 'index')));
            $this->assertEquals('/hi/show',  $m->generate(array('fred' => 'show')));
            $this->assertEquals('/hi/list+people', $m->generate(array('fred' => 'list people')));
            $this->assertNull($m->generate());
        }
    }

    public function testDynamicWithDefault()
    {
        foreach (array('hi/:action', 'hi/:(action)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);

            $this->assertEquals('/hi',      $m->generate(array('action' => 'index')));
            $this->assertEquals('/hi/show', $m->generate(array('action' => 'show')));
            $this->assertEquals('/hi/list+people', $m->generate(array('action' => 'list people')));
            $this->assertEquals('/hi', $m->generate());
        }
    }

    /**
     * Some of these assertions are invalidated in PHP, it passes in Python because
     * unicode(None) == unicode('None').  In PHP, we don't have a function similar
     * to unicode().  These have the comment "unicode false equiv"
     */
    public function testDynamicWithFalseEquivs()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('article/:page', array('page' => false));
        $m->connect(':controller/:action/:id');

        $this->assertEquals('/blog/view/0',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => '0')));

        // unicode false equiv
        // $this->assertEquals('/blog/view/0',
        //                     $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => 0)));
        //$this->assertEquals('/blog/view/False',
        //                     $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => false)));

        $this->assertEquals('/blog/view/False',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => 'False')));
        $this->assertEquals('/blog/view',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => null)));

        // unicode false equiv
        // $this->assertEquals('/blog/view',
        //                    $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => 'null')));

        $this->assertEquals('/article',
                            $m->generate(array('page' => null)));
    }

    public function testDynamicWithUnderscoreParts()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('article/:small_page', array('small_page' => false));
        $m->connect(':(controller)/:(action)/:(id)');

        $this->assertEquals('/blog/view/0',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => '0')));

        // unicode false equiv
        // $this->assertEquals('/blog/view/False',
        //                     $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => false)));

        // unicode False Equiv
        //$this->assertEquals('/blog/view',
        //                    $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => 'null'))); */

        $this->assertEquals('/article',
                            $m->generate(array('small_page' => null)));
        $this->assertEquals('/article/hobbes',
                            $m->generate(array('small_page' => 'hobbes')));
    }

    public function testDynamicWithFalseEquivsAndSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('article/:(page)', array('page' => false));
        $m->connect(':(controller)/:(action)/:(id)');

        $this->assertEquals('/blog/view/0',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => '0')));

        // unicode false equiv
        // $this->assertEquals('/blog/view/0',
        //                     $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => 0)));
        // $this->assertEquals('/blog/view/False',
        //                     $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => false));

        $this->assertEquals('/blog/view/False',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => 'False')));
        $this->assertEquals('/blog/view',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => null)));

        // unicode false equiv
        //$this->assertEquals('/blog/view',
        //                    $m->generate(array('controller' => 'blog', 'action' => 'view', 'id' => 'null')));

        $this->assertEquals('/article',
                            $m->generate(array('page' => null)));

        $m = new Horde_Routes_Mapper();
        $m->connect('view/:(home)/:(area)', array('home' => 'austere', 'area' => null));

        $this->assertEquals('/view/sumatra',
                            $m->generate(array('home' => 'sumatra')));
        $this->assertEquals('/view/austere/chicago',
                            $m->generate(array('area' => 'chicago')));

        $m = new Horde_Routes_Mapper();
        $m->connect('view/:(home)/:(area)', array('home' => null, 'area' => null));

        $this->assertEquals('/view/null/chicago',
                            $m->generate(array('home' => null, 'area' => 'chicago')));
    }

    public function testDynamicWithRegExpCondition()
    {
        foreach (array('hi/:name', 'hi/:(name)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('requirements' => array('name' => '[a-z]+')));

            $this->assertEquals('/hi/index', $m->generate(array('name' => 'index')));
            $this->assertNull($m->generate(array('name' => 'fox5')));
            $this->assertNull($m->generate(array('name' => 'something_is_up')));
            $this->assertEquals('/hi/abunchofcharacter',
                                $m->generate(array('name' => 'abunchofcharacter')));
            $this->assertNull($m->generate());
        }
    }

    public function testDynamicWithDefaultAndRegexpCondition()
    {
        foreach (array('hi/:action', 'hi/:(action)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('requirements' => array('action' => '[a-z]+')));

            $this->assertEquals('/hi', $m->generate(array('action' => 'index')));
            $this->assertNull($m->generate(array('action' => 'fox5')));
            $this->assertNull($m->generate(array('action' => 'something_is_up')));
            $this->assertNull($m->generate(array('action' => 'list people')));
            $this->assertEquals('/hi/abunchofcharacter',
                                $m->generate(array('action' => 'abunchofcharacter')));
            $this->assertEquals('/hi', $m->generate());
        }
    }

    public function testPath()
    {
        foreach (array('hi/*file', 'hi/*(file)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);

            $this->assertEquals('/hi',
                                $m->generate(array('file' => null)));
            $this->assertEquals('/hi/books/learning_python.pdf',
                                $m->generate(array('file' => 'books/learning_python.pdf')));
            $this->assertEquals('/hi/books/development%26whatever/learning_python.pdf',
                                $m->generate(array('file' => 'books/development&whatever/learning_python.pdf')));
        }
    }

    public function testPathBackwards()
    {
        foreach (array('*file/hi', '*(file)/hi') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);

            $this->assertEquals('/hi',
                                $m->generate(array('file' => null)));
            $this->assertEquals('/books/learning_python.pdf/hi',
                                $m->generate(array('file' => 'books/learning_python.pdf')));
            $this->assertEquals('/books/development%26whatever/learning_python.pdf/hi',
                                $m->generate(array('file' => 'books/development&whatever/learning_python.pdf')));
        }
    }

    public function testController()
    {
        foreach (array('hi/:controller', 'hi/:(controller)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);

            $this->assertEquals('/hi/content',
                                $m->generate(array('controller' => 'content')));
            $this->assertEquals('/hi/admin/user',
                                $m->generate(array('controller' => 'admin/user')));
        }
    }

    public function testControllerWithStatic()
    {
        foreach (array('hi/:controller', 'hi/:(controller)') as $path) {
            $m = new Horde_Routes_Mapper();
            $utils = $m->utils;
            $m->connect($path);
            $m->connect('google', 'http://www.google.com', array('_static' => true));

            $this->assertEquals('/hi/content',
                                $m->generate(array('controller' => 'content')));
            $this->assertEquals('/hi/admin/user',
                                $m->generate(array('controller' => 'admin/user')));
            $this->assertEquals('http://www.google.com', $utils->urlFor('google'));
        }
    }

    public function testStandardRoute()
    {
        foreach (array(':controller/:action/:id', ':(controller)/:(action)/:(id)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);

            $this->assertEquals('/content',
                                $m->generate(array('controller' => 'content', 'action' => 'index')));
            $this->assertEquals('/content/list',
                                $m->generate(array('controller' => 'content', 'action' => 'list')));
            $this->assertEquals('/content/show/10',
                                $m->generate(array('controller' => 'content', 'action' => 'show', 'id' => '10')));

            $this->assertEquals('/admin/user',
                                $m->generate(array('controller' => 'admin/user', 'action' => 'index')));
            $this->assertEquals('/admin/user/list',
                                $m->generate(array('controller' => 'admin/user', 'action' => 'list')));
            $this->assertEquals('/admin/user/show/10',
                                $m->generate(array('controller' => 'admin/user', 'action' => 'show', 'id' => '10')));
        }
    }

    public function testMultiroute()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('archive/:year/:month/:day', array('controller' => 'blog', 'action' => 'view',
                                                       'month' => null, 'day' => null,
                                                       'requirements' => array('month' => '\d{1,2}',
                                                                               'day'   => '\d{1,2}')));
        $m->connect('viewpost/:id', array('controller' => 'post', 'action' => 'view'));
        $m->connect(':controller/:action/:id');

        $this->assertEquals('/blog/view?year=2004&month=blah',
                            $m->generate(array('controller' => 'blog', 'action' => 'view',
                                               'year' => 2004, 'month' => 'blah')));
        $this->assertEquals('/archive/2004/11',
                            $m->generate(array('controller' => 'blog', 'action' => 'view',
                                               'year' => 2004, 'month' => 11)));
        $this->assertEquals('/archive/2004/11',
                            $m->generate(array('controller' => 'blog', 'action' => 'view',
                                               'year' => 2004, 'month' => '11')));
        $this->assertEquals('/archive/2004/11',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'year' => 2004, 'month' => 11)));
        $this->assertEquals('/archive/2004', $m->generate(array('controller' => 'blog', 'action' => 'view', 'year' => 2004)));
        $this->assertEquals('/viewpost/3',
                            $m->generate(array('controller' => 'post', 'action' => 'view', 'id' => 3)));
    }

    public function testMultirouteWithSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('archive/:(year)/:(month)/:(day)', array('controller' => 'blog', 'action' => 'view',
                                                             'month' => null, 'day' => null,
                                                             'requirements' => array('month' => '\d{1,2}',
                                                                                           'day'   => '\d{1,2}')));
        $m->connect('viewpost/:(id)', array('controller' => 'post', 'action' => 'view'));
        $m->connect(':(controller)/:(action)/:(id)');

        $this->assertEquals('/blog/view?year=2004&month=blah',
                            $m->generate(array('controller' => 'blog', 'action' => 'view',
                                               'year' => 2004, 'month' => 'blah')));
        $this->assertEquals('/archive/2004/11',
                            $m->generate(array('controller' => 'blog', 'action' => 'view',
                                               'year' => 2004, 'month' => 11)));
        $this->assertEquals('/archive/2004/11',
                            $m->generate(array('controller' => 'blog', 'action' => 'view',
                                               'year' => 2004, 'month' => '11')));
        $this->assertEquals('/archive/2004',
                            $m->generate(array('controller' => 'blog', 'action' => 'view', 'year' => 2004)));
        $this->assertEquals('/viewpost/3',
                            $m->generate(array('controller' => 'post', 'action' => 'view', 'id' => 3)));
    }

    public function testBigMultiroute()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('', array('controller' => 'articles', 'action' => 'index'));
        $m->connect('admin', array('controller' => 'admin/general', 'action' => 'index'));

        $m->connect('admin/comments/article/:article_id/:action/:id',
                    array('controller' => 'admin/comments', 'action' => null, 'id' => null));
        $m->connect('admin/trackback/article/:article_id/:action/:id',
                    array('controller' => 'admin/trackback', 'action' => null, 'id' => null));
        $m->connect('admin/content/:action/:id', array('controller' => 'admin/content'));

        $m->connect('xml/:action/feed.xml', array('controller' => 'xml'));
        $m->connect('xml/articlerss/:id/feed.xml', array('controller' => 'xml', 'action' => 'articlerss'));
        $m->connect('index.rdf', array('controller' => 'xml', 'action' => 'rss'));

        $m->connect('articles', array('controller' => 'articles', 'action' => 'index'));
        $m->connect('articles/page/:page',
                    array('controller' => 'articles', 'action' => 'index',
                          'requirements' => array('page' => '\d+')));
        $m->connect('articles/:year/:month/:day/page/:page',
                    array('controller' => 'articles', 'action' => 'find_by_date',
                          'month' => null, 'day' => null,
                          'requirements' => array('year' => '\d{4}', 'month' => '\d{1,2}',
                                                                     'day' => '\d{1,2}')));
        $m->connect('articles/category/:id', array('controller' => 'articles', 'action' => 'category'));
        $m->connect('pages/*name', array('controller' => 'articles', 'action' => 'view_page'));

        $this->assertEquals('/pages/the/idiot/has/spoken',
                            $m->generate(array('controller' => 'articles', 'action' => 'view_page',
                                               'name' => 'the/idiot/has/spoken')));
        $this->assertEquals('/',
                            $m->generate(array('controller' => 'articles', 'action' => 'index')));
        $this->assertEquals('/xml/articlerss/4/feed.xml',
                            $m->generate(array('controller' => 'xml', 'action' => 'articlerss', 'id' => 4)));
        $this->assertEquals('/xml/rss/feed.xml',
                            $m->generate(array('controller' => 'xml', 'action' => 'rss')));
        $this->assertEquals('/admin/comments/article/4/view/2',
                            $m->generate(array('controller' => 'admin/comments', 'action' => 'view',
                                               'article_id' => 4, 'id' => 2)));
        $this->assertEquals('/admin',
                            $m->generate(array('controller' => 'admin/general')));
        $this->assertEquals('/admin/comments/article/4/index',
                            $m->generate(array('controller' => 'admin/comments', 'article_id' => 4)));
        $this->assertEquals('/admin/comments/article/4',
                            $m->generate(array('controller' => 'admin/comments', 'action' => null,
                                               'article_id' => 4)));
        $this->assertEquals('/articles/2004/2/20/page/1',
                            $m->generate(array('controller' => 'articles', 'action' => 'find_by_date',
                                               'year' => 2004, 'month' => 2, 'day' => 20, 'page' => 1)));
        $this->assertEquals('/articles/category',
                            $m->generate(array('controller' => 'articles', 'action' => 'category')));
        $this->assertEquals('/xml/index/feed.xml',
                            $m->generate(array('controller' => 'xml')));
        $this->assertEquals('/xml/articlerss/feed.xml',
                            $m->generate(array('controller' => 'xml', 'action' => 'articlerss')));
        $this->assertNull($m->generate(array('controller' => 'admin/comments', 'id' => 2)));
        $this->assertNull($m->generate(array('controller' => 'articles', 'action' => 'find_by_date',
                                             'year' => 2004)));
    }

    public function testBigMultirouteWithSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('', array('controller' => 'articles', 'action' => 'index'));
        $m->connect('admin', array('controller' => 'admin/general', 'action' => 'index'));

        $m->connect('admin/comments/article/:(article_id)/:(action)/:(id)',
                    array('controller' => 'admin/comments', 'action' => null, 'id' => null));
        $m->connect('admin/trackback/article/:(article_id)/:(action)/:(id)',
                    array('controller' => 'admin/trackback', 'action' => null, 'id' => null));
        $m->connect('admin/content/:(action)/:(id)', array('controller' => 'admin/content'));

        $m->connect('xml/:(action)/feed.xml', array('controller' => 'xml'));
        $m->connect('xml/articlerss/:(id)/feed.xml', array('controller' => 'xml', 'action' => 'articlerss'));
        $m->connect('index.rdf', array('controller' => 'xml', 'action' => 'rss'));

        $m->connect('articles', array('controller' => 'articles', 'action' => 'index'));
        $m->connect('articles/page/:(page)',
                    array('controller' => 'articles', 'action' => 'index',
                          'requirements' => array('page' => '\d+')));
        $m->connect('articles/:(year)/:(month)/:(day)/page/:(page)',
                    array('controller' => 'articles', 'action' => 'find_by_date',
                          'month' => null, 'day' => null,
                          'requirements' => array('year' => '\d{4}', 'month' => '\d{1,2}',
                                                                     'day' => '\d{1,2}')));
        $m->connect('articles/category/:(id)', array('controller' => 'articles', 'action' => 'category'));
        $m->connect('pages/*name', array('controller' => 'articles', 'action' => 'view_page'));

        $this->assertEquals('/pages/the/idiot/has/spoken',
                            $m->generate(array('controller' => 'articles', 'action' => 'view_page',
                                               'name' => 'the/idiot/has/spoken')));
        $this->assertEquals('/',
                            $m->generate(array('controller' => 'articles', 'action' => 'index')));
        $this->assertEquals('/xml/articlerss/4/feed.xml',
                            $m->generate(array('controller' => 'xml', 'action' => 'articlerss', 'id' => 4)));
        $this->assertEquals('/xml/rss/feed.xml',
                            $m->generate(array('controller' => 'xml', 'action' => 'rss')));
        $this->assertEquals('/admin/comments/article/4/view/2',
                            $m->generate(array('controller' => 'admin/comments', 'action' => 'view',
                                               'article_id' => 4, 'id' => 2)));
        $this->assertEquals('/admin',
                            $m->generate(array('controller' => 'admin/general')));
        $this->assertEquals('/admin/comments/article/4/index',
                            $m->generate(array('controller' => 'admin/comments', 'article_id' => 4)));
        $this->assertEquals('/admin/comments/article/4',
                            $m->generate(array('controller' => 'admin/comments', 'action' => null,
                                               'article_id' => 4)));
        $this->assertEquals('/articles/2004/2/20/page/1',
                            $m->generate(array('controller' => 'articles', 'action' => 'find_by_date',
                                               'year' => 2004, 'month' => 2, 'day' => 20, 'page' => 1)));
        $this->assertEquals('/articles/category',
                            $m->generate(array('controller' => 'articles', 'action' => 'category')));
        $this->assertEquals('/xml/index/feed.xml',
                            $m->generate(array('controller' => 'xml')));
        $this->assertEquals('/xml/articlerss/feed.xml',
                            $m->generate(array('controller' => 'xml', 'action' => 'articlerss')));
        $this->assertNull($m->generate(array('controller' => 'admin/comments', 'id' => 2)));
        $this->assertNull($m->generate(array('controller' => 'articles', 'action' => 'find_by_date',
                                             'year' => 2004)));
    }

    public function testNoExtras()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':controller/:action/:id');
        $m->connect('archive/:year/:month/:day', array('controller' => 'blog', 'action' => 'view',
                                                       'month' => null, 'day' => null));

        $this->assertEquals('/archive/2004',
                            $m->generate(array('controller' => 'blog', 'action' => 'view',
                                               'year' => 2004)));
    }

    public function testNoExtrasWithSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':(controller)/:(action)/:(id)');
        $m->connect('archive/:(year)/:(month)/:(day)', array('controller' => 'blog', 'action' => 'view',
                                                             'month' => null, 'day' => null));
    }

    public function testTheSmallestRoute()
    {
        foreach (array('pages/:title', 'pages/:(title)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect('', array('controller' => 'page', 'action' => 'view', 'title' => 'HomePage'));
            $m->connect($path, array('controller' => 'page', 'action' => 'view'));

            $this->assertEquals('/pages/joe', $m->generate(array('controller' => 'page', 'action' => 'view', 'title' => 'joe')));
            $this->assertEquals('/',
                                $m->generate(array('controller' => 'page', 'action' => 'view',
                                                   'title' => 'HomePage')));
        }
    }

    public function testExtras()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('viewpost/:id', array('controller' => 'post', 'action' => 'view'));
        $m->connect(':controller/:action/:id');

        $this->assertEquals('/viewpost/2?extra=x%2Fy',
                            $m->generate(array('controller' => 'post', 'action' => 'view',
                                               'id' => 2, 'extra' => 'x/y')));
        $this->assertEquals('/blog?extra=3',
                            $m->generate(array('controller' => 'blog', 'action' => 'index', 'extra' => 3)));
        $this->assertEquals('/viewpost/2?extra=3',
                            $m->generate(array('controller' => 'post', 'action' => 'view',
                                               'id' => 2, 'extra' => 3)));
    }

    public function testExtrasWithSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('viewpost/:(id)', array('controller' => 'post', 'action' => 'view'));
        $m->connect(':(controller)/:(action)/:(id)');

        $this->assertEquals('/viewpost/2?extra=x%2Fy',
                            $m->generate(array('controller' => 'post', 'action' => 'view',
                                               'id' => 2, 'extra' => 'x/y')));
        $this->assertEquals('/blog?extra=3',
                            $m->generate(array('controller' => 'blog', 'action' => 'index', 'extra' => 3)));
        $this->assertEquals('/viewpost/2?extra=3',
                            $m->generate(array('controller' => 'post', 'action' => 'view',
                                               'id' => 2, 'extra' => 3)));
    }

    public function testStatic()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('hello/world', array('controller' => 'content', 'action' => 'index',
                                         'known' => 'known_value'));

        $this->assertEquals('/hello/world',
                            $m->generate(array('controller' => 'content', 'action' => 'index',
                                               'known' => 'known_value')));
        $this->assertEquals('/hello/world?extra=hi',
                            $m->generate(array('controller' => 'content', 'action' => 'index',
                                               'known' => 'known_value', 'extra' => 'hi')));
        $this->assertNull($m->generate(array('known' => 'foo')));
    }

    public function testTypical()
    {
        foreach (array(':controller/:action/:id', ':(controller)/:(action)/:(id)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('action' => 'index', 'id' => null));

            $this->assertEquals('/content',
                                $m->generate(array('controller' => 'content', 'action' => 'index')));
            $this->assertEquals('/content/list',
                                $m->generate(array('controller' => 'content', 'action' => 'list')));
            $this->assertEquals('/content/show/10',
                                $m->generate(array('controller' => 'content', 'action' => 'show', 'id' => 10)));

            $this->assertEquals('/admin/user',
                                $m->generate(array('controller' => 'admin/user', 'action' => 'index')));
            $this->assertEquals('/admin/user',
                                $m->generate(array('controller' => 'admin/user')));
            $this->assertEquals('/admin/user/show/10',
                                $m->generate(array('controller' => 'admin/user', 'action' => 'show', 'id' => 10)));

            $this->assertEquals('/content', $m->generate(array('controller' => 'content')));
        }
    }

    public function testRouteWithFixnumDefault()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('page/:id', array('controller' => 'content', 'action' => 'show_page', 'id' => 1));

        $m->connect(':controller/:action/:id');

        $this->assertEquals('/page',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page')));
        $this->assertEquals('/page',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page', 'id' => 1)));
        $this->assertEquals('/page',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page', 'id' => '1')));
        $this->assertEquals('/page/10',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page', 'id' => 10)));
        $this->assertEquals('/blog/show/4',
                            $m->generate(array('controller' => 'blog', 'action' => 'show', 'id' => 4)));
        $this->assertEquals('/page',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page')));
        $this->assertEquals('/page/4',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page', 'id' => 4)));
        $this->assertEquals('/content/show',
                            $m->generate(array('controller' => 'content', 'action' => 'show')));
    }

    public function testRouteWithFixnumDefaultWithSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('page/:(id)', array('controller' => 'content', 'action' => 'show_page', 'id' => 1));
        $m->connect(':(controller)/:(action)/:(id)');

        $this->assertEquals('/page',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page')));
        $this->assertEquals('/page',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page', 'id' => 1)));
        $this->assertEquals('/page',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page', 'id' => '1')));
        $this->assertEquals('/page/10',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page', 'id' => 10)));
        $this->assertEquals('/blog/show/4',
                            $m->generate(array('controller' => 'blog', 'action' => 'show', 'id' => 4)));
        $this->assertEquals('/page',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page')));
        $this->assertEquals('/page/4',
                            $m->generate(array('controller' => 'content', 'action' => 'show_page', 'id' => 4)));
        $this->assertEquals('/content/show',
                            $m->generate(array('controller' => 'content', 'action' => 'show')));
    }

    public function testUppercaseRecognition()
    {
        foreach (array(':controller/:action/:id', ':(controller)/:(action)/:(id)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);

            $this->assertEquals('/Content',
                                $m->generate(array('controller' => 'Content', 'action' => 'index')));
            $this->assertEquals('/Content/list',
                                $m->generate(array('controller' => 'Content', 'action' => 'list')));
            $this->assertEquals('/Content/show/10',
                                $m->generate(array('controller' => 'Content', 'action' => 'show', 'id' => '10')));

            $this->assertEquals('/Admin/NewsFeed',
                                $m->generate(array('controller' => 'Admin/NewsFeed', 'action' => 'index')));
        }
    }

    public function testBackwards()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('page/:id/:action', array('controller' => 'pages', 'action' => 'show'));
        $m->connect(':controller/:action/:id');

        $this->assertEquals('/page/20',
                            $m->generate(array('controller' => 'pages', 'action' => 'show', 'id' => 20)));
        $this->assertEquals('/pages/boo',
                            $m->generate(array('controller' => 'pages', 'action' => 'boo')));
    }

    public function testBackwardsWithSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('page/:(id)/:(action)', array('controller' => 'pages', 'action' => 'show'));
        $m->connect(':(controller)/:(action)/:(id)');

        $this->assertEquals('/page/20',
                            $m->generate(array('controller' => 'pages', 'action' => 'show', 'id' => 20)));
        $this->assertEquals('/pages/boo',
                            $m->generate(array('controller' => 'pages', 'action' => 'boo')));
    }

    public function testBothRequirementAndOptional()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('test/:year', array('controller' => 'post', 'action' => 'show',
                                        'year' => null, 'requirements' => array('year' => '\d{4}')));

        $this->assertEquals('/test',
                            $m->generate(array('controller' => 'post', 'action' => 'show')));
        $this->assertEquals('/test',
                            $m->generate(array('controller' => 'post', 'action' => 'show', 'year' => null)));
    }

    public function testSetToNilForgets()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('pages/:year/:month/:day',
                    array('controller' => 'content', 'action' => 'list_pages',
                          'month' => null, 'day' => null));
        $m->connect(':controller/:action/:id');

        $this->assertEquals('/pages/2005',
                            $m->generate(array('controller' => 'content', 'action' => 'list_pages',
                                               'year' => 2005)));
        $this->assertEquals('/pages/2005/6',
                            $m->generate(array('controller' => 'content', 'action' => 'list_pages',
                                               'year' => 2005, 'month' => 6)));
        $this->assertEquals('/pages/2005/6/12',
                            $m->generate(array('controller' => 'content', 'action' => 'list_pages',
                                               'year' => 2005, 'month' => 6, 'day' => 12)));
    }

    public function testUrlWithNoActionSpecified()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('', array('controller' => 'content'));
        $m->connect(':controller/:action/:id');

        $this->assertEquals('/',
                            $m->generate(array('controller' => 'content', 'action' => 'index')));
        $this->assertEquals('/',
                            $m->generate(array('controller' => 'content')));
    }

    public function testUrlWithPrefix()
    {
        $m = new Horde_Routes_Mapper();
        $m->prefix = '/blog';
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertEquals('/blog/content/view',
                            $m->generate(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/blog/content',
                            $m->generate(array('controller' => 'content')));
        $this->assertEquals('/blog/admin/comments',
                            $m->generate(array('controller' => 'admin/comments')));
    }

    public function testUrlWithPrefixDeeper()
    {
        $m = new Horde_Routes_Mapper();
        $m->prefix = '/blog/phil';
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertEquals('/blog/phil/content/view',
                            $m->generate(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/blog/phil/content',
                            $m->generate(array('controller' => 'content')));
        $this->assertEquals('/blog/phil/admin/comments',
                            $m->generate(array('controller' => 'admin/comments')));
    }

    public function testUrlWithEnvironEmpty()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '');

        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertEquals('/content/view',
                            $m->generate(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/content',
                            $m->generate(array('controller' => 'content')));
        $this->assertEquals('/admin/comments',
                            $m->generate(array('controller' => 'admin/comments')));
    }

    public function testUrlWithEnviron()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '/blog');

        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertEquals('/blog/content/view',
                            $m->generate(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/blog/content',
                            $m->generate(array('controller' => 'content')));
        $this->assertEquals('/blog/admin/comments',
                            $m->generate(array('controller' => 'admin/comments')));

        $m->environ['SCRIPT_NAME'] = '/notblog';

        $this->assertEquals('/notblog/content/view',
                            $m->generate(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/notblog/content',
                            $m->generate(array('controller' => 'content')));
        $this->assertEquals('/notblog/content',
                            $m->generate(array('controller' => 'content')));
        $this->assertEquals('/notblog/admin/comments',
                            $m->generate(array('controller' => 'admin/comments')));
    }

    public function testUrlWithEnvironAndAbsolute()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '/blog');

        $utils = $m->utils;

        $m->connect('image', 'image/:name', array('_absolute' => true));
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertEquals('/blog/content/view',
                            $m->generate(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/blog/content',
                            $m->generate(array('controller' => 'content')));
        $this->assertEquals('/blog/content',
                            $m->generate(array('controller' => 'content')));
        $this->assertEquals('/blog/admin/comments',
                            $m->generate(array('controller' => 'admin/comments')));
        $this->assertEquals('/image/topnav.jpg',
                            $utils->urlFor('image', array('name' => 'topnav.jpg')));
    }

    public function testRouteWithOddLeftovers()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array();

        $m->connect(':controller/:(action)-:(id)');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertEquals('/content/view-',
                            $m->generate(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/content/index-',
                            $m->generate(array('controller' => 'content')));
    }

    public function testRouteWithEndExtension()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array();

        $m->connect(':controller/:(action)-:(id).html');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertNull($m->generate(array('controller' => 'content', 'action' => 'view')));
        $this->assertNull($m->generate(array('controller' => 'content')));

        $this->assertEquals('/content/view-3.html',
                            $m->generate(array('controller' => 'content', 'action' => 'view', 'id' => 3)));
        $this->assertEquals('/content/index-2.html',
                            $m->generate(array('controller' => 'content', 'id' => 2)));
    }

    public function testResources()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array();

        $utils = $m->utils;

        $m->resource('message', 'messages');
        $m->createRegs(array('messages'));
        $options = array('controller' => 'messages');

        $this->assertEquals('/messages',
                            $utils->urlFor('messages'));
        $this->assertEquals('/messages.xml',
                            $utils->urlFor('messages', array('format' => 'xml')));
        $this->assertEquals('/messages/1',
                            $utils->urlFor('message', array('id' => 1)));
        $this->assertEquals('/messages/1.xml',
                            $utils->urlFor('message', array('id' => 1, 'format' => 'xml')));
        $this->assertEquals('/messages/new',
                            $utils->urlFor('new_message'));
        $this->assertEquals('/messages/1.xml',
                            $utils->urlFor('message', array('id' => 1, 'format' => 'xml')));
        $this->assertEquals('/messages/1/edit',
                            $utils->urlFor('edit_message', array('id' => 1)));
        $this->assertEquals('/messages/1/edit.xml',
                            $utils->urlFor('edit_message', array('id' => 1, 'format' => 'xml')));

        $this->assertRestfulRoutes($m, $options);
    }

    public function testResourcesWithPathPrefix()
    {
        $m = new Horde_Routes_Mapper();
        $m->resource('message', 'messages', array('pathPrefix' => '/thread/:threadid'));
        $m->createRegs(array('messages'));
        $options = array('controller' => 'messages', 'threadid' => '5');
        $this->assertRestfulRoutes($m, $options, 'thread/5/');
    }

    public function testResourcesWithCollectionAction()
    {
        $m = new Horde_Routes_Mapper();
        $utils = $m->utils;
        $m->resource('message', 'messages', array('collection' => array('rss' => 'GET')));
        $m->createRegs(array('messages'));
        $options = array('controller' => 'messages');
        $this->assertRestfulRoutes($m, $options);

        $this->assertEquals('/messages/rss',
                            $m->generate(array('controller' => 'messages', 'action' => 'rss')));
        $this->assertEquals('/messages/rss',
                            $utils->urlFor('rss_messages'));
        $this->assertEquals('/messages/rss.xml',
                            $m->generate(array('controller' => 'messages', 'action' => 'rss',
                                               'format' => 'xml')));
        $this->assertEquals('/messages/rss.xml',
                            $utils->urlFor('formatted_rss_messages', array('format' => 'xml')));
    }

    public function testResourcesWithMemberAction()
    {
        foreach (array('put', 'post') as $method) {
            $m = new Horde_Routes_Mapper();
            $m->resource('message', 'messages', array('member' => array('mark' => $method)));
            $m->createRegs(array('messages'));

            $options = array('controller' => 'messages');
            $this->assertRestfulRoutes($m, $options);

            $connectkargs = array('method' => $method, 'action' => 'mark', 'id' => '1');
            $this->assertEquals('/messages/1/mark',
                                $m->generate(array_merge($connectkargs, $options)));

            $connectkargs = array('method' => $method, 'action' => 'mark',
                                  'id' => '1', 'format' => 'xml');
            $this->assertEquals('/messages/1/mark.xml',
                                $m->generate(array_merge($connectkargs, $options)));
        }
    }

    public function testResourcesWithNewAction()
    {
        $m = new Horde_Routes_Mapper();
        $utils = $m->utils;
        $m->resource('message', 'messages/', array('new' => array('preview' => 'POST')));
        $m->createRegs(array('messages'));
        $this->assertRestfulRoutes($m, array('controller' => 'messages'));

        $this->assertEquals('/messages/new/preview',
                            $m->generate(array('controller' => 'messages', 'action' => 'preview',
                                               'method' => 'post')));
        $this->assertEquals('/messages/new/preview',
                            $utils->urlFor('preview_new_message'));
        $this->assertEquals('/messages/new/preview.xml',
                            $m->generate(array('controller' => 'messages', 'action' => 'preview',
                                               'method' => 'post', 'format' => 'xml')));
        $this->assertEquals('/messages/new/preview.xml',
                            $utils->urlFor('preview_new_message', array('format' => 'xml')));
    }

    public function testResourcesWithNamePrefix()
    {
        $m = new Horde_Routes_Mapper();
        $utils = $m->utils;
        $m->resource('message', 'messages', array('namePrefix' => 'category_',
                                                  'new'        => array('preview' => 'POST')));
        $m->createRegs(array('messages'));
        $options = array('controller' => 'messages');
        $this->assertRestfulRoutes($m, $options);

        $this->assertEquals('/messages/new/preview',
                            $utils->urlFor('category_preview_new_message'));

        $this->assertNull($utils->urlFor('category_preview_new_message', array('method' => 'get')));
    }

    public function testUnicode()
    {
        // php version does not handing decoding
    }

    public function testUnicodeStatic()
    {
        // php version does not handing decoding
    }

    public function testOtherSpecialChars()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('/:year/:(slug).:(format),:(locale)', array('locale' => 'en', 'format' => 'html'));
        $m->createRegs(array('content'));

        $this->assertEquals('/2007/test',
                            $m->generate(array('year' => 2007, 'slug' => 'test')));
        $this->assertEquals('/2007/test.xml',
                            $m->generate(array('year' => 2007, 'slug' => 'test', 'format' => 'xml')));
        $this->assertEquals('/2007/test.xml,ja',
                            $m->generate(array('year' => 2007, 'slug' => 'test', 'format' => 'xml',
                                               'locale' => 'ja')));
        $this->assertNull($m->generate(array('year' => 2007, 'format' => 'html')));
    }

    // Test Helpers

    public function assertRestfulRoutes($m, $options, $pathPrefix = '')
    {
        $baseroute = '/' . $pathPrefix . $options['controller'];

        $this->assertEquals($baseroute,
                            $m->generate(array_merge($options, array('action' => 'index'))));

        $this->assertEquals($baseroute . '.xml',
                            $m->generate(array_merge($options, array('action' => 'index',
                                                                     'format' => 'xml'))));
        $this->assertEquals($baseroute . '/new',
                            $m->generate(array_merge($options, array('action' => 'new'))));
        $this->assertEquals($baseroute . '/1',
                            $m->generate(array_merge($options, array('action' => 'show',
                                                                     'id'     => '1'))));
        $this->assertEquals($baseroute . '/1/edit',
                            $m->generate(array_merge($options, array('action' => 'edit',
                                                                     'id'     => '1'))));
        $this->assertEquals($baseroute . '/1.xml',
                            $m->generate(array_merge($options, array('action' => 'show',
                                                                     'id'     => '1',
                                                                     'format' => 'xml'))));
        $this->assertEquals($baseroute,
                            $m->generate(array_merge($options, array('action' => 'create',
                                                                     'method' => 'post'))));

        $this->assertEquals($baseroute . '/1',
                            $m->generate(array_merge($options, array('action' => 'update',
                                                                     'method' => 'put',
                                                                     'id'     => '1'))));
        $this->assertEquals($baseroute . '/1',
                            $m->generate(array_merge($options, array('action' => 'delete',
                                                                     'method' => 'delete',
                                                                     'id'     => '1'))));
    }

}
