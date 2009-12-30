<?php
/**
 * $Id$
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @package Shout
 */
// {{{
/**
 * The Shout_Dialplan:: class provides an interactive view of an Asterisk dialplan.
 * It allows for expanding/collapsing of extensions and priorities and maintains their state.
 * It can work together with the Horde_Tree javascript class to achieve this in
 * DHTML on supported browsers.
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * $Id$
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout_Dialplan
 * @since   Shout 0.1
 */
class Shout_Dialplan
{
    /**
     * The name of this instance.
     *
     * @var string
     */
    var $_instance = null;

    /**
     * The array of dialplan information to render the form
     *
     * @var array
     */
    var $_dialplan = array();

    /**
     * Object containing the instantiation of the Horde_Tree class
     *
     * @var object
     */
     var $_tree = null;

    /**
     * Create or return a unique instance of the Shout_Dialplan object
     *
     * @param string $instance Unique identifier for this instance
     * @param array $dialplan Dialplan array as returned by the driver
     * @return object Instantiation of the Shout_Dialplan object
     */
     function &singleton($instance, $dialplan)
     {
        static $instances = array();

        if (isset($instances[$instance])) {
            return $instances[$instance];
        }
        $instances[$instance] = new Shout_Dialplan($instance, $dialplan);
        return $instances[$instance];
    }

    /**
     * Instantiator for the Shout_Dialplan
     *
     * @param string $instance Unique identifier for this instance
     * @param array $dialplan Dialplan array as returned by the driver
     * @return Shout_Dialplan Instantiation of the Shout_Dialplan object
     */
    function Shout_Dialplan($instance, $dialplan)
    {
        require_once 'Horde/Tree.php';
        require_once 'Horde/Block.php';
        require_once 'Horde/Block/Collection.php';

        $this->_instance = $instance;
        $this->_dialplan = $dialplan;
        $this->_tree = Horde_Tree::singleton('shout_dialplan_nav_'.$instance, 'javascript');

        foreach ($this->_dialplan as $linetype => $linedata) {
            switch($linetype) {
                case 'extensions':
                    $url = '#top';
                    $this->_tree->addNode('extensions', null, 'Extensions', null, array('url' => $url));
                    foreach ($linedata as $extension => $priorities) {
                        $nodetext = Shout::exten2name($extension);
                        $url = Horde::applicationUrl('index.php?section=dialplan' .
                            '&extension=' . $extension . '&context=' . $this->_dialplan['name']);
                        $url = "#$extension";
                        $this->_tree->addNode("extension_".$extension, 'extensions', $nodetext,
                            null, false,
                            array(
                                'url' => $url,
                                'onclick' =>
                                    'shout_dialplan_object_'.$this->_instance.
                                        '.highlightExten(\''.$extension.'\')',
                            )
                        );
        //                 foreach ($priorities as $priority => $application) {
        //                     $this->_tree->addNode("$extension-$priority", $extension, "$priority: $application", null);
        //                 }
                    }
                    break;

                case 'includes':
                    $this->_tree->addNode('includes', null, 'Includes', null);
                    foreach ($linedata as $include) {
                        $url = Horde::applicationUrl('index.php?section=dialplan&context='.$include);
                        $this->_tree->addNode("include_$include", 'includes', $include, null,
                            true, array('url' => $url));
                    }
                    break;

                # TODO Ignoring ignorepat lines for now

                case 'barelines':
                    $this->_tree->addNode('barelines', null, 'Extra Settings', null);
                    $i = 0;
                    foreach ($linedata as $bareline) {
                        $this->_tree->addNode("bareline_".$i, 'barelines', $bareline, null);
                        $i++;
                    }
                    break;
            }
        }
    }

    /**
     * Render dialplan side navigation tree
     */
    function renderNavTree()
    {
        print '<div id=\'contextTree\'>'."\n";
        $this->_tree->renderTree(true);
        print '    <br />'."\n";
        print '    <a href="#top" class="small">Back to Top</a>'."\n";
        print '</div>'."\n";
        return true;
    }

