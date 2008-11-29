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

/**
 * @package Horde_Routes
 */
class RecognitionTest extends PHPUnit_Framework_TestCase 
{
    public function testRegexpCharEscaping()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':controller/:(action).:(id)');
        $m->createRegs(array('content'));
        
        $this->assertNull($m->match('/content/view#2'));

        $matchdata = array('action' => 'view', 'controller' => 'content', 'id' => '2');
        $this->assertEquals($matchdata, $m->match('/content/view.2'));

        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'find.all'));

        $matchdata = array('action' => 'view#2', 'controller' => 'content', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/content/view#2'));

        $matchdata = array('action' => 'view', 'controller' => 'find.all', 'id'=> null);
        $this->assertEquals($matchdata, $m->match('/find.all/view'));
        
        $this->assertNull($m->match('/findzall/view'));
    }
 
    public function testAllStatic()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('hello/world/how/are/you', array('controller' => 'content', 'action' => 'index'));
        $m->createRegs(array());
        
        $this->assertNull($m->match('/x'));
        $this->assertNull($m->match('/hello/world/how'));
        $this->assertNull($m->match('/hello/world/how/are'));
        $this->assertNull($m->match('/hello/world/how/are/you/today'));
        
        $matchdata = array('controller' => 'content', 'action' =>'index');
        $this->assertEquals($matchdata, $m->match('/hello/world/how/are/you'));
    }

    public function testUnicode()
    {
        // php version does not handing decoding
    }
    
    public function testDisablingUnicode()
    {
        // php version does not handing decoding
    }
    
    public function testBasicDynamic()
    {
        foreach(array('hi/:name', 'hi/:(name)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'content'));
            $m->createRegs();
            
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi'));
            $this->assertNull($m->match('/hi/dude/what'));

            $matchdata = array('controller' => 'content', 'name' => 'dude', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/hi/dude'));
            $this->assertEquals($matchdata, $m->match('/hi/dude/'));
        }
    }

    public function testBasicDynamicBackwards()
    {
        foreach (array(':name/hi', ':(name)/hi') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);
            $m->createRegs();
            
            $this->assertNull($m->match('/'));
            $this->assertNull($m->match('/hi'));
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/shop/walmart/hi'));
            
            $matchdata = array('name' => 'fred', 'action' => 'index', 'controller' => 'content');
            $this->assertEquals($matchdata, $m->match('/fred/hi'));

            $matchdata = array('name' => 'index', 'action' => 'index', 'controller' => 'content');
            $this->assertEquals($matchdata, $m->match('/index/hi'));
        }
    }

    public function testDynamicWithUnderscores()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('article/:small_page', array('small_page' => false));
        $m->connect(':(controller)/:(action)/:(id)');
        $m->createRegs(array('article', 'blog'));
        
        $matchdata = array('controller' => 'blog', 'action' => 'view', 'id' => '0');
        $this->assertEquals($matchdata, $m->match('/blog/view/0'));

        $matchdata = array('controller' => 'blog', 'action' => 'view', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/view'));
    }

    public function testDynamicWithDefault()
    {
        foreach (array('hi/:action', 'hi/:(action)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'content'));
            $m->createRegs();
            
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi/dude/what'));

            $matchdata = array('controller' => 'content', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/hi'));

            $matchdata = array('controller' => 'content', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/hi/index'));
            
            $matchdata = array('controller' => 'content', 'action' => 'dude');
            $this->assertEquals($matchdata, $m->match('/hi/dude'));
        }
    }

    public function testDynamicWithDefaultBackwards()
    {
        foreach (array(':action/hi', ':(action)/hi') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'content'));
            $m->createRegs();

            $this->assertNull($m->match('/'));
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi'));

            $matchdata = array('controller' => 'content', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/index/hi'));
            $this->assertEquals($matchdata, $m->match('/index/hi/'));

            $matchdata = array('controller' => 'content', 'action' => 'dude');
            $this->assertEquals($matchdata, $m->match('/dude/hi'));
        }
    }

    public function testDynamicWithStringCondition()
    {
        foreach (array(':name/hi', ':(name)/hi') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller'   => 'content', 
                                     'requirements' => array('name' => 'index')));
            $m->createRegs();
            
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi'));
            $this->assertNull($m->match('/dude/what/hi'));

            $matchdata = array('controller' => 'content', 'name' => 'index', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/index/hi'));
            $this->assertNull($m->match('/dude/hi'));
        }
    }

    public function testDynamicWithStringConditionBackwards()
    {
        foreach (array('hi/:name', 'hi/:(name)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller'   => 'content',
                                     'requirements' => array('name' => 'index')));
            $m->createRegs();

            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi'));
            $this->assertNull($m->match('/hi/dude/what'));
            
            $matchdata = array('controller' => 'content', 'name' => 'index', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/hi/index'));

            $this->assertEquals($matchdata, $m->match('/hi/index'));
            
            $this->assertNull($m->match('/dude/hi'));
        }
    }

    public function testDynamicWithRegexpCondition()
    {
        foreach (array('hi/:name', 'hi/:(name)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller'   => 'content',
                                     'requirements' => array('name' => '[a-z]+')));
            $m->createRegs();
            
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi'));
            $this->assertNull($m->match('/hi/FOXY'));
            $this->assertNull($m->match('/hi/138708jkhdf'));
            $this->assertNull($m->match('/hi/dkjfl8792343dfsf'));
            $this->assertNull($m->match('/hi/dude/what'));
            $this->assertNull($m->match('/hi/dude/what/'));

            $matchdata = array('controller' => 'content', 'name' => 'index', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/hi/index'));

            $matchdata = array('controller' => 'content', 'name' => 'dude', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/hi/dude'));
        }
    }

    public function testDynamicWithRegexpAndDefault()
    {
        foreach (array('hi/:action', 'hi/:(action)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller'   => 'content',
                                     'requirements' => array('action' => '[a-z]+')));
            $m->createRegs();
            
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi/FOXY'));
            $this->assertNull($m->match('/hi/138708jkhdf'));
            $this->assertNull($m->match('/hi/dkjfl8792343dfsf'));
            $this->assertNull($m->match('/hi/dude/what/'));
            
            $matchdata = array('controller' => 'content', 'action' => 'index');
            $this->assertEquals($matchdata, $m->match('/hi'));
            $this->assertEquals($matchdata, $m->match('/hi/index'));

            $matchdata = array('controller' => 'content', 'action' => 'dude');
            $this->assertEquals($matchdata, $m->match('/hi/dude'));
        }
    }

    public function testDynamicWithDefaultAndStringConditionBackwards()
    {
        foreach (array(':action/hi', ':(action)/hi') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);
            $m->createRegs();
            
            $this->assertNull($m->match('/'));
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi'));

            $matchdata = array('action' => 'index', 'controller' => 'content');
            $this->assertEquals($matchdata, $m->match('/index/hi'));
        }
    }

    public function testDynamicAndControllerWithStringAndDefaultBackwards()
    {
        foreach (array(':controller/:action/hi', ':(controller)/:(action)/hi') as $path) {
            $m = new Horde_Routes_Mapper();

            $m->connect($path, array('controller' => 'content'));
            $m->createRegs(array('content', 'admin/user'));

            $this->assertNull($m->match('/'));
            $this->assertNull($m->match('/fred'));
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
        $m->createRegs(array('post','blog','admin/user'));
        
        $this->assertNull($m->match('/'));
        $this->assertNull($m->match('/archive'));
        $this->assertNull($m->match('/archive/2004/ab'));

        $matchdata = array('controller' => 'blog', 'action' => 'view', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/view'));

        $matchdata = array('controller' => 'blog', 'action' => 'view',
                           'month' => null, 'day' => null, 'year' => '2004');
        $this->assertEquals($matchdata, $m->match('/archive/2004'));

        $matchdata = array('controller' => 'blog', 'action' => 'view', 
                           'month' => '4', 'day' => null, 'year' =>'2004');
        $this->assertEquals($matchdata, $m->match('/archive/2004/4'));
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
        $m->createRegs(array('post','blog','admin/user'));
        
        $this->assertNull($m->match('/'));
        $this->assertNull($m->match('/archive'));
        $this->assertNull($m->match('/archive/2004/ab'));

        $matchdata = array('controller' => 'blog', 'action' => 'view', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/view'));

        $matchdata = array('controller' => 'blog', 'action' => 'view',
                           'month' => null, 'day' => null, 'year' => '2004');
        $this->assertEquals($matchdata, $m->match('/archive/2004'));

        $matchdata = array('controller' => 'blog', 'action' => 'view', 
                           'month' => '4', 'day' => null, 'year' => '2004');
        $this->assertEquals($matchdata, $m->match('/archive/2004/4'));             
    }
    
    public function testDynamicWithRegexpDefaultsAndGaps()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('archive/:year/:month/:day', array('controller' => 'blog', 'action' => 'view', 
                                                       'month' => null, 'day' => null,
	                                                   'requirements' => array('month' => '\d{1,2}')));
        $m->connect('view/:id/:controller', array('controller' => 'blog', 'action' => 'view',
                                                      'id' => 2, 'requirements' => array('id' => '\d{1,2}')));
        $m->createRegs(array('post','blog','admin/user'));        
        
        $this->assertNull($m->match('/'));
        $this->assertNull($m->match('/archive'));
        $this->assertNull($m->match('/archive/2004/haha'));
        $this->assertNull($m->match('/view/blog'));

        $matchdata = array('controller' => 'blog', 'action' => 'view', 'id' => '2');
        $this->assertEquals($matchdata, $m->match('/view'));
        
        $matchdata = array('controller' => 'blog', 'action' => 'view', 
                           'month' => null, 'day' => null, 'year' => '2004');
        $this->assertEquals($matchdata, $m->match('/archive/2004'));
    }

    public function testDynamicWithRegexpDefaultsAndGapsAndSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('archive/:(year)/:(month)/:(day)', array('controller' => 'blog', 'action' => 'view', 
                                                             'month' => null, 'day' => null,
	                                                         'requirements' => array('month' => '\d{1,2}')));
        $m->connect('view/:(id)/:(controller)', array('controller' => 'blog', 'action' => 'view',
                                                          'id' => 2, 'requirements' => array('id' => '\d{1,2}')));
        $m->createRegs(array('post','blog','admin/user'));        
        
        $this->assertNull($m->match('/'));
        $this->assertNull($m->match('/archive'));
        $this->assertNull($m->match('/archive/2004/haha'));
        $this->assertNull($m->match('/view/blog'));

        $matchdata = array('controller' => 'blog', 'action' => 'view', 'id' => '2');
        $this->assertEquals($matchdata, $m->match('/view'));
        
        $matchdata = array('controller' => 'blog', 'action' => 'view', 
                           'month' => null, 'day' => null, 'year' => '2004');
        $this->assertEquals($matchdata, $m->match('/archive/2004'));        
    }
    
    public function testDynamicWithRegexpGapsControllers()
    {
        foreach(array('view/:id/:controller', 'view/:(id)/:(controller)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('id' => 2, 'action' => 'view', 'requirements' => array('id' => '\d{1,2}')));
            $m->createRegs(array('post', 'blog', 'admin/user'));
            
            $this->assertNull($m->match('/'));
            $this->assertNull($m->match('/view'));
            $this->assertNull($m->match('/view/blog'));
            $this->assertNull($m->match('/view/3'));
            $this->assertNull($m->match('/view/4/honker'));

            $matchdata = array('controller' => 'blog', 'action' => 'view', 'id' => '2');
            $this->assertEquals($matchdata, $m->match('/view/2/blog'));
        }
    }
    
    public function testDynamicWithTrailingStrings()
    {
        foreach (array('view/:id/:controller/super', 'view/:(id)/:(controller)/super') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'blog', 'action' => 'view',
                                     'id' => 2, 'requirements' => array('id' => '\d{1,2}')));
            $m->createRegs(array('post', 'blog', 'admin/user'));

            $this->assertNull($m->match('/'));
            $this->assertNull($m->match('/view'));
            $this->assertNull($m->match('/view/blah/blog/super'));
            $this->assertNull($m->match('/view/ha/super'));
            $this->assertNull($m->match('/view/super'));
            $this->assertNull($m->match('/view/4/super'));

            $matchdata = array('controller' => 'blog', 'action' => 'view', 'id' => '2');
            $this->assertEquals($matchdata, $m->match('/view/2/blog/super'));

            $matchdata = array('controller' => 'admin/user', 'action' => 'view', 'id' => '4');
            $this->assertEquals($matchdata, $m->match('/view/4/admin/user/super'));
        }
    }

    public function testDynamicWithTrailingNonKeywordStrings()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('somewhere/:over/rainbow', array('controller' => 'blog'));
        $m->connect('somewhere/:over', array('controller' => 'post'));
        $m->createRegs(array('post', 'blog', 'admin/user'));

        $this->assertNull($m->match('/'));
        $this->assertNull($m->match('/somewhere'));

        $matchdata = array('controller' => 'blog', 'action' => 'index', 'over' => 'near');
        $this->assertEquals($matchdata, $m->match('/somewhere/near/rainbow'));
        
        $matchdata = array('controller' => 'post', 'action' => 'index', 'over' => 'tomorrow');
        $this->assertEquals($matchdata, $m->match('/somewhere/tomorrow'));
    }

    public function testDynamicWithTrailingDynamicDefaults()
    {
        foreach (array('archives/:action/:article', 'archives/:(action)/:(article)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'blog'));
            $m->createRegs(array('blog'));
            
            $this->assertNull($m->match('/'));
            $this->assertNull($m->match('/archives'));
            $this->assertNull($m->match('/archives/introduction'));
            $this->assertNull($m->match('/archives/sample'));
            $this->assertNull($m->match('/view/super'));
            $this->assertNull($m->match('/view/4/super'));

            $matchdata = array('controller' => 'blog', 'action' => 'view', 'article' => 'introduction');
            $this->assertEquals($matchdata, $m->match('/archives/view/introduction'));
            
            $matchdata = array('controller' => 'blog', 'action' => 'edit', 'article' => 'recipes');
            $this->assertEquals($matchdata, $m->match('/archives/edit/recipes'));
        }
    }

    public function testPath()
    {
        foreach (array('hi/*file', 'hi/*(file)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'content', 'action' => 'download'));
            $m->createRegs();
            
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/hi'));
            
            $matchdata = array('controller' => 'content', 'action' => 'download', 
                               'file' => 'books/learning_python.pdf');
            $this->assertEquals($matchdata, $m->match('/hi/books/learning_python.pdf'));
            
            $matchdata = array('controller' => 'content', 'action' => 'download',
                               'file' => 'dude');
            $this->assertEquals($matchdata, $m->match('/hi/dude'));
            
            $matchdata = array('controller' => 'content', 'action' => 'download',
                               'file' => 'dude/what');
            $this->assertEquals($matchdata, $m->match('/hi/dude/what'));
        }
    }

    public function testDynamicWithPath()
    {
        foreach (array(':controller/:action/*url', ':(controller)/:(action)/*(url)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path);
            $m->createRegs(array('content', 'admin/user'));
            
            $this->assertNull($m->match('/'));
            $this->assertNull($m->match('/blog'));
            $this->assertNull($m->match('/content'));
            $this->assertNull($m->match('/content/view'));

            $matchdata = array('controller' => 'content', 'action' => 'view', 'url' => 'blob');
            $this->assertEquals($matchdata, $m->match('/content/view/blob'));

            $this->assertNull($m->match('/admin/user'));
            $this->assertNull($m->match('/admin/user/view'));

            $matchdata = array('controller' => 'admin/user', 'action' => 'view',
                               'url' => 'blob/check');
            $this->assertEquals($matchdata, $m->match('/admin/user/view/blob/check'));
        }
    }

    public function testPathWithDynamicAndDefault()
    {
        foreach (array(':controller/:action/*url', ':(controller)/:(action)/*(url)') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'content', 'action' => 'view', 'url' => null));
            $m->createRegs(array('content', 'admin/user'));
            
            $this->assertNull($m->match('/goober/view/here'));
            
            $matchdata = array('controller' => 'content', 'action' => 'view', 'url' => null);
            $this->assertEquals($matchdata, $m->match('/content'));
            $this->assertEquals($matchdata, $m->match('/content/'));
            $this->assertEquals($matchdata, $m->match('/content/view'));

            $matchdata = array('controller' => 'content', 'action' => 'view', 'url' => 'fred');
            $this->assertEquals($matchdata, $m->match('/content/view/fred'));

            $matchdata = array('controller' => 'admin/user', 'action' => 'view', 'url' => null);
            $this->assertEquals($matchdata, $m->match('/admin/user'));
            $this->assertEquals($matchdata, $m->match('/admin/user/view'));
        }
    }
    
    public function testPathWithDynamicAndDefaultBackwards()
    {
        foreach (array('*file/login', '*(file)/login') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'content', 'action' => 'download', 'file' => null));
            $m->createRegs();
            
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));

            $matchdata = array('controller' => 'content', 'action' => 'download', 'file' => '');
            $this->assertEquals($matchdata, $m->match('//login'));
            
            $matchdata = array('controller' => 'content', 'action' => 'download',
                               'file' => 'books/learning_python.pdf');
            $this->assertEquals($matchdata, $m->match('/books/learning_python.pdf/login'));
            
            $matchdata = array('controller' => 'content', 'action' => 'download', 'file' => 'dude');
            $this->assertEquals($matchdata, $m->match('/dude/login'));
            
            $matchdata = array('controller' => 'content', 'action' => 'download', 'file' => 'dude/what');
            $this->assertEquals($matchdata, $m->match('/dude/what/login'));
        }
    }
    
    public function testPathBackwards()
    {
        foreach (array('*file/login', '*(file)/login') as $path) {
            $m = new Horde_Routes_Mapper();
            $m->connect($path, array('controller' => 'content', 'action' => 'download'));
            $m->createRegs();
            
            $this->assertNull($m->match('/boo'));
            $this->assertNull($m->match('/boo/blah'));
            $this->assertNull($m->match('/login'));

            $matchdata = array('controller' => 'content', 'action' => 'download',
                               'file' => 'books/learning_python.pdf');
            $this->assertEquals($matchdata, $m->match('/books/learning_python.pdf/login'));
            
            $matchdata = array('controller' => 'content', 'action' => 'download', 'file' => 'dude');
            $this->assertEquals($matchdata, $m->match('/dude/login'));
            
            $matchdata = array('controller' => 'content', 'action' => 'download', 'file' => 'dude/what');
            $this->assertEquals($matchdata, $m->match('/dude/what/login'));
        }
    }
    
    public function testPathBackwardsWithController()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('*url/login', array('controller' => 'content', 'action' => 'check_access'));
        $m->connect('*url/:controller', array('action' => 'view'));
        $m->createRegs(array('content', 'admin/user'));
        
        $this->assertNull($m->match('/boo'));
        $this->assertNull($m->match('/boo/blah'));
        $this->assertNull($m->match('/login'));
        
        $matchdata = array('controller' => 'content', 'action' => 'check_access',
                           'url' => 'books/learning_python.pdf');
        $this->assertEquals($matchdata, $m->match('/books/learning_python.pdf/login'));
        
        $matchdata = array('controller' => 'content', 'action' => 'check_access', 'url' => 'dude');
        $this->assertEquals($matchdata, $m->match('/dude/login'));
        
        $matchdata = array('controller' => 'content', 'action' => 'check_access', 'url' => 'dude/what');
        $this->assertEquals($matchdata, $m->match('/dude/what/login'));

        $this->assertNull($m->match('/admin/user'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'view',
                           'url' => 'books/learning_python.pdf');
        $this->assertEquals($matchdata, $m->match('/books/learning_python.pdf/admin/user'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'view', 'url' => 'dude');
        $this->assertEquals($matchdata, $m->match('/dude/admin/user'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'view', 'url' => 'dude/what');
        $this->assertEquals($matchdata, $m->match('/dude/what/admin/user'));
    }
    
    public function testPathBackwardsWithControllerAndSplits()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('*(url)/login', array('controller' => 'content', 'action' => 'check_access'));
        $m->connect('*(url)/:(controller)', array('action' => 'view'));
        $m->createRegs(array('content', 'admin/user'));
        
        $this->assertNull($m->match('/boo'));
        $this->assertNull($m->match('/boo/blah'));
        $this->assertNull($m->match('/login'));
        
        $matchdata = array('controller' => 'content', 'action' => 'check_access',
                           'url' => 'books/learning_python.pdf');
        $this->assertEquals($matchdata, $m->match('/books/learning_python.pdf/login'));
        
        $matchdata = array('controller' => 'content', 'action' => 'check_access', 'url' => 'dude');
        $this->assertEquals($matchdata, $m->match('/dude/login'));
        
        $matchdata = array('controller' => 'content', 'action' => 'check_access', 'url' => 'dude/what');
        $this->assertEquals($matchdata, $m->match('/dude/what/login'));

        $this->assertNull($m->match('/admin/user'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'view',
                           'url' => 'books/learning_python.pdf');
        $this->assertEquals($matchdata, $m->match('/books/learning_python.pdf/admin/user'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'view', 'url' => 'dude');
        $this->assertEquals($matchdata, $m->match('/dude/admin/user'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'view', 'url' => 'dude/what');
        $this->assertEquals($matchdata, $m->match('/dude/what/admin/user'));        
    }
    
    public function testController()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('hi/:controller', array('action' => 'hi'));
        $m->createRegs(array('content', 'admin/user'));
        
        $this->assertNull($m->match('/boo'));
        $this->assertNull($m->match('/boo/blah'));
        $this->assertNull($m->match('/hi/13870948'));
        $this->assertNull($m->match('/hi/content/dog'));
        $this->assertNull($m->match('/hi/admin/user/foo'));
        $this->assertNull($m->match('/hi/admin/user/foo/'));

        $matchdata = array('controller' => 'content', 'action' => 'hi');
        $this->assertEquals($matchdata, $m->match('/hi/content'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'hi');
        $this->assertEquals($matchdata, $m->match('/hi/admin/user'));
    }
    
    public function testStandardRoute()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'admin/user'));
        
        $matchdata = array('controller' => 'content', 'action' => 'index', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/content'));
        
        $matchdata = array('controller' => 'content', 'action' => 'list', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/content/list'));
        
        $matchdata = array('controller' => 'content', 'action' => 'show', 'id' => '10');
        $this->assertEquals($matchdata, $m->match('/content/show/10'));

        $matchdata = array('controller' => 'admin/user', 'action' => 'index', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/admin/user'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'list', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/admin/user/list'));
                
        $matchdata = array('controller' => 'admin/user', 'action' => 'show', 'id' => 'bbangert');
        $this->assertEquals($matchdata, $m->match('/admin/user/show/bbangert'));
        
        $this->assertNull($m->match('/content/show/10/20'));
        $this->assertNull($m->match('/food'));
    }

    public function testStandardRouteWithGaps()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':controller/:action/:(id).py');
        $m->createRegs(array('content', 'admin/user'));

        $matchdata = array('controller' => 'content', 'action' => 'index', 'id' => 'None');
        $this->assertEquals($matchdata, $m->match('/content/index/None.py'));
        
        $matchdata = array('controller' =>'content', 'action' => 'list', 'id' => 'None');
        $this->assertEquals($matchdata, $m->match('/content/list/None.py'));    

        $matchdata = array('controller' => 'content', 'action' => 'show', 'id' => '10');
        $this->assertEquals($matchdata, $m->match('/content/show/10.py'));    
    }

    public function testStandardRouteWithGapsAndDomains()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('manage/:domain.:ext', array('controller' => 'admin/user', 'action' => 'view', 
                                                 'ext' => 'html'));
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content', 'admin/user'));

        $matchdata = array('controller' => 'content', 'action' => 'index', 'id' => 'None.py');
        $this->assertEquals($matchdata, $m->match('/content/index/None.py'));

        $matchdata = array('controller' => 'content', 'action' => 'list', 'id' => 'None.py');
        $this->assertEquals($matchdata, $m->match('/content/list/None.py'));

        $matchdata = array('controller' => 'content', 'action' => 'show', 'id' => '10.py');
        $this->assertEquals($matchdata, $m->match('/content/show/10.py'));
 
        $matchdata = array('controller' => 'content', 'action' => 'show.all', 'id' => '10.py');
        $this->assertEquals($matchdata, $m->match('/content/show.all/10.py'));
 
        $matchdata = array('controller' => 'content', 'action' => 'show', 'id' => 'www.groovie.org');
        $this->assertEquals($matchdata, $m->match('/content/show/www.groovie.org'));

        $matchdata = array('controller' => 'admin/user', 'action' => 'view', 
                           'ext' => 'html', 'domain' => 'groovie');
        $this->assertEquals($matchdata, $m->match('/manage/groovie'));
        
        $matchdata = array('controller' => 'admin/user', 'action' => 'view', 
                           'ext' => 'xml', 'domain' => 'groovie');
        $this->assertEquals($matchdata, $m->match('/manage/groovie.xml'));        
    }
    
    public function testStandardWithDomains()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('manage/:domain', array('controller' => 'domains', 'action' => 'view'));
        $m->createRegs(array('domains'));

        $matchdata = array('controller' => 'domains', 'action' => 'view', 'domain' => 'www.groovie.org');
        $this->assertEquals($matchdata, $m->match('/manage/www.groovie.org'));
    }

    public function testDefaultRoute()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('', array('controller' => 'content', 'action' => 'index'));
        $m->createRegs(array('content'));

        $this->assertNull($m->match('/x'));
        $this->assertNull($m->match('/hello/world'));
        $this->assertNull($m->match('/hello/world/how/are'));
        $this->assertNull($m->match('/hello/world/how/are/you/today'));
        
        $matchdata = array('controller' => 'content', 'action' => 'index');
        $this->assertEquals($matchdata, $m->match('/'));
    }

    public function testDynamicWithPrefix()
    {
        $m = new Horde_Routes_Mapper();
        $m->prefix = '/blog';
        $m->connect(':controller/:action/:id');
        $m->connect('', array('controller' => 'content', 'action' => 'index'));
        $m->createRegs(array('content', 'archive', 'admin/comments'));

        $this->assertNull($m->match('/x'));
        $this->assertNull($m->match('/admin/comments'));
        $this->assertNull($m->match('/content/view'));
        $this->assertNull($m->match('/archive/view/4'));

        $matchdata = array('controller' => 'content', 'action' => 'index');
        $this->assertEquals($matchdata, $m->match('/blog'));

        $matchdata = array('controller' => 'content', 'action' => 'index', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/content'));

        $matchdata = array('controller' => 'admin/comments', 'action' => 'view', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/admin/comments/view'));
        
        $matchdata = array('controller' => 'archive', 'action' => 'index', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/archive'));
        
        $matchdata = array('controller' => 'archive', 'action' => 'view', 'id' => '4');
        $this->assertEquals($matchdata, $m->match('/blog/archive/view/4'));
    }

    public function testDynamicWithMultipleAndPrefix()
    {
        $m = new Horde_Routes_Mapper();
        $m->prefix = '/blog';
        $m->connect(':controller/:action/:id');
        $m->connect('home/:action', array('controller' => 'archive'));
        $m->connect('', array('controller' => 'content'));
        $m->createRegs(array('content', 'archive', 'admin/comments'));
        
        $this->assertNull($m->match('/x'));
        $this->assertNull($m->match('/admin/comments'));
        $this->assertNull($m->match('/content/view'));
        $this->assertNull($m->match('/archive/view/4'));
        
        $matchdata = array('controller' => 'content', 'action' => 'index');
        $this->assertEquals($matchdata, $m->match('/blog/'));

        $matchdata = array('controller' => 'archive', 'action' => 'view');
        $this->assertEquals($matchdata, $m->match('/blog/home/view'));

        $matchdata = array('controller' => 'content', 'action' => 'index', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/content'));

        $matchdata = array('controller' => 'admin/comments', 'action' => 'view', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/admin/comments/view'));

        $matchdata = array('controller' => 'archive', 'action' => 'index', 'id' => null);
        $this->assertEquals($matchdata, $m->match('/blog/archive'));

        $matchdata = array('controller' => 'archive', 'action' => 'view', 'id' => '4');
        $this->assertEquals($matchdata, $m->match('/blog/archive/view/4'));
    }

    public function testSplitsWithExtension()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('hi/:(action).html', array('controller' => 'content'));
        $m->createRegs();
        
        $this->assertNull($m->match('/boo'));
        $this->assertNull($m->match('/boo/blah'));
        $this->assertNull($m->match('/hi/dude/what'));
        $this->assertNull($m->match('/hi'));

        $matchdata = array('controller' => 'content', 'action' => 'index');
        $this->assertEquals($matchdata, $m->match('/hi/index.html'));
        
        $matchdata = array('controller' => 'content', 'action' => 'dude');
        $this->assertEquals($matchdata, $m->match('/hi/dude.html'));
    }

    public function testSplitsWithDashes()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('archives/:(year)-:(month)-:(day).html', 
                    array('controller' => 'archives', 'action' => 'view'));
        $m->createRegs();            

        $this->assertNull($m->match('/boo'));
        $this->assertNull($m->match('/archives'));

        $matchdata = array('controller' => 'archives', 'action' => 'view',
                           'year' => '2004', 'month' => '12', 'day' => '4');
        $this->assertEquals($matchdata, $m->match('/archives/2004-12-4.html'));
        
        $matchdata = array('controller' => 'archives', 'action' => 'view',
                           'year' => '04', 'month' => '10', 'day' => '4');
        $this->assertEquals($matchdata, $m->match('/archives/04-10-4.html'));
        
        $matchdata = array('controller' => 'archives', 'action' => 'view',
                           'year' => '04', 'month' => '1', 'day' => '1');
        $this->assertEquals($matchdata, $m->match('/archives/04-1-1.html'));
    }
    
    public function testSplitsPackedWithRegexps()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('archives/:(year):(month):(day).html',
                    array('controller' => 'archives', 'action' => 'view',
                          'requirements' => array('year' => '\d{4}', 'month' => '\d{2}', 
                                                  'day'  => '\d{2}')));
        $m->createRegs();
        
        $this->assertNull($m->match('/boo'));
        $this->assertNull($m->match('/archives'));
        $this->assertNull($m->match('/archives/2004020.html'));
        $this->assertNull($m->match('/archives/200502.html'));        

        $matchdata = array('controller' => 'archives', 'action' => 'view',
                           'year' => '2004', 'month' => '12', 'day' => '04');
        $this->assertEquals($matchdata, $m->match('/archives/20041204.html'));
        
        $matchdata = array('controller' => 'archives', 'action' => 'view',
                           'year' => '2005', 'month' => '10', 'day' => '04');
        $this->assertEquals($matchdata, $m->match('/archives/20051004.html'));
        
        $matchdata = array('controller' => 'archives', 'action' => 'view',
                           'year' => '2006', 'month' => '01', 'day' => '01');
        $this->assertEquals($matchdata, $m->match('/archives/20060101.html'));
    }

    public function testSplitsWithSlashes()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':name/:(action)-:(day)', array('controller' => 'content'));

        $this->assertNull($m->match('/something'));
        $this->assertNull($m->match('/something/is-'));

        $matchdata = array('controller' => 'content', 'action' => 'view', 
                           'day' => '3', 'name' => 'group');
        $this->assertEquals($matchdata, $m->match('/group/view-3'));

        $matchdata = array('controller' => 'content', 'action' => 'view',
                           'day' => '5', 'name' => 'group');
        $this->assertEquals($matchdata, $m->match('/group/view-5'));
    }

    public function testSplitsWithSlashesAndDefault()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':name/:(action)-:(id)', array('controller' => 'content'));
        $m->createRegs();

        $this->assertNull($m->match('/something'));
        $this->assertNull($m->match('/something/is'));

        $matchdata = array('controller' => 'content', 'action' => 'view',
                           'id' => '3', 'name' => 'group');
        $this->assertEquals($matchdata, $m->match('/group/view-3'));
        
        $matchdata = array('controller' => 'content', 'action' => 'view',
                           'id' => null, 'name' => 'group');
        $this->assertEquals($matchdata, $m->match('/group/view-'));
    }
    
    public function testNoRegMake()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':name/:(action)-:(id)', array('controller' => 'content'));
        $m->controllerScan = false;
        try {
            $m->match('/group/view-3');
            $this->fail();
        } catch (Horde_Routes_Exception $e) {
            $this->assertRegExp('/must generate the regular expressions/i', $e->getMessage());
        }
    }
    
    public function testRoutematch()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':controller/:action/:id');
        $m->createRegs(array('content'));
        $route = $m->matchList[0];
        
        list($resultdict, $resultObj) = $m->routematch('/content');
        
        $this->assertEquals(array('controller' => 'content', 'action' => 'index', 'id' => null),
                            $resultdict);
        $this->assertSame($route, $resultObj);
        $this->assertNull($m->routematch('/nowhere'));
    }
    
    public function testRoutematchDebug()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect(':controller/:action/:id');
        $m->debug = true;
        $m->createRegs(array('content'));
        $route = $m->matchList[0];
        
        list($resultdict, $resultObj, $debug) = $m->routematch('/content');

        $this->assertEquals(array('controller' => 'content', 'action' => 'index', 'id' => null),
                            $resultdict);        
        $this->assertSame($route, $resultObj);

        list($resultdict, $resultObj, $debug) = $m->routematch('/nowhere');
        $this->assertNull($resultdict);
        $this->assertNull($resultObj);
        $this->assertEquals(1, count($debug));
    }
    
    public function testMatchDebug()
    {
        $m = new Horde_Routes_Mapper();
        $m->connect('nowhere', 'http://nowhere.com/', array('_static' => true));
        $m->connect(':controller/:action/:id');
        $m->debug = true;
        $m->createRegs(array('content'));
        $route = $m->matchList[1];

        list($resultdict, $resultObj, $debug) = $m->match('/content');
        $this->assertEquals(array('controller' => 'content', 'action' => 'index', 'id' => null),
                            $resultdict);
        
        $this->assertSame($route, $resultObj);
        
        list($resultdict, $resultObj, $debug) = $m->match('/nowhere');
        $this->assertNull($resultdict);
        $this->assertNull($resultObj);
        $this->assertEquals(2, count($debug));
    }

    public function testResourceCollection()
    {
        $m = new Horde_Routes_Mapper();
        $m->resource('message', 'messages');
        $m->createRegs(array('messages'));

        $path = '/messages';

        $m->environ = array('REQUEST_METHOD' => 'GET');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'index'),
                            $m->match($path));

        $m->environ = array('REQUEST_METHOD' => 'POST');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'create'),
                            $m->match($path));
    }

    public function testFormattedResourceCollection()
    {
        $m = new Horde_Routes_Mapper();
        $m->resource('message', 'messages');
        $m->createRegs(array('messages'));

        $path = '/messages.xml';

        $m->environ = array('REQUEST_METHOD' => 'GET');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'index', 
                                  'format' => 'xml'),
                            $m->match($path));

        $m->environ = array('REQUEST_METHOD' => 'POST');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'create', 
                                  'format' => 'xml'),
                            $m->match($path));    
    }
    
    public function testResourceMember()
    {
        $m = new Horde_Routes_Mapper();
        $m->resource('message', 'messages');
        $m->createRegs(array('messages'));

        $path = '/messages/42';

        $m->environ = array('REQUEST_METHOD' => 'GET');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'show', 
                                  'id' => 42),
                            $m->match($path));

        $m->environ = array('REQUEST_METHOD' => 'POST');
        $this->assertNull($m->match($path));
        
        $m->environ = array('REQUEST_METHOD' => 'PUT');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'update', 
                                  'id' => 42),
                            $m->match($path));                            

        $m->environ = array('REQUEST_METHOD' => 'DELETE');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'delete', 
                                  'id' => 42),
                            $m->match($path));
    }
    
    public function testFormattedResourceMember()
    {
        $m = new Horde_Routes_Mapper();
        $m->resource('message', 'messages');
        $m->createRegs(array('messages'));

        $path = '/messages/42.xml';

        $m->environ = array('REQUEST_METHOD' => 'GET');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'show', 
                                  'id' => 42, 'format' => 'xml'),
                            $m->match($path));

        $m->environ = array('REQUEST_METHOD' => 'POST');
        $this->assertNull($m->match($path));

        $m->environ = array('REQUEST_METHOD' => 'PUT');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'update', 
                                  'id' => 42, 'format' => 'xml'),
                            $m->match($path));                            

        $m->environ = array('REQUEST_METHOD' => 'DELETE');
        $this->assertEquals(array('controller' => 'messages', 'action' => 'delete', 
                                  'id' => 42, 'format' => 'xml'),
                            $m->match($path));
    }

}
