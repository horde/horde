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
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * @package Horde_Routes
 */

require_once dirname(__FILE__) . '/TestHelper.php';

/**
 * @package Horde_Routes
 */
class UtilTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('HTTP_HOST' => 'www.test.com');

        $m->connect('archive/:year/:month/:day',
            array('controller' => 'blog',
                  'action' => 'view',
                  'month' => null,
                  'day' => null,
                  'requirements' => array('month' => '\d{1,2}', 'day' => '\d{1,2}')));
        $m->connect('viewpost/:id', array('controller' => 'post', 'action' => 'view'));
        $m->connect(':controller/:action/:id');

        $this->mapper = $m;
        $this->utils = $m->utils;
    }

    public function testUrlForSelf()
    {
        $utils = $this->utils;
        $utils->mapperDict = array();

        $this->assertEquals('/blog', $utils->urlFor(array('controller' => 'blog')));
        $this->assertEquals('/content', $utils->urlFor());
        $this->assertEquals('https://www.test.com/viewpost', $utils->urlFor(array('controller' => 'post', 'action' => 'view', 'protocol' => 'https')));
        $this->assertEquals('http://www.test.org/content/view/2', $utils->urlFor(array('host' => 'www.test.org', 'controller' => 'content', 'action' => 'view', 'id' => 2)));
    }

    public function testUrlForWithDefaults()
    {
        $utils = $this->utils;
        $utils->mapperDict = array('controller' => 'blog', 'action' => 'view', 'id' => 4);

        $this->assertEquals('/blog/view/4', $utils->urlFor());
        $this->assertEquals('/post/index/4', $utils->urlFor(array('controller' => 'post')));
        $this->assertEquals('/blog/view/2', $utils->urlFor(array('id' => 2)));
        $this->assertEquals('/viewpost/4', $utils->urlFor(array('controller' => 'post', 'action' => 'view', 'id' => 4)));

        $utils->mapperDict = array('controller' => 'blog', 'action' => 'view', 'year' => 2004);
        $this->assertEquals('/archive/2004/10', $utils->urlFor(array('month' => 10)));
        $this->assertEquals('/archive/2004/9/2', $utils->urlFor(array('month' => 9, 'day' => 2)));
        $this->assertEquals('/blog', $utils->urlFor(array('controller' => 'blog', 'year' => null)));
    }

    public function testUrlForWithMoreDefaults()
    {
        $utils = $this->utils;
        $utils->mapperDict = array('controller' => 'blog', 'action' => 'view', 'id' => 4);
        $this->assertEquals('/blog/view/4', $utils->urlFor());
        $this->assertEquals('/post/index/4', $utils->urlFor(array('controller' => 'post')));
        $this->assertEquals('/viewpost/4', $utils->urlfor(array('controller' => 'post', 'action' => 'view', 'id' => 4)));

        $utils->mapperDict = array('controller' => 'blog', 'action' => 'view', 'year' => 2004);
        $this->assertEquals('/archive/2004/10', $utils->urlFor(array('month' => 10)));
        $this->assertEquals('/archive/2004/9/2', $utils->urlFor(array('month' => 9, 'day' => 2)));
        $this->assertEquals('/blog', $utils->urlFor(array('controller' => 'blog', 'year' => null)));
        $this->assertEquals('/archive/2004', $utils->urlFor());
    }

    public function testUrlForWithDefaultsAndQualified()
    {
        $m = $this->mapper;
        $utils = $m->utils;

        $m->connect('home', '', array('controller' => 'blog', 'action' => 'splash'));
        $m->connect('category_home', 'category/:section', array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $environ = array('SCRIPT_NAME' => '', 'HTTP_HOST' => 'www.example.com',
                         'PATH_INFO' => '/blog/view/4');
        Horde_Routes_TestHelper::updateMapper($m, $environ);

        $this->assertEquals('/blog/view/4', $utils->urlFor());
        $this->assertEquals('/post/index/4', $utils->urlFor(array('controller' => 'post')));
        $this->assertEquals('http://www.example.com/blog/view/4', $utils->urlFor(array('qualified' => true)));
        $this->assertEquals('/blog/view/2', $utils->urlFor(array('id' => 2)));
        $this->assertEquals('/viewpost/4', $utils->urlFor(array('controller' => 'post', 'action' => 'view', 'id' => 4)));

        $environ = array('SCRIPT_NAME' => '', 'HTTP_HOST' => 'www.example.com:8080', 'PATH_INFO' => '/blog/view/4');
        Horde_Routes_TestHelper::updateMapper($m, $environ);

        $this->assertEquals('/post/index/4',
                            $utils->urlFor(array('controller' => 'post')));
        $this->assertEquals('http://www.example.com:8080/blog/view/4',
                            $utils->urlFor(array('qualified' => true)));
    }

    public function testWithRouteNames()
    {
        $m = $this->mapper;

        $utils = $this->utils;
        $utils->mapperDict = array();

        $m->connect('home', '', array('controller' => 'blog', 'action' => 'splash'));
        $m->connect('category_home', 'category/:section',
                    array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->assertEquals('/content/view',
                    $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/content',
                    $utils->urlFor(array('controller' => 'content')));
        $this->assertEquals('/admin/comments',
                    $utils->urlFor(array('controller' => 'admin/comments')));
        $this->assertEquals('/category',
                    $utils->urlFor('category_home'));
        $this->assertEquals('/category/food',
                    $utils->urlFor('category_home', array('section' => 'food')));
        $this->assertEquals('/category',
                    $utils->urlFor('home', array('action' => 'view', 'section' => 'home')));
        $this->assertEquals('/content/splash',
                    $utils->urlFor('home', array('controller' => 'content')));
        $this->assertEquals('/',
                    $utils->urlFor('/'));
    }

    public function testWithRouteNamesAndDefaults()
    {
        $this->markTestSkipped();
        
        $m = $this->mapper;

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect('home', '', array('controller' => 'blog', 'action' => 'splash'));
        $m->connect('category_home', 'category/:section', array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->connect('building', 'building/:campus/:building/alljacks', array('controller' => 'building', 'action' => 'showjacks'));
        $m->createRegs(array('content', 'blog', 'admin/comments', 'building'));

        $utils->mapperDict = array('controller' => 'building', 'action' => 'showjacks', 'campus' => 'wilma', 'building' => 'port');
        $this->assertEquals('/building/wilma/port/alljacks', $utils->urlFor());
        $this->assertEquals('/', $utils->urlFor('home'));
    }

    // callback used by testRedirectTo
    // Python version is inlined in test_redirect_to
    public function printer($echo)
    {
        $this->redirectToResult = $echo;
    }

    public function testRedirectTo()
    {
        $m = $this->mapper;
        $m->environ = array('SCRIPT_NAME' => '', 'HTTP_HOST' => 'www.example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();

        $callback = array($this, 'printer');
        $utils->redirect = $callback;

        $m->createRegs(array('content', 'blog', 'admin/comments'));

        $this->redirectToResult = null;
        $utils->redirectTo(array('controller' => 'content', 'action' => 'view'));
        $this->assertEquals('/content/view', $this->redirectToResult);

        $this->redirectToResult = null;
        $utils->redirectTo(array('controller' => 'content', 'action' => 'lookup', 'id' => 4));
        $this->assertEquals('/content/lookup/4', $this->redirectToResult);

        $this->redirectToResult = null;
        $utils->redirectTo(array('controller' => 'admin/comments', 'action' => 'splash'));
        $this->assertEquals('/admin/comments/splash', $this->redirectToResult);

        $this->redirectToResult = null;
        $utils->redirectTo('http://www.example.com/');
        $this->assertEquals('http://www.example.com/', $this->redirectToResult);

        $this->redirectToResult = null;
        $utils->redirectTo('/somewhere.html', array('var' => 'keyword'));
        $this->assertEquals('/somewhere.html?var=keyword', $this->redirectToResult);
    }

    public function testStaticRoute()
    {
        $m = $this->mapper;

        $utils = $m->utils;
        $utils->mapperDict = array();

        $environ = array('SCRIPT_NAME' => '', 'HTTP_HOST' => 'example.com');
        Horde_Routes_TestHelper::updateMapper($m, $environ);

        $m->connect(':controller/:action/:id');
        $m->connect('home', 'http://www.groovie.org/', array('_static' => true));
        $m->connect('space', '/nasa/images', array('_static' => true));
        $m->createRegs(array('content', 'blog'));

        $this->assertEquals('http://www.groovie.org/',
                            $utils->urlFor('home'));
        $this->assertEquals('http://www.groovie.org/?s=stars',
                            $utils->urlFor('home', array('s' => 'stars')));
        $this->assertEquals('/content/view',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/nasa/images?search=all',
                            $utils->urlFor('space', array('search' => 'all')));
    }

    public function testStaticRouteWithScript()
    {
        $m = $this->mapper;
        $m->environ = array('SCRIPT_NAME' => '/webapp', 'HTTP_HOST' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();


        $m->connect(':controller/:action/:id');
        $m->connect('home', 'http://www.groovie.org/', array('_static' => true));
        $m->connect('space', '/nasa/images', array('_static' => true));
        $m->createRegs(array('content', 'blog'));

        $this->assertEquals('http://www.groovie.org/',
                            $utils->urlFor('home'));
        $this->assertEquals('http://www.groovie.org/?s=stars',
                            $utils->urlFor('home', array('s' => 'stars')));
        $this->assertEquals('/webapp/content/view',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/webapp/nasa/images?search=all',
                            $utils->urlFor('space', array('search' => 'all')));
        $this->assertEquals('http://example.com/webapp/nasa/images',
                            $utils->urlFor('space', array('protocol' => 'http')));
    }

    public function testNoNamedPath()
    {
        $m = $this->mapper;
        $m->environ = array('SCRIPT_NAME' => '', 'HTTP_HOST' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect(':controller/:action/:id');
        $m->connect('home', 'http://www.groovie.org', array('_static' => true));
        $m->connect('space', '/nasa/images', array('_static' => true));
        $m->createRegs(array('content', 'blog'));

        $this->assertEquals('http://www.google.com/search',
                            $utils->urlFor('http://www.google.com/search'));
        $this->assertEquals('http://www.google.com/search?q=routes',
                            $utils->urlFor('http://www.google.com/search', array('q'=>'routes')));
        $this->assertEquals('/delicious.jpg',
                            $utils->urlFor('/delicious.jpg'));
        $this->assertEquals('/delicious/search?v=routes',
                            $utils->urlFor('/delicious/search', array('v'=>'routes')));
    }

    public function testAppendSlash()
    {
        $m = $this->mapper;
        $m->environ = array('SCRIPT_NAME' => '', 'HTTP_HOST' => 'example.com');
        $m->appendSlash = true;

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect(':controller/:action/:id');
        $m->connect('home', 'http://www.groovie.org/', array('_static' => true));
        $m->connect('space', '/nasa/images', array('_static' => true));
        $m->createRegs(array('content', 'blog'));

        $this->assertEquals('http://www.google.com/search',
                            $utils->urlFor('http://www.google.com/search'));
        $this->assertEquals('http://www.google.com/search?q=routes',
                            $utils->urlFor('http://www.google.com/search', array('q'=>'routes')));
        $this->assertEquals('/delicious.jpg',
                            $utils->urlFor('/delicious.jpg'));
        $this->assertEquals('/delicious/search?v=routes',
                            $utils->urlFor('/delicious/search', array('v' => 'routes')));
        $this->assertEquals('/content/list/',
                            $utils->urlFor(array('controller' => '/content', 'action' => 'list')));
        $this->assertEquals('/content/list/?page=1',
                            $utils->urlFor(array('controller' => '/content', 'action' => 'list', 'page' => '1')));
    }

    public function testNoNamedPathWithScript()
    {
        $m = $this->mapper;
        $m->environ = array('SCRIPT_NAME' => '/webapp', 'HTTP_HOST' => 'example.com');
        
        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect(':controller/:action/:id');
        $m->connect('home', 'http://www.groovie.org/', array('_static' => true));
        $m->connect('space', '/nasa/images', array('_static' => true));
        $m->createRegs(array('content', 'blog'));

        $this->assertEquals('http://www.google.com/search',
                            $utils->urlFor('http://www.google.com/search'));
        $this->assertEquals('http://www.google.com/search?q=routes',
                            $utils->urlFor('http://www.google.com/search', array('q'=>'routes')));
        $this->assertEquals('/webapp/delicious.jpg',
                            $utils->urlFor('/delicious.jpg'));
        $this->assertEquals('/webapp/delicious/search?v=routes',
                            $utils->urlFor('/delicious/search', array('v'=>'routes')));
    }

    // callback used by testRouteFilter
    // Python version is inlined in test_route_filter
    public function articleFilter($kargs)
    {
        $article = isset($kargs['article']) ? $kargs['article'] : null;
        unset($kargs['article']);

        if ($article !== null) {
            $kargs['year']  = isset($article['year'])  ? $article['year']  : 2004;
            $kargs['month'] = isset($article['month']) ? $article['month'] : 12;
            $kargs['day']   = isset($article['day'])   ? $article['day']   : 20;
            $kargs['slug']  = isset($article['slug'])  ? $article['slug']  : 'default';
        }

        return $kargs;
    }

    public function testRouteFilter()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '', 'HTTP_HOST' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();

        $callback = array($this, 'articleFilter');

        $m->connect(':controller/:(action)-:(id).html');
        $m->connect('archives', 'archives/:year/:month/:day/:slug',
                    array('controller' =>'archives', 'action' =>'view', '_filter' => $callback));
        $m->createRegs(array('content', 'archives', 'admin/comments'));

        $this->assertNull($utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertNull($utils->urlFor(array('controller' => 'content')));

        $this->assertEquals('/content/view-3.html',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view', 'id' => 3)));
        $this->assertEquals('/content/index-2.html',
                            $utils->urlFor(array('controller' => 'content', 'id' => 2)));

        $this->assertEquals('/archives/2005/10/5/happy',
                            $utils->urlFor('archives', array('year' => 2005, 'month' => 10,
                                                            'day' => 5, 'slug' => 'happy')));

        $story = array('year' => 2003, 'month' => 8, 'day' => 2, 'slug' => 'woopee');
        $empty = array();

        $expected = array('controller' => 'archives', 'action' => 'view', 'year' => '2005',
                         'month' => '10', 'day' => '5', 'slug' => 'happy');
        $this->assertEquals($expected, $m->match('/archives/2005/10/5/happy'));

        $this->assertEquals('/archives/2003/8/2/woopee',
                            $utils->urlFor('archives', array('article' => $story)));
        $this->assertEquals('/archives/2004/12/20/default',
                            $utils->urlFor('archives', array('article' => $empty)));
    }

    public function testWithSslEnviron()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '', 'HTTPS' => 'on', 'SERVER_PORT' => '443', 
                            'PATH_INFO' => '/', 'HTTP_HOST' => 'example.com', 
                            'SERVER_NAME' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'archives', 'admin/comments'));

        // HTTPS is on, but we're running on a different port internally
        $this->assertEquals('/content/view',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/content/index/2',
                            $utils->urlFor(array('controller' => 'content', 'id' => 2)));
        $this->assertEquals('https://nowhere.com/content',
                            $utils->urlFor(array('host' => 'nowhere.com', 'controller' => 'content')));

        // If HTTPS is on, but the port isn't 443, we'll need to include the port info
        $m->environ['SERVER_PORT'] = '8080';

        $utils->mapperDict = array();

        $this->assertEquals('/content/index/2',
                            $utils->urlFor(array('controller' => 'content', 'id' => '2')));
        $this->assertEquals('https://nowhere.com/content',
                            $utils->urlFor(array('host' => 'nowhere.com', 'controller' => 'content')));
        $this->assertEquals('https://nowhere.com:8080/content',
                            $utils->urlFor(array('host' => 'nowhere.com:8080', 'controller' => 'content')));
        $this->assertEquals('http://nowhere.com/content',
                            $utils->urlFor(array('host' => 'nowhere.com', 'protocol' => 'http',
                                                'controller' => 'content')));
    }

    public function testWithHttpEnviron()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '', 'SERVER_PORT' => '1080', 'PATH_INFO' => '/',
                            'HTTP_HOST' => 'example.com', 'SERVER_NAME' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'archives', 'admin/comments'));

        $this->assertEquals('/content/view',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/content/index/2',
                            $utils->urlFor(array('controller' => 'content', 'id' => 2)));
        $this->assertEquals('https://example.com/content',
                            $utils->urlFor(array('protocol' => 'https', 'controller' => 'content')));
    }

    public function testSubdomains()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '', 'PATH_INFO' => '/',
                            'HTTP_HOST' => 'example.com', 'SERVER_NAME' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();
        
        $m->subDomains = true;
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'archives', 'admin/comments'));

        $this->assertEquals('/content/view',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/content/index/2',
                            $utils->urlFor(array('controller' => 'content', 'id' => 2)));

        $m->environ['HTTP_HOST'] = 'sub.example.com';

        $utils->mapperDict = array('subDomain' => 'sub');

        $this->assertEquals('/content/view/3',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view', 'id' => 3)));
        $this->assertEquals('http://new.example.com/content',
                            $utils->urlFor(array('controller' => 'content', 'subDomain' => 'new')));
    }

    public function testSubdomainsWithExceptions()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '', 'PATH_INFO' => '/',
                            'HTTP_HOST' => 'example.com', 'SERVER_NAME' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->subDomains = true;
        $m->subDomainsIgnore = array('www');
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'archives', 'admin/comments'));

        $this->assertEquals('/content/view',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/content/index/2',
                            $utils->urlFor(array('controller' => 'content', 'id' => 2)));

        $m->environ = array('HTTP_HOST' => 'sub.example.com');

        $utils->mapperDict = array('subDomain' => 'sub');

        $this->assertEquals('/content/view/3',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view', 'id' => 3)));
        $this->assertEquals('http://new.example.com/content',
                            $utils->urlFor(array('controller' => 'content', 'subDomain' => 'new')));
        $this->assertEquals('http://example.com/content',
                            $utils->urlFor(array('controller' => 'content', 'subDomain' => 'www')));

        $utils->mapperDict = array('subDomain' => 'www');
        $this->assertEquals('http://example.com/content/view/3',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view', 'id' => 3)));
        $this->assertEquals('http://new.example.com/content',
                            $utils->urlFor(array('controller' => 'content', 'subDomain' => 'new')));
        $this->assertEquals('/content',
                            $utils->urlFor(array('controller' => 'content', 'subDomain' => 'sub')));
    }

    public function testSubdomainsWithNamedRoutes()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '', 'PATH_INFO' => '/',
                            'HTTP_HOST' => 'example.com', 'SERVER_NAME' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->subDomains = true;
        $m->connect(':controller/:action/:id');
        $m->connect('category_home', 'category/:section',
                    array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->connect('building', 'building/:campus/:building/alljacks',
                    array('controller' => 'building', 'action' => 'showjacks'));
        $m->createRegs(array('content','blog','admin/comments','building'));

        $this->assertEquals('/content/view',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/content/index/2',
                            $utils->urlFor(array('controller' => 'content', 'id' => 2)));
        $this->assertEquals('/category',
                            $utils->urlFor('category_home'));
        $this->assertEquals('http://new.example.com/category',
                            $utils->urlFor('category_home', array('subDomain' => 'new')));
    }

    public function testSubdomainsWithPorts()
    {
        $m = new Horde_Routes_Mapper();
        $m->environ = array('SCRIPT_NAME' => '', 'PATH_INFO' => '/',
                            'HTTP_HOST' => 'example.com:8000', 'SERVER_NAME' => 'example.com');

        $utils = $m->utils;
        $utils->mapperDict = array();

        $m->subDomains = true;
        $m->connect(':controller/:action/:id');
        $m->connect('category_home', 'category/:section',
                    array('controller' => 'blog', 'action' => 'view', 'section' => 'home'));
        $m->connect('building', 'building/:campus/:building/alljacks',
                    array('controller' => 'building', 'action' => 'showjacks'));
        $m->createRegs(array('content', 'blog', 'admin/comments', 'building'));

        $this->assertEquals('/content/view',
                            $utils->urlFor(array('controller' => 'content', 'action' => 'view')));
        $this->assertEquals('/category',
                            $utils->urlFor('category_home'));
        $this->assertEquals('http://new.example.com:8000/category',
                            $utils->urlFor('category_home', array('subDomain' => 'new')));
        $this->assertEquals('http://joy.example.com:8000/building/west/merlot/alljacks',
                            $utils->urlFor('building', array('campus' => 'west', 'building' => 'merlot',
                                                            'subDomain' => 'joy')));

        $m->environ = array('HTTP_HOST' => 'example.com');

        $this->assertEquals('http://new.example.com/category',
                            $utils->urlFor('category_home', array('subDomain' => 'new')));
    }

    public function testControllerScan()
    {
        $hereDir = dirname(__FILE__);
        $controllerDir = "$hereDir/fixtures/controllers";

        $controllers = Horde_Routes_Utils::controllerScan($controllerDir);
        
        $this->assertEquals(3, count($controllers));
        $this->assertEquals('admin/users', $controllers[0]);
        $this->assertEquals('content', $controllers[1]);
        $this->assertEquals('users', $controllers[2]);
    }

    public function testAutoControllerScan()
    {
        $hereDir = dirname(__FILE__);
        $controllerDir = "$hereDir/fixtures/controllers";

        $m = new Horde_Routes_Mapper(array('directory' => $controllerDir));
        $m->alwaysScan = true;
        
        $m->connect(':controller/:action/:id');

        $expected = array('action' => 'index', 'controller' => 'content', 'id' => null);
        $this->assertEquals($expected, $m->match('/content'));

        $expected = array('action' => 'index', 'controller' => 'users', 'id' => null);
        $this->assertEquals($expected, $m->match('/users'));

        $expected = array('action' => 'index', 'controller' => 'admin/users', 'id' => null);
        $this->assertEquals($expected, $m->match('/admin/users'));
    }

}
