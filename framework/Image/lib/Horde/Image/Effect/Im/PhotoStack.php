<?php
/**
 * Effect for composing multiple images into a single image.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * The technique for the Polaroid-like stack using the Imagick extension is
 * credited to Mikko Koppanen and is documented at http://valokuva.org
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_Im_PhotoStack extends Horde_Image_Effect
{
    /**
     * Valid parameters for the stack effect
     *
     * images           -    An array of Horde_Image objects to stack. Images
     *                       are stacked in a FIFO manner, so that the top-most
     *                       image is the last one in this array.
     *
     * type             -    Determines the style for the composition.
     *                       'plain' or 'polaroid' are supported.
     *
     * resize_height    -    The height that each individual thumbnail
     *                       should be resized to before composing on the image.
     *
     * padding          -    How much padding should we ensure is left around
     *                       the active image area?
     *
     * background       -    The background canvas color - this is used as the
     *                       color to set any padding to.
     *
     * bordercolor      -    If using type 'plain' this sets the color of the
     *                       border that each individual thumbnail gets.
     *
     * borderwidth      -    If using type 'plain' this sets the width of the
     *                       border on each individual thumbnail.
     *
     * offset           -    If using type 'plain' this determines the amount of
     *                       x and y offset to give each successive image when
     *                       it is placed on the top of the stack.
     *
     * @var array
     */
    protected $_params = array('type' => 'plain',
                               'resize_height' => '150',
                               'padding' => 0,
                               'background' => 'none',
                               'bordercolor' => '#333',
                               'borderwidth' => 1,
                               'borderrounding' => 10,
                               'offset' => 5);

    /**
     * Create the photo_stack
     *
     */
    public function apply()
    {
        $i = 1;
        $cnt = count($this->_params['images']);
        if ($cnt <=0) {
            throw new Horde_Image_Exception('No Images provided.');
        }

        // Start out fresh.
        $this->_image->raw();

        switch ($this->_params['type']) {
        case 'plain':
        case 'rounded':
            // Get top image dimensions, then force each bottom image to the
            // same dimensions.
            $this->_params['images'][$cnt - 1]->resize($this->_params['resize_height'],
                                                       $this->_params['resize_height'],
                                                       true);
            $size = $this->_params['images'][$cnt - 1]->getDimensions();
            $xo = $yo = (count($this->_params['images'])) * $this->_params['offset'];
            $ops = '';
            $haveBottom = false;
            foreach ($this->_params['images'] as $image) {
                $image->resize($size['height'], $size['width'], false);
                $xo -= $this->_params['offset'];
                $yo -= $this->_params['offset'];

                if ($this->_params['type'] == 'rounded') {
                    $temp = $this->_roundBorder($image);
                } else {
                    $temp = $image->toFile();
                }
                $this->_image->addFileToClean($temp);
                $ops .= ' \( ' . $temp . ' -background none -thumbnail ' . $size['width'] . 'x' . $size['height'] . '! -repage +' . $xo . '+' . $yo . ($this->_params['type'] == 'plain' ? ' -bordercolor "#333" -border 1 ' : ' ' ) . ((!$haveBottom) ? '\( +clone -shadow 80x3+4+4 \) +swap -mosaic' : '') . ' \) ';
                $haveBottom = true;
            }

            // The first -background none option below is only honored in
            // convert versions before 6.4 it seems. Without it specified as
            // none here, all stacks come out with a white background.
            $this->_image->addPostSrcOperation($ops . ' -background ' . $this->_params['background'] . ' -mosaic -bordercolor ' . $this->_params['background'] . ' -border ' . $this->_params['padding']);
            break;

        case 'polaroid':
            // Check for im version > 6.3.2
            $ver = $this->_image->getIMVersion();
            if (is_array($ver) && version_compare($ver[0], '6.3.2') >= 0) {
                $ops = '';
                foreach ($this->_params['images'] as $image) {
                    $temp = $image->toFile();
                    // Remember the temp files so we can nuke them later.
                    $this->_image->addFileToClean($temp);

                    // Don't rotate the top image.
                    if ($i++ == $cnt) {
                        $angle = 0;
                    } else {
                        $angle = mt_rand(1, 45);
                        if (mt_rand(1, 2) % 2 === 0) {
                            $angle = $angle * -1;
                        }
                    }
                    $ops .= ' \( ' . $temp . ' -geometry +' . mt_rand(1, $this->_params['resize_height']) . '+' . mt_rand(1, $this->_params['resize_height']) . ' -thumbnail \'' . $this->_params['resize_height'] . 'x' . $this->_params['resize_height'] . '>\' -bordercolor Snow -border 1 -polaroid ' . $angle . ' \) ';
                }
                $this->_image->addPostSrcOperation('-background none' . $ops . '-mosaic -bordercolor ' . $this->_params['background'] . ' -border ' . $this->_params['padding']);
            } else {
                // An attempt at a -polaroid command free version of this
                // effect based on various examples and ideas at
                // http://imagemagick.org
                $ops = '';
                foreach ($this->_params['images'] as $image) {
                    $temp = $image->toFile();
                    $this->_image->addFileToClean($temp);
                    if ($i++ == $cnt) {
                        $angle = 0;
                    } else {
                        $angle = mt_rand(1, 45);
                        if (mt_rand(1, 2) % 2 === 0) {
                            $angle = $angle * -1;
                        }
                    }
                    $ops .= '\( ' . $temp . ' -thumbnail \'' . $this->_params['resize_height'] . 'x' . $this->_params['resize_height']. '>\' -bordercolor "#eee" -border 4 -bordercolor grey90 -border 1 -bordercolor none -background none -rotate ' . $angle . ' -background none \( +clone -shadow 60x4+4+4 \) +swap -background none -flatten \) ';
                }
                $this->_image->addPostSrcOperation('-background none ' . $ops . '-mosaic -trim +repage -bordercolor ' . $this->_params['background'] . ' -border ' . $this->_params['padding']);
            }
            break;
        }

        return true;
    }

    private function _roundBorder($image)
    {
        $context = array('tmpdir' => $this->_image->getTmpDir(),
                         'convert' => $this->_image->getConvertPath());

        $size = $image->getDimensions();
        $new = new Horde_Image_Im(array('data' => $image->raw()), $context);
        $new->addEffect('RoundCorners', array('border' => 2, 'bordercolor' => '#111', 'background' => 'none'));
        $new->applyEffects();

        return $new->toFile();
    }

}