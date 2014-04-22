/**
 * layout.js - Core layout logic for the responsive ajax view. Provides an
 * implementation of a Justified Grid layout. Essentially:
 *   1) Take a set of images with a height that is at least some set max height.
 *   2) Calculate the scale factor ratio needed to bring each image down to the
 *      max height, and calculate the scaled widths of each image.
 *   3) Determine the images that can fit in the specified div's  width, while
 *      tracking the accumulated width, then calculate the ratio of the actual
 *      div width compared to the available/max div width.
 *   4) Use the ratio from 3 to resize the row and images to fill the
 *      entire row.
 *
 * @TODO: On-demand image loading/pagination.
 *        Refetch higher res images when viewport is scaled up.
 *
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */
AnselLayout = {

    // Wrapper element that contains the gallery display:
    // <div id="anselSizer"></div>
    // <div id="anselLayoutMain">
    //   <div class="anselRow"</div>
    //   <div class="anselRow"></div>
    // </div>
    container: 'anselLayoutMain',

    // CSS Selector of hidden row used to determine row width.
    hiddenDiv: 'anselSizer',

    // CSS selector of a single row.
    rowSelector: '.anselRow',

    // An array of image objects to be displayed.
    // [{id: xx, width_s: xx, height_s: xx }]
    images: [],

    // An array of galleries to display.
    galleries: [],

    // Used internally to calculate viewport size changes.
    lastWidth: 0,

    // Maximum height (in px) for thumbnails.
    maxHeight: 300,

    // Border width, in px.
    border: 4,

    reset: function()
    {
        $(AnselLayout.container).select(AnselLayout.rowSelector).each(function(r) {
            r.update();
        });
    },

    resize: function()
    {
        AnselLayout.lastWidth = $(AnselLayout.hiddenDiv).getWidth();
        $(AnselLayout.container).select(AnselLayout.rowSelector).each(function(r) {
            r.width = AnselLayout.lastWidth;
        });
        AnselLayout.process();
    },

    process: function()
    {
        var rows = $(AnselLayout.container).select(AnselLayout.rowSelector),
            rowWidth = $(AnselLayout.hiddenDiv).getWidth(), scaledWidths = [],
            baseLine = 0, imgBaseLine = 0, rowNum = 0, newRow;

        if (!AnselLayout.images.length && !AnselLayout.galleries.size()) {
            return;
        }

        // Gallery key images are always the same size, make sure
        // we account for any we want to display.
        AnselLayout.galleries.each(function(pair) {
            scaledWidths.push(Ansel.conf.style['gallery-width']);
        });

        // Calculate scaled image heights
        // @TODO, request newly sized images for certain size thresholds.
        AnselLayout.images.each(function(im) {
            var wt = parseInt(im.width_s, 10),
                ht = parseInt(im.height_s, 10);
            if (ht != AnselLayout.maxHeight) {
                wt = Math.floor(wt *  (AnselLayout.maxHeight / ht));
            }
            scaledWidths.push(wt);
        });

        while (rowNum++ < rows.length) {
            // imgCntRow =   Number of images in this row.
            // totalCntRow = Number of tiles in the current row.
            // imgNumber =   The current image we are handling for the current
            //               row.
            // totalNumber = The current tile (image OR gallery) we are handling
            //               for the current row.
            // totalWidth =  The total width of the current row.
            // ratio =       The scale ratio.
            // newht =       The new height of the scaled row.
            var d_row = rows[rowNum - 1], imgCntRow = 0, totalCntRow = 0,
                imgNumber = 0, totalWidth = 0, totalNumber = 0, ratio, newht;

            d_row.update();

            // calculate width of images and number of images to view in this row.
            while (totalWidth * 1.1 < rowWidth) {
                if (baseLine + totalCntRow >= scaledWidths.length) {
                    break;
                }
                totalWidth += scaledWidths[baseLine + totalCntRow++] + AnselLayout.border * 2;
            }

            // Ratio of actual width of row to total width of images to be used.
            ratio = rowWidth / totalWidth;

            // Reset, this will hold the totalWidth of the processed images.
            totalWidth = 0;

            // new height
            newht = Math.floor(AnselLayout.maxHeight * ratio);

            // Fill the row with the images we know can fit. Start with any
            // gallery tiles we decided to show.
            while (totalNumber < totalCntRow && (totalNumber + baseLine) < AnselLayout.galleries.size()) {
                var keyImage = AnselLayout.galleries.get(totalNumber + 1).ki,
                    newwt = Math.floor(scaledWidths[baseLine + totalNumber] * ratio);

                totalWidth += newwt + AnselLayout.border * 2;
                // Create and insert image into current row.
                (function() {
                    var img = new Element('img',
                        {
                            class: 'ansel-photo',
                            src: keyImage,
                            width: newwt,
                            height: newht
                        }).setStyle({margin: AnselLayout.border + "px"});

                    // When ratio >= 1, we didn't have enough images to finish
                    // out the row. Set the height to the maximum we can and
                    // let the browser do the width scale.
                    if (ratio >= 1) {
                        img.style.width = 'auto';
                        img.style.height = Math.min(AnselLayout.maxHeight, Ansel.conf.style['gallery-width']) + 'px';
                    }
                    d_row.insert(img);
                })();
                totalNumber++;
            }

            // Move on to images?
            while (totalNumber < totalCntRow && (imgNumber + imgBaseLine) <= AnselLayout.images.length) {
                var photo = AnselLayout.images[imgBaseLine + imgNumber],
                    newwt = Math.floor(scaledWidths[baseLine + totalNumber] * ratio);

                // Add border, and new image width to accumulated width.
                totalWidth += newwt + AnselLayout.border * 2;

                // Create and insert image into current row.
                (function() {
                    var img = new Element('img',
                        {
                            class: 'ansel-photo',
                            src: photo.screen,
                            width: newwt,
                            height: newht
                        }).setStyle({margin: AnselLayout.border + "px"});

                    // When ratio >= 1, we didn't have enough images to finish
                    // out the row. Set the height to the maximum we can and
                    // let the browser do the width scale.
                    if (ratio >= 1) {
                        img.style.width = 'auto';
                        img.style.height = Math.min(AnselLayout.maxHeight, photo.height_s) + 'px';
                    }

                    d_row.insert(img);
                })();
                imgNumber++;
                totalNumber++;
                imgCntRow++;
            }

            // if total width is slightly smaller than
            // actual div width then add 1 to each
            // photo width till they match
            totalNumber = 0;
            while (totalWidth < rowWidth) {
                var imgs = d_row.select('img:nth-child(' + (totalNumber + 1) + ')');
                if (!imgs[0]) {
                    break;
                }
                imgs[0].width = imgs[0].getWidth() + 1;
                totalNumber = (totalNumber + 1) % totalCntRow;
                totalWidth++;
            }

            // if total width is slightly bigger than
            // actual div width then subtract 1 from each
            // photo width till they match
            totalNumber = 0;
            while (totalWidth > rowWidth) {
                var imgs = d_row.select('img:nth-child(' + (totalNumber + 1) + ')');
                if (!imgs[0]) {
                    break;
                }
                imgs[0].width = imgs[0].getWidth() - 1;
                totalNumber = (totalNumber + 1) % totalCntRow;
                totalWidth--;
            }

            // set row height to actual height + margins
            d_row.height = newht + AnselLayout.border * 2;
            baseLine += totalCntRow;
            imgBaseLine += imgCntRow;

            if (rowNum == rows.length && baseLine < (AnselLayout.images.length + AnselLayout.galleries.size())) {
                newRow = d_row.clone();
                $(AnselLayout.container).insert(newRow);
                rows.push(newRow);
            }
        }
    },

    // Handlers
    // Trigger a resize when the screen changes by 10%
    onResize: function()
    {
        var currentWidth = $(AnselLayout.hiddenDiv).getWidth();
       // if (currentWidth * 1.1 < AnselLayout.lastWidth || currentWidth * 0.9 > AnselLayout.lastWidth) {
            AnselLayout.resize();
        //}
    }

}