<?php
/**
 * Image uploader. Provides 3 different options - single images,
 * multiple images, and zip file.
 *
 * @package Ansel
 */
class Ansel_Form_Upload extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct(&$vars, $title)
    {
        global $gallery, $conf;

        parent::__construct($vars, $title);

        $filesize = ini_get('upload_max_filesize');
        if (substr($filesize, -1) == 'M') {
            $filesize = $filesize * 1048576;
        }
        $filesize = $this->_get_size($filesize);

        $postsize = ini_get('post_max_size');
        if (substr($postsize, -1) == 'M') {
            $postsize = $postsize * 1048576;
        }
        $postsize = $this->_get_size($postsize);

        $this->setButtons(array(_("Upload"), _("Cancel")));
        $this->addHidden('', 'gallery', 'text', false);
        $this->addHidden('', 'page', 'text', false);

        $this->setSection('single_file', _("Single Photo"));
        $this->addHidden('', 'image0', 'text', false);
        if (!strlen($vars->get('image0'))) {
            $upload = $this->addVariable(
                _("File to upload"), 'file0', 'file', false, false,
                _("Maximum photo size:") . ' '  . $filesize, array(false));
            $upload->setHelp('upload');
        }
        $this->addVariable(_("Make this the default photo for this gallery?"), 'image0_default', 'boolean', false);
        $this->addVariable(_("Caption"), 'image0_desc', 'longtext', false, false, null, array('4', '40'));
        $this->addVariable(_("Tags"), 'image0_tags', 'text', false, false, _("Separate tags with commas."));

        $this->setSection('multi_file', _("Multiple Photos"));

        if (!strlen($vars->get('image0'))) {
            $msg = sprintf(_("Maximum photo size: %s; with a total of: %s"),
                           $filesize, $postsize);
            $this->addVariable($msg, 'description', 'description', false);
        }

        // start at $i = 1 because 0 is used above.
        for ($i = 1; $i <= $conf['image']['num_uploads']; $i++) {
            $this->addHidden('', 'image' . $i, 'text', false);
            if (!strlen($vars->get('image' . $i))) {
                $upload = $this->addVariable(sprintf(_("File %s"), $i), 'file' . $i, 'file', false, false, null, array(false));
                $upload->setHelp('upload');
            }
        }

        $this->setSection('zip_file', _("Zip File Upload"));
        $this->addHidden('', 'image' . ($conf['image']['num_uploads'] + 1), 'text', false);
        if (!strlen($vars->get('zip'))) {
            $upload = $this->addVariable(
                _("File to upload"),
                'file' . ($conf['image']['num_uploads'] + 1),
                'file', false, false,
                _("Maximum file size:") . ' ' . $filesize);
            $upload->setHelp('upload');
        }
    }

    /**
     * Format file size
     */
    protected function _get_size($size)
    {
        $bytes = array('B', 'KB', 'MB', 'GB', 'TB');

        foreach ($bytes as $val) {
            if ($size > 1024) {
                $size = $size / 1024;
            } else {
                break;
            }
        }

        return round($size, 2) . ' '  . $val;
    }

}
