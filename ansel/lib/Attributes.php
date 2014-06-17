<?php

class Ansel_Attributes
{

    protected $_exif;
    protected $_imageExif;

    public function getImageExifData($imageFile)
    {
        try {
            $this->_imageExif = $this->_getExifDriver()->getData($imageFile);
            return $this->_imageExif;
        } catch (Horde_Image_Exception $e) {
            throw new Ansel_Exception($e->getMessage());
        }
    }

    public function getTitle()
    {
        foreach (array_keys(Horde_Image_Exif::getTitleFields()) as $field) {
            if (!empty($this->_imageExif[$field])) {
                return $this->_imageExif[$field];
            }
        }

        return false;
    }

    public function getCaption()
    {
        foreach (array_keys(Horde_Image_Exif::getDescriptionFields()) as $field) {
            if (!empty($this->_imageExif[$field])) {
                return $this->_imageExif[$field];
            }
        }

        return false;
    }

    protected function _getExifDriver()
    {
        global $conf, $injector;

        if (empty($this->_exif)) {
            $params = !empty($conf['exif']['params']) ?
                    $conf['exif']['params'] :
                    array();
            $params['logger'] = $injector->getInstance('Horde_Log_Logger');
            $this->_exif = Horde_Image_Exif::factory(
                $GLOBALS['conf']['exif']['driver'],
                $params
            );
        }

        return $this->_exif;
    }

}