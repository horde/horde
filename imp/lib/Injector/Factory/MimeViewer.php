<?php
/**
 * A Horde_Injector:: based Horde_Mime_Viewer factory for IMP drivers.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  IMP
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 */

/**
 * A Horde_Injector:: based Horde_Mime_Viewer factory for IMP drivers.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @category Horde
 * @package  IMP
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 */
class IMP_Injector_Factory_MimeViewer
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

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
    public function getViewer(Horde_Mime_Part $mime,
                              IMP_Contents $contents = null,
                              $type = null)
    {
        list($driver, $params) = $this->_injector->getInstance('Horde_Mime_Viewer')->getViewerConfig($type ? $type : $mime->getType(), 'imp');

        switch ($driver) {
        case 'Report':
        case 'Security':
            $params['viewer_callback'] = array($this, 'getViewerCallback');
            break;
        }

        $params['imp_contents'] = $contents;

        return Horde_Mime_Viewer::factory($driver, $mime, $params);
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
    public function getViewerCallback(Horde_Mime_Viewer_Base $viewer,
                                      Horde_Mime_Part $mime, $type)
    {
        return $this->getViewer($mime, $viewer->getConfigParam('imp_contents'), $type);
    }

}
