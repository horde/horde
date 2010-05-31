<?php
/**
 * Face_detect implementation
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Faces_Facedetect extends Ansel_Faces_Base
{
    /**
     * Where the face defintions are stored
     */
    private $_defs = '';

    /**
     * Create instance
     */
    public function __construct($params)
    {
        $this->_defs = $params['defs'];
    }

    /**
     *
     */
    public function canAutogenerate()
    {
        return true;
    }

    /**
     * Get faces
     *
     * @param string $file Picture filename
     * @throws Horde_Exception
     */
    protected function _getFaces($file)
    {
        if (!Horde_Util::loadExtension('facedetect')) {
            throw new Horde_Exception('You do not have the facedetect extension enabled in PHP');
        }

        return face_detect($file, $this->_defs);
    }

    /**
     * Check if a face in is inside anoter face
     *
     * @param array $face  Face we are cheking
     * @param array $faces Existing faces
     *
     * @param int Face ID containg passed face
     */
    protected function _isInFace($face, $faces)
    {
        foreach ($faces as $id => $rect) {
            if ($face['x'] > $rect['x'] && $face['x'] + $face['w'] < $face['x'] + $rect['w']
                && $face['y'] > $rect['y'] && $face['y'] + $face['h'] < $face['y'] + $rect['h']) {
                return $id;
            }
        }

        return false;
    }

    protected function _getParamsArray($face_id, $image, $rect)
    {
        return array($face_id,
                     $image->id,
                     $image->gallery,
                     $rect['x'],
                     $rect['y'],
                     $rect['x'] + $rect['w'],
                     $rect['y'] + $rect['h']);
    }

    protected function _createView($face_id, $image, $rect)
    {
        return $this->createView($face_id,
                                $image,
                                $rect['x'],
                                $rect['y'],
                                $rect['x'] + $rect['w'],
                                $rect['y'] + $rect['h']);
    }

}
