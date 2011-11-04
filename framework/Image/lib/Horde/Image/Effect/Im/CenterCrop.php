<?php
/**
 * Image effect easily creating small, center-cropped thumbnails.
 * Requires IM version 6.3.8-3 or greater.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image_Effect_Im_CenterCrop extends Horde_Image_Effect
{
    /**
     * Valid parameters:
     *  <pre>
     *    width    - Target width
     *    height   - Target height
     * </pre>
     *
     * @var array
     */
    protected $_params = array();

    public function apply()
    {
        $this->_params = new Horde_Support_Array($this->_params);
        $ver = $this->_image->getIMVersion();
        if (is_array($ver) && version_compare($ver[0], '6.3.8') < 0) {
            $initialCrop = $this->_params->width * 2;
            $command = "-resize x{$initialCrop} -resize '{$initialCrop}x<' -resize 50% -gravity center -crop {$this->_params->width}x{$this->_params->height}+0+0 +repage";
        } else {
            $command = "-thumbnail {$this->_params->width}x{$this->_params->height}^ -gravity center -extent {$this->_params->width}x{$this->_params->height}";
        }
        $this->_image->addPostSrcOperation($command);
        $this->_image->clearGeometry();

        return;
    }

}