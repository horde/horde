<?php
/**
 * OpenCV implementation
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Faces_opencv extends Ansel_Faces
{
    /**
     * Where the face defintions are stored
     */
    private $_defs = '';

    /**
     * Create instance
     */
    function Ansel_Faces_opencv($params)
    {
        $this->_defs = $params['defs'];
    }

    /**
     * Get faces
     *
     * @param string $file Picture filename
     */
    function _getFaces($file)
    {
        $result = Horde_Util::loadExtension('opencv');
        if (!$result) {
            $err = PEAR::raiseError(_("You do not have the opencv extension enabled in PHP"));
            Horde::logMessage($err, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $err;
        }
        $im = cv_image_load($file);
        $haar = cv_object_load($this->_defs);
        $seq = cv_haar_classifier_cascade_detect_objects($haar, $im);
        $l = cv_seq_count($seq);
        Horde::logMessage(sprintf("opencv extension detected %u faces.", $l),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $rects = array();
        for ($i = 0; $i < $l; $i++) {
            $r = cv_seq_get($seq, $i);
            $rects[] = array('x' => $r['x'],
                             'y' => $r['y'],
                             'width' => $r['w'],
                             'height' => $r['h']);
        }

        return $rects;
    }

    /**
     * Check if a face in is inside anoter face
     *
     * @param array $face  Face we are cheking
     * @param array $faces Existing faces
     *
     * @param int Face ID containg passed face
     */
    function _isInFace($face, $faces)
    {
        foreach ($faces as $id => $rect) {
            if ($face['x'] > $rect['x'] && $face['x'] + $face['width'] < $face['x'] + $rect['width']
                && $face['y'] > $rect['y'] && $face['y'] + $face['height'] < $face['y'] + $rect['height']) {
                return $id;
            }
        }

        return false;
    }

    function _getParamsArray($face_id, $image, $rect)
    {
        $params = array($face_id,
                $image->id,
                $image->gallery,
                $rect['x'],
                $rect['y'],
                $rect['x'] + $rect['width'],
                $rect['y'] + $rect['height']);
       return $params;
    }

    function _createView($face_id, $image, $rect)
    {
        return $this->createView($face_id,
                                $image,
                                $rect['x'],
                                $rect['y'],
                                $rect['x'] + $rect['width'],
                                $rect['y'] + $rect['height']);
    }
}
