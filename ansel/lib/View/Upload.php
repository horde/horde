<?php
/**
 * The Ansel_View_Upload:: class provides a view for handling image uploads.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

class Ansel_View_Upload
{
    /**
     *
     * @var array
     */
    protected $_params;

    /**
     *
     * @var Ansel_Gallery
     */
    protected $_gallery;

    /**
     * Initialize the view. Needs the following parameters:
     * <pre>
     *   'browse_button' - Dom id of button to open file system browser.
     *   'target'        - Url of the target page to upload images to.
     *   'drop_target'   - Dom id of the element to receive drag and drop images
     *                     (If runtime supports it).
     *   'gallery'
     * </pre>
     * @param <type> $params
     */
    public function __construct($params)
    {
        $this->_params = $params;
        $this->_gallery = $this->_params['gallery'];
    }

    public function run()
    {
        /* Check for file upload */
        $this->_handleFileUpload();

        // TODO: Configure which runtimes to allow?
        Horde::addScriptFile('plupload/plupload.js', 'horde');
        Horde::addScriptFile('plupload/plupload.flash.js', 'horde');
        Horde::addScriptFile('plupload/plupload.silverlight.js', 'horde');
        Horde::addScriptFile('plupload/plupload.html5.js', 'horde');
        Horde::addScriptFile('plupload/plupload.browserplus.js', 'horde');
        Horde::addScriptFile('plupload/uploader.js', 'horde');
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('carousel.js', 'ansel', true);

        $startText = _("Upload");
        $addText = _("Add Image");
        $header = _("Upload to gallery");
        $returnText =_("View Gallery");

        $this->_params['target']->add('gallery', $this->_params['gallery']->id);

        $jsuri = $GLOBALS['registry']->get('jsuri', 'horde');
        $js = <<< EOT
        var uploader = new Horde_Uploader({
            'target': "{$this->_params['target']}",
            drop_target: "{$this->_params['drop_target']}",
            swf_path: '{$jsuri}/plupload/plupload.flash.swf',
            xap_path: '{$jsuri}/plupload/plupload.silverlight.xap',
            container: 'anseluploader',
            text: { start: '{$startText}',
                    add: '{$addText}',
                    header: '{$header}',
                    returnButton: '{$returnText}'
            },
            header_class: 'control header hordeUploaderHeader',
            container_class: 'uploaderContainer',
            return_target: '{$this->_params['return_target']}'
        });
        uploader.init();
EOT;

        Horde::addInlineScript($js, 'dom');
    }

    /**
     * Handle uploads from non-js browsers
     */
    public function handleNoJs()
    {
        global $conf, $notification, $browser;

        $vars = Horde_Variables::getDefaultVariables();
        $form = new Ansel_Form_Upload($vars, _("Upload photos"));

        if ($form->validate($vars)) {
            $valid = true;
            $uploaded = 0;
            $form->getInfo($vars, $info);

            /* Remember the ids of the images we uploaded so we can autogen */
            $image_ids = array();
            for ($i = 0; $i <= $conf['image']['num_uploads'] + 1; ++$i) {
                if (empty($info['file' . $i]['file'])) {
                    continue;
                }

                try {
                    $GLOBALS['browser']->wasFileUploaded('file' . $i);
                } catch (Horde_Browser_Exception $e) {
                    if (!empty($info['file' . $i]['error'])) {
                        $notification->push(sprintf(_("There was a problem uploading the photo: %s"), $info['file' . $i]['error']), 'horde.error');
                    } elseif (!filesize($info['file' . $i]['file'])) {
                        $notification->push(_("The uploaded file appears to be empty. It may not exist on your computer."), 'horde.error');
                    }
                    $valid = false;
                    continue;
                }

                /* Check for a compressed file. */
                if (in_array($info['file' . $i]['type'],
                             array('x-extension/zip',
                                   'application/x-compressed',
                                   'application/x-zip-compressed',
                                   'application/zip')) ||
                    Horde_Mime_Magic::filenameToMime($info['file' . $i]['name']) == 'application/zip') {

                    $this->_handleZip($info['file' . $i]['name']);

                } else {

                    /* Read in the uploaded data. */
                    $data = file_get_contents($info['file' . $i]['file']);

                    /* Try and make sure the image is in a recognizeable
                     * format. */
                    if (getimagesize($info['file' . $i]['file']) === false) {
                        $notification->push(_("The file you uploaded does not appear to be a valid photo."), 'horde.error');
                        continue;
                    }

                    /* Add the image to the gallery */
                    $image_data = array('image_filename' => $info['file' . $i]['name'],
                                        'image_caption' => $vars->get('image' . $i . '_desc'),
                                        'image_type' => $info['file' . $i]['type'],
                                        'data' => $data,
                                        'tags' => (isset($info['image' . $i . '_tags']) ? explode(',', $info['image' . $i . '_tags']) : array()));
                    try {
                        $image_id = $this->_gallery->addImage($image_data, (bool)$vars->get('image' . $i . '_default'));
                        ++$uploaded;
                        $image_ids[] = $image_id;
                    } catch (Ansel_Exception $e) {
                        $notification->push(sprintf(_("There was a problem saving the photo: %s"), $image_id->getMessage()), 'horde.error');
                        $valid = false;
                    }
                    unset($data);
                }
            }

            /* Try to autogenerate some views and tell the user what happened. */
            if ($uploaded) {
                $cnt = count($image_ids);
                for ($i = 0; $i < $conf['image']['autogen'] && $cnt > $i; $i++) {
                    $image_id = $image_ids[$i];
                    $image = &$GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($image_id);
                    $image->createView('screen');
                    $image->createView('thumb');
                    $image->createView('mini');
                    unset($image);
                }

                // postupload hook if needed
                try {
                    Horde::callHook('postupload', array($image_ids));
                } catch (Horde_Exception_HookNotSet $e) {}
                $notification->push(sprintf(ngettext("%d photo was uploaded.", "%d photos were uploaded.", $uploaded), $uploaded), 'horde.success');
            } elseif ($vars->get('submitbutton') != _("Cancel")) {
                $notification->push(_("You did not select any photos to upload."), 'horde.error');
            }

            if ($valid) {
                /* Return to the gallery view. */
                Ansel::getUrlFor('view',
                                 array('gallery' => $this->_gallery->getId(),
                                       'slug' => $this->_gallery->get('slug'),
                                       'view' => 'Gallery',
                                       'page' => $page),
                                 true)->redirect();
                exit;
            }
        }

        Horde::startBuffer();
        include ANSEL_TEMPLATES . '/image/upload.inc';
        return '<noscript>' . Horde::endBuffer() . '</noscript>';
    }

    /**
     * Checks for a file uploaded via the pluploader. If one is found, handle
     * it, send the server json response and exit.
     */
    protected function _handleFileUpload()
    {
        if ($filename = Horde_Util::getFormData('name')) {
            /* First, figure out the content type */
            if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
                $type = $_SERVER["HTTP_CONTENT_TYPE"];
            } elseif (isset($_SERVER["CONTENT_TYPE"])) {
                $type = $_SERVER["CONTENT_TYPE"];
            }

            if (empty($type) || $type == 'application/octet-stream') {
                $temp = Horde_Util::getTempFile('', true);
                $out = fopen($temp, 'wb');
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = fopen("php://input", "rb");
                    if ($in) {
                        while ($buff = fread($in, 4096)) {
                            fwrite($out, $buff);
                        }
                    } else {
                        fclose($out);
                        header('Content-Type: application/json');
                        echo('{"status" : "500", "file": "' . $temp. '", error" : { "message": "Failed to open input stream." } }');
                        exit;
                    }
                }

                // Don't know type. Try to deduce it.
                if (!($type = Horde_Mime_Magic::analyzeFile($temp, isset($GLOBALS['conf']['mime']['magic_db']) ? $GLOBALS['conf']['mime']['magic_db'] : null))) {
                    $type = Horde_Mime_Magic::filenameToMime($filename);
                }

            } elseif (strpos($type, "multipart") !== false) {
                // TODO:
            }

            /* Figure out what to do with the file */
            if (in_array($type, array('x-extension/zip',
                                      'application/x-compressed',
                                      'application/x-zip-compressed',
                                      'application/zip')) ||
                Horde_Mime_Magic::filenameToMime($temp) == 'application/zip') {

                /* handle zip files */
                try {
                    $image_ids = $this->_handleZip($temp);
                } catch (Ansel_Exception $e) {
                    $notification->push(sprintf(_("There was an error processing the uploaded archive: %s"), $e->getMessage()), 'horde.error');
                }

                header('Content-Type: application/json');
                echo('{ "status" : "200", "error" : {} }');
                exit;
            } else {
                /* Try and make sure the image is in a recognizeable
                 * format. */
                $data = file_get_contents($temp);
                if (getimagesize($temp) === false) {
                    header('Content-Type: application/json');
                    echo('{ "status" : "400", "error" : { "message": "Not a valid, supported image file." }, "id" : "id" }');
                    exit;
                }

                /* Add the image to the gallery */
                $image_data = array('image_filename' => $filename,
                                    //'image_caption' => $vars->get('image' . $i . '_desc'),
                                    'image_type' => $type,
                                    'data' => $data,
                                    );

                try {
                    $image_id = $this->_gallery->addImage($image_data);
                    $image_ids[] = $image_id;
                } catch (Ansel_Exception $e) {
                    header('Content-Type: application/json');
                    echo('{ "status" : "400", "error" : { "message": "Not a valid, supported image file." }, "id" : "id" }');
                    exit;
                }

                unset($data);
                header('Content-Type: application/json');
                echo('{ "status" : "200", "error" : {} }');
                exit;
            }
        }
    }

    /**
     * Indicates if the specified filename is a known meta file type.
     *
     * @param string $filename
     *
     * @return boolean
     */
    protected function _isMetaFile($filename)
    {
        /* Skip some known metadata files. */
        $len = strlen($filename);
        if ($filename[$len - 1] == '/' ||
            $filename == 'Thumbs.db' ||
            strrpos($filename, '.DS_Store') == ($len - 9) ||
            strrpos($filename, '.localized') == ($len - 10) ||
            strpos($filename, '__MACOSX/') !== false) {

            return true;
        }

        return false;
    }

    /**
     * Handle extracting images from uploaded zip files.
     *
     * @param string $filename  The local path to the zip file.
     *
     * @return array  An array of the resulting image_ids.
     * @throws Ansel_Exception
     */
    private function _handleZip($filename)
    {
        $image_ids = array();

        /* See if we can use the zip extension for reading the file. */
        if (Horde_Util::extensionExists('zip')) {
            $zip = new ZipArchive();
            if ($zip->open($filename) !== true) {
                throw new Ansel_Exception(_("Could not open zip archive."));
            }

            /* Iterate the archive */
            for ($z = 0; $z < $zip->numFiles; $z++) {
                $zinfo = $zip->statIndex($z);
                if ($this->_isMetaFile($zinfo['name'])) {
                    continue;
                }

                /* Extract the image */
                $stream = $zip->getStream($zinfo['name']);
                $zdata = stream_get_contents($stream);
                if (!strlen($zdata)) {
                    throw new Ansel_Exception(_("Could not extract image data from zip archive."));
                }

                /* Save the image */
                $image_id = $this->_gallery->addImage(array('image_filename' => $zinfo['name'],
                                                     'image_caption' => '',
                                                     'data' => $zdata));
                $image_ids[] = $image_id;
                unset($zdata);
            }
            $zip->close();
            unset($zip);
        } else {
            /* Receiving zip data, but extension not loaded */
            $data = file_get_contents($filename);

            /* Get list of images */
            try {
                $zip = Horde_Compress::factory('zip');
                $files = $zip->decompress($data, array('action' => Horde_Compress_Zip::ZIP_LIST));
            } catch (Horde_Exception $e) {
                throw new Ansel_Exception($e);
                continue;
            }

            /* Iterate the archive */
            foreach ($files as $key => $zinfo) {
                if ($this->_isMetaFile($zinfo['name'])) {
                    continue;
                }

                /* Extract the image */
                try {
                    $zdata = $zip->decompress($data,
                                              array('action' => Horde_Compress_Zip::ZIP_DATA,
                                                    'info' => $files,
                                                    'key' => $key));
                } catch (Horde_Exception $e) {
                    throw new Ansel_Exception($e);
                }

                /* Add the image */
                $image_id = $this->_gallery->addImage(array('image_filename' => $zinfo['name'],
                                                     'image_caption' => '',
                                                     'data' => $zdata));
                $image_ids[] = $image_id;
                unset($zdata);
            }
            unset($zip);
            unset($data);
        }

        return $image_ids;
    }

}
