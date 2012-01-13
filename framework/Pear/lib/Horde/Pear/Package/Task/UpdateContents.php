<?php
/**
 * Updates the content listings of a package.xml file.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Updates the content listings of a package.xml file.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Task_UpdateContents
implements Horde_Pear_Package_Task
{
    /**
     * The package.xml handler.
     *
     * @var Horde_Pear_Package_Xml
     */
    private $_xml;

    /**
     * The contents handler.
     *
     * @var Horde_Pear_Package_Contents
     */
    private $_content;

    /**
     * Additional options.
     *
     * @var array
     */
    private $_options;

    /**
     * Constructor.
     *
     * @param Horde_Pear_Package_Xml      $xml     The package.xml file to
     *                                             operate on.
     * @param Horde_Pear_Package_Contents $content The content list.
     * @param array                       $options Additional options.
     */
    public function __construct(Horde_Pear_Package_Xml $xml,
                                Horde_Pear_Package_Contents $content = null,
                                $options = array())
    {
        $this->_xml = $xml;
        $this->_options = $options;
        if ($content === null) {
            $this->_content = $this->_xml->getContent();
        } else {
            $this->_content = $content;
        }
    }

    /**
     * Execute the task.
     *
     * @return NULL
     */
    public function run()
    {
        $contents = $this->_xml->findNode('/p:package/p:contents/p:dir');
        if ($contents && !empty($this->_options['regenerate'])) {
            $contents = $this->_xml->findNode('/p:package/p:contents');
            $this->_xml->removeWhitespace($contents->previousSibling);
            $this->_xml->findNode('/p:package')->removeChild($contents);
            $contents = false;
        }

        $filelist = $this->_xml->findNode('/p:package/p:phprelease/p:filelist');
        if ($filelist && !empty($this->_options['regenerate'])) {
            $filelist = $this->_xml->findNode('/p:package/p:phprelease');
            $this->_xml->removeWhitespace($filelist->previousSibling);
            $this->_xml->findNode('/p:package')->removeChild($filelist);
            $filelist = false;
        }

        if (!$contents) {
            $root = $this->_xml->insert(
                array(
                    'contents' => array(),
                    "\n ",
                ),
                $this->_xml->findNode('/p:package/p:dependencies')
            );
            $contents = $this->_xml->append(
                array(
                    "\n  ",
                    'dir' => array('baseinstalldir' => '/', 'name' => '/'),
                    ' ',
                    $this->_xml->createComment(' / '),
                    "\n ",
                ),
                $root
            );
            $this->_xml->append("\n  ", $contents);
        }

        if (!$filelist) {
            $root = $this->_xml->insert(
                array(
                    'phprelease' => array(),
                    "\n ",
                ),
                $this->_xml->findNode('/p:package/p:changelog')
            );

            $filelist = $this->_xml->append(
                array(
                    "\n  ",
                    'filelist' => array(),
                    "\n ",
                ),
                $root
            );
            $this->_xml->append("\n  ", $filelist);
        }

        $current = $this->_xml->createContents($this->_xml, $contents, $filelist);
        $current->update($this->_content);
        try {
            if (empty($this->_options['no_timestamp'])) {
                $this->_xml->timestamp();
            }
            $this->_xml->syncCurrentVersion();
        } catch (Horde_Pear_Exception $e) {
            /**
             * Ignore errors in this operation as it is not mandatory for
             * updating the file list.
             */
        }
    }

}