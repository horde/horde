<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based Horde_Mime_Viewer factory for IMP drivers.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_MimeViewer extends Horde_Core_Factory_MimeViewer
{
    /**
     * Temporary storage for IMP_Contents object.
     *
     * @var IMP_Contents
     */
    private $_contents;

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
     * @param Horde_Mime_Part $mime  An object with the data to be rendered.
     * @param array $opts            Additional options:
     * <pre>
     *   - contents: (IMP_Contents) Object associated with $mime.
     *   - type: (string) The MIME type to use for loading.
     * </pre>
     *
     * @return Horde_Mime_Viewer_Base  The newly created instance.
     * @throws Horde_Mime_Viewer_Exception
     */
    public function create(Horde_Mime_Part $mime, array $opts = array())
    {
        $opts = array_merge(array(
            'contents' => null,
            'type' => null
        ), $opts);

        $sig = implode('|', array(
            spl_object_hash($mime),
            $opts['contents'] ? spl_object_hash($opts['contents']) : '',
            strval($opts['type'])
        ));

        if (!isset($this->_instances[$sig])) {
            $this->_contents = $opts['contents'];
            $this->_instances[$sig] = parent::create($mime, array_filter(array(
                'app' => 'imp',
                'type' => $opts['type']
            )));
            unset($this->_contents);
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
        return $this->create($mime, array(
            'contents' => $viewer->getConfigParam('imp_contents'),
            'type' => $type
        ));
    }

    /**
     */
    public function getViewerConfig($type, $app)
    {
        list($driver, $params) = parent::getViewerConfig($type, $app);

        switch ($driver) {
        case 'Horde_Mime_Viewer_Report':
        case 'Horde_Mime_Viewer_Security':
        case 'Report':
        case 'Security':
            $params['viewer_callback'] = array($this, 'createCallback');
            break;
        }

        $params['imp_contents'] = $this->_contents;

        return array($driver, $params);
    }

}
