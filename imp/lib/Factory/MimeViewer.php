<?php
/**
 * A Horde_Injector:: based Horde_Mime_Viewer factory for IMP drivers.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  IMP
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 */

/**
 * A Horde_Injector:: based Horde_Mime_Viewer factory for IMP drivers.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @package  IMP
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 */
class IMP_Factory_MimeViewer extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Attempts to return a concrete Horde_Mime_Viewer object based on the
     * MIME type.
     *
     * @param Horde_Mime_Part $mime   An object with the data to be rendered.
     * @param IMP_Contents $contents  The IMP_Contents object associated with
     *                                $mime.
     * @param string $type            The MIME type to use for loading.
     *
     * @return Horde_Mime_Viewer_Base  The newly created instance.
     * @throws Horde_Mime_Viewer_Exception
     */
    public function create(Horde_Mime_Part $mime,
                           IMP_Contents $contents = null, $type = null)
    {
        $sig = implode('|', array(
            spl_object_hash($mime),
            spl_object_hash($contents),
            strval($type)
        ));

        if (!isset($this->_instances[$sig])) {
            list($driver, $params) = $this->_injector->getInstance('Horde_Core_Factory_MimeViewer')->getViewerConfig($type ? $type : $mime->getType(), 'imp');

            switch ($driver) {
            case 'Report':
            case 'Security':
                $params['viewer_callback'] = array($this, 'createCallback');
                break;
            }

            $params['imp_contents'] = $contents;

            $this->_instances[$sig] = Horde_Mime_Viewer::factory($driver, $mime, $params);
        }

        return $this->_instances[$sig];
    }

    /**
     * Callback used to return a MIME Viewer object from within certain
     * Viewer drivers.
     *
     * @param Horde_Mime_Viewer_Base $viewer  The MIME Viewer driver
     *                                        requesting the new object.
     * @param Horde_Mime_Part $mime           An object with the data to be
     *                                        rendered.
     * @param string $type                    The MIME type to use for
     *                                        rendering.
     *
     * @return Horde_Mime_Viewer_Base  The newly created instance.
     * @throws Horde_Mime_Viewer_Exception
     */
    public function createCallback(Horde_Mime_Viewer_Base $viewer,
                                   Horde_Mime_Part $mime, $type)
    {
        return $this->create($mime, $viewer->getConfigParam('imp_contents'), $type);
    }

}