    function generateAppList()
    {
        $applist = Shout::getApplist();
        print '<script language="JavaScript" type="text/javascript">'."\n";
        print '<!--'."\n";
        print 'var shout_dialplan_applist_'.$this->_instance.' = new Array();'."\n";

        $i = 0;
        foreach ($applist as $app => $appdata) {
            print 'shout_dialplan_applist_'.$this->_instance.'['.$i.'] = \''.$app.'\''."\n";
            $i++;
        }
        print '//-->'."\n";
        print '</script>'."\n";
        return true;
    }

    function renderExtensions()
    {
        if(!isset($this->_dialplan['extensions'])) {
            print '<div id="extensionDetail">'."\n";
            print '    <div class="extensionBox">No Configured Extensions</div>'."\n";
            print '</div>'."\n";
        } else {
            print '<script language="JavaScript" type="text/javascript"';
            print ' src="/services/javascript.php?file=dialplan.js&amp;app=shout"></script>'."\n";
            print '<script language="JavaScript" type="text/javascript">'."\n";
            print '<!--'."\n";
            print 'var shout_dialplan_entry_'.$this->_instance.' = new Array();'."\n";
            foreach($this->_dialplan['extensions'] as $extension => $priorities) {
                print 'shout_dialplan_entry_'.$this->_instance.'[\''.$extension.'\'] = new Array();'."\n";
                print 'shout_dialplan_entry_'.$this->_instance.'[\''.$extension.'\'][\'name\'] =';
                print '\''.Shout::exten2name($extension).'\';'."\n";
                print 'shout_dialplan_entry_'.$this->_instance.'[\''.$extension.'\'][\'priorities\']';
                print ' = new Array();'."\n";
                foreach($priorities as $priority => $data) {
                    print 'shout_dialplan_entry_'.$this->_instance.'[\''.$extension.'\']';
                    print '[\'priorities\']['.$priority.'] = new Array();'."\n";
                    print 'shout_dialplan_entry_'.$this->_instance.'[\''.$extension.'\']';
                    print '[\'priorities\']['.$priority.'][\'application\'] = ';
                    print '\''.$data['application'].'\';'."\n";
                    print 'shout_dialplan_entry_'.$this->_instance.'[\''.$extension.'\'][\'priorities\']';
                    print '['.$priority.'][\'args\'] = \''.$data['args'].'\';'."\n";
                }
            }
            print 'var shout_dialplan_object_'.$this->_instance.' = new Dialplan(\''.$this->_instance.'\');'."\n";
            print '//-->'."\n";
            print '</script>'."\n";

            print '<form id="shout_dialplan_form_'.$this->_instance.'" action="#">'."\n";
            print '<div id="extensionDetail">'."\n";
            $e = 0;
            foreach($this->_dialplan['extensions'] as $extension => $priorities) {
                print '<div class="extension" ';
                    print 'id="extension_'.$extension.'">';
                    print '<div class="extensionBox" ';
                        print 'id="eBox-'.$extension.'" ';
                        print 'onclick="javascript:shout_dialplan_object_'.$this->_instance.'.highlightExten';
                            print '(\''.$extension.'\');">'."\n";
                        print '<a name="'.$extension.'">'."\n";
                            print Shout::exten2name($extension);
                        print '</a>'."\n";
                    print '</div>'."\n";
                    print '<div id="pList-'.$extension.'">'."\n";
                    print '</div>'."\n";
                $e++;
                print '</div>'."\n";
                print '<br />'."\n";
                print '<script language="JavaScript" type="text/javascript">'."\n";
                print '<!--'."\n";
                print 'shout_dialplan_object_'.$this->_instance.'.drawPrioTable(\''.$extension.'\');'."\n";
                print '//-->'."\n";
                print '</script>'."\n";
            }
            print '</div>'."\n";
            print '</form>'."\n";
        }
    }
}