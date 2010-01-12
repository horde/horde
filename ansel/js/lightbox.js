// This file modified in various ways for use in Ansel. Mostly to allow
// the lightbox images to span multiple gallery pages...and thus is no longer
// really "Lightbox", but a good bit of the original code is still intact.
// The original credits/copyright appears below.

// -----------------------------------------------------------------------------------
//
//    Lightbox v2.04
//    by Lokesh Dhakar - http://www.lokeshdhakar.com
//    Last Modification: 2/9/08
//
//    For more information, visit:
//    http://lokeshdhakar.com/projects/lightbox2/
//
//    Licensed under the Creative Commons Attribution 2.5 License - http://creativecommons.org/licenses/by/2.5/
//      - Free for use in both personal and commercial projects
//        - Attribution requires leaving author name, author link, and the license info intact.
//
//  Thanks: Scott Upton(uptonic.com), Peter-Paul Koch(quirksmode.com), and Thomas Fuchs(mir.aculo.us) for ideas, libs, and snippets.
//          Artemy Tregubenko (arty.name) for cleanup and help in updating to latest ver of proto-aculous.
//
// -----------------------------------------------------------------------------------
/*

    Table of Contents
    -----------------
    Configuration

    Lightbox Class Declaration
    - initialize()
    - updateImageList()
    - start()
    - changeImage()
    - resizeImageContainer()
    - showImage()
    - updateDetails()
    - updateNav()
    - enableKeyboardNav()
    - disableKeyboardNav()
    - keyboardAction()
    - preloadNeighborImages()
    - end()

    Function Calls
    - document.observe()

*/
// -----------------------------------------------------------------------------------

var Lightbox = Class.create();

Lightbox.prototype = {
    imageArray: [],
    activeImage: undefined,
    options: undefined,

    // initialize()
    // Constructor runs on completion of the DOM loading. Calls updateImageList and then
    // the function inserts html at the bottom of the page which is used to display the shadow
    // overlay and the image container.
    //
    // Modified 3/25/2008 Michael J. Rubinsky <mrubinsk@horde.org> to remove
    // dependency on scriptaculous' Builder object since the new Element
    // constructor in Prototype does this more efficently.
    initialize: function(options) {

        this.options = options;
        this.imageArray = this.options.gallery_json;
        this.keyboardAction = this.keyboardAction.bindAsEventListener(this);
        if (this.options.resizeSpeed > 10) this.options.resizeSpeed = 10;
        if (this.options.resizeSpeed < 1)  this.options.resizeSpeed = 1;
        this.resizeDuration = this.options.animate ? ((11 - this.options.resizeSpeed) * 0.15) : 0;
        this.overlayDuration = this.options.animate ? 0.2 : 0;  // shadow fade in/out duration

        // When Lightbox starts it will resize itself from 250 by 250 to the current image dimension.
        // If animations are turned off, it will be hidden as to prevent a flicker of a
        // white 250 by 250 box.

        var size = (this.options.animate ? 250 : 1) + 'px';

        // Code inserts html at the bottom of the page that looks similar to this:
        //
        //  <div id="overlay"></div>
        //  <div id="lightbox">
        //      <div id="outerImageContainer">
        //          <div id="imageContainer">
        //              <img id="lightboxImage">
        //              <div style="" id="hoverNav">
        //                  <a href="#" id="prevLink"></a>
        //                  <a href="#" id="nextLink"></a>
        //              </div>
        //              <div id="loading">
        //                  <a href="#" id="loadingLink">
        //                      <img src="images/loading.gif">
        //                  </a>
        //              </div>
        //          </div>
        //      </div>
        //      <div id="imageDataContainer">
        //          <div id="imageData">
        //              <div id="imageDetails">
        //                  <span id="caption"></span>
        //                  <span id="numberDisplay"></span>
        //              </div>
        //              <div id="bottomNav">
        //                  <a href="#" id="bottomNavClose">
        //                      <img src="images/close.gif">
        //                  </a>
        //              </div>
        //          </div>
        //      </div>
        //  </div>


        var objBody = $$('body')[0];

        objBody.appendChild(new Element('div', {id: 'overlay'}));

        // Build innermost children
        var hoverNav = new Element('div', {id: 'hoverNav'});
          hoverNav.appendChild(new Element('a', {id:'prevLink', href: '#'}));
          hoverNav.appendChild(new Element('a', {id: 'nextLink', href: '#'}));

        var loadingLink = new Element('a', {id: 'loadingLink', href: '#'});
          loadingLink.appendChild(new Element('img', {src: this.options.fileLoadingImage}));

        var loading = new Element('div', {id: 'loading'});
          loading.appendChild(loadingLink);

        var container = new Element('div', {id: 'imageContainer'});
          container.appendChild(new Element('img', {id: 'lightboxImage'}));
          container.appendChild(hoverNav);
          container.appendChild(loading);

        var outerContainer = new Element('div', {id: 'outerImageContainer'});
          outerContainer.appendChild(container);

        var imageDetails = new Element('div', {id: 'imageDetails'});
          imageDetails.appendChild(new Element('span', {id: 'caption'}));
          imageDetails.appendChild(new Element('span', {id: 'numberDisplay'}));

        var bottomClose = new Element('a', {id: 'bottomNavClose', href: '#'});
           bottomClose.appendChild(new Element('img', {src: this.options.fileBottomNavCloseImage}));

        var bottomNav = new Element('div', {id: 'bottomNav'});
           bottomNav.appendChild(bottomClose);

        var imageData = new Element('div', {id: 'imageData'});
          imageData.appendChild(imageDetails);
          imageData.appendChild(bottomNav);

        var imageDataContainer = new Element('div', {id: 'imageDataContainer'});
           imageDataContainer.appendChild(imageData);

        // The outermost node
        var lightbox = new Element('div', {id: 'lightbox'});
          lightbox.appendChild(outerContainer);
          lightbox.appendChild(imageDataContainer);

        objBody.appendChild(lightbox);

        $('overlay').hide().observe('click', (function() { this.end(); }).bind(this));
        $('lightbox').hide().observe('click', (function(event) { if (event.element().id == 'lightbox') this.end(); }).bind(this));
        $('outerImageContainer').setStyle({ width: size, height: size });

        // Need to cache the event listener function so we can call stopObserving() later
        this.prevEventListener = (function(event) { event.stop(); this.changeImage(this.activeImage - 1); }).bindAsEventListener(this);
        $('prevLink').observe('click', this.prevEventListener);

        this.nextEventListener = (function(event) { event.stop(); this.changeImage(this.activeImage + 1); }).bindAsEventListener(this);
        $('nextLink').observe('click', this.nextEventListener);
        $('loadingLink').observe('click', (function(event) { event.stop(); this.end(); }).bind(this));
        $('bottomNavClose').observe('click', (function(event) { event.stop(); this.end(); }).bind(this));
    },

    //
    //  start()
    //  Display overlay and lightbox.
    //
    start: function(imageId) {

        $$('select', 'object', 'embed').invoke('setStyle', 'visibility:hidden');

        // stretch overlay to fill page and fade in
        var arrayPageSize = this.getPageSize();
        $('overlay').setStyle({ height: arrayPageSize[1] + 'px' });

        new Effect.Appear($('overlay'), { duration: this.overlayDuration, from: 0.0, to: this.options.overlayOpacity });

        // calculate top and left offset for the lightbox
        var arrayPageScroll = document.viewport.getScrollOffsets();
        var lightboxTop = arrayPageScroll[1] + (document.viewport.getHeight() / 15);
        var lightboxLeft = arrayPageScroll[0];
        $('lightbox').setStyle({ top: lightboxTop + 'px', left: lightboxLeft + 'px' }).show();

        // Need to find the index for this image.
        var imageNum = 0;
        while (this.imageArray[imageNum][3] != imageId || imageNum > this.imageArray.length - 1) {
            imageNum++;
        }

        this.changeImage(imageNum);
        return false;
    },

    //
    //  changeImage()
    //  Hide most elements and preload image in preparation for resizing image container.
    //
    changeImage: function(imageNum) {
        this.activeImage = imageNum; // update global var

        // hide elements during transition
        if (this.options.animate) $('loading').show();
        $('lightboxImage', 'hoverNav', 'prevLink', 'nextLink', 'numberDisplay').invoke('hide');
        // HACK: Opera9 does not currently support scriptaculous opacity and appear fx
        $('imageDataContainer').setStyle({opacity: .0001});

        var imgPreloader = new Image();

        // once image is preloaded, resize image container
        imgPreloader.onload = (function(){
            $('lightboxImage').src = this.imageArray[this.activeImage][0];
            this.resizeImageContainer(imgPreloader.width, imgPreloader.height);
        }).bind(this);
        imgPreloader.src = this.imageArray[this.activeImage][0];
    },

    //
    //  resizeImageContainer()
    //
    resizeImageContainer: function(imgWidth, imgHeight) {

        // get current width and height
        var widthCurrent  = $('outerImageContainer').getWidth();
        var heightCurrent = $('outerImageContainer').getHeight();

        // get new width and height
        var widthNew  = (imgWidth  + this.options.borderSize * 2);
        var heightNew = (imgHeight + this.options.borderSize * 2);

        // scalars based on change from old to new
        var xScale = (widthNew  / widthCurrent)  * 100;
        var yScale = (heightNew / heightCurrent) * 100;

        // calculate size difference between new and old image, and resize if necessary
        var wDiff = widthCurrent - widthNew;
        var hDiff = heightCurrent - heightNew;

        if (hDiff != 0) new Effect.Scale($('outerImageContainer'), yScale, {scaleX: false, duration: this.resizeDuration, queue: 'front'});
        if (wDiff != 0) new Effect.Scale($('outerImageContainer'), xScale, {scaleY: false, duration: this.resizeDuration, delay: this.resizeDuration});

        // if new and old image are same size and no scaling transition is necessary,
        // do a quick pause to prevent image flicker.
        var timeout = 0;
        if ((hDiff == 0) && (wDiff == 0)){
            timeout = 100;
            if (Prototype.Browser.IE) timeout = 250;
        }

        (function(){
            $('prevLink', 'nextLink').invoke('setStyle', 'height:' + imgHeight + 'px');
            $('imageDataContainer').setStyle({ width: widthNew + 'px' });
            this.showImage();
        }).bind(this).delay(timeout / 1000);
    },

    //
    //  showImage()
    //  Display image and begin preloading neighbors.
    //
    showImage: function(){
        $('loading').hide();
        new Effect.Appear($('lightboxImage'), {
            duration: this.resizeDuration,
            queue: 'end',
            afterFinish: (function(){ this.updateDetails(); }).bind(this)
        });
        this.preloadNeighborImages();
    },

    //
    //  updateDetails()
    //  Display caption, image number, and bottom nav.
    //
    updateDetails: function() {

        // use caption, or fall back to the file name if it's empty.
        if (this.imageArray[this.activeImage][2] != ""){
            $('caption').update(this.imageArray[this.activeImage][2]).show();
        } else {
            $('caption').update(this.imageArray[this.activeImage][1]).show();
        }

        // if image is part of set display 'Image x of x'
        if (this.imageArray.length > 1){
            $('numberDisplay').update( this.options.labelImage + ' ' + (this.activeImage + 1) + ' ' + this.options.labelOf + '  ' + this.imageArray.length).show();
        }

        new Effect.Parallel(
            [
                new Effect.SlideDown($('imageDataContainer'), { sync: true, duration: this.resizeDuration, from: 0.0, to: 1.0 }),
                new Effect.Appear($('imageDataContainer'), { sync: true, duration: this.resizeDuration })
            ],
            {
                duration: this.resizeDuration,
                afterFinish: (function() {
                    // update overlay size and update nav
                    var arrayPageSize = this.getPageSize();
                    $('overlay').setStyle({ height: arrayPageSize[1] + 'px' });
                    this.updateNav();
                }).bind(this)
            }
        );
    },

    //
    //  updateNav()
    //  Display appropriate previous and next hover navigation.
    //
    updateNav: function() {

        $('hoverNav').show();
        // if not first image in set, display prev image button
        if (this.activeImage > 0) $('prevLink').show();

        // if not last image in set, display next image button
        if (this.activeImage < (this.imageArray.length - 1)) $('nextLink').show();

        this.enableKeyboardNav();
    },

    //
    //  enableKeyboardNav()
    //
    enableKeyboardNav: function() {
        document.observe('keydown', this.keyboardAction);
    },

    //
    //  disableKeyboardNav()
    //
    disableKeyboardNav: function() {
        document.stopObserving('keydown', this.keyboardAction);
    },

    //
    //  keyboardAction()
    //
    keyboardAction: function(event) {
        var keycode = event.keyCode;

        var escapeKey;
        if (event.DOM_VK_ESCAPE) {  // mozilla
            escapeKey = event.DOM_VK_ESCAPE;
        } else { // ie
            escapeKey = 27;
        }

        var key = String.fromCharCode(keycode).toLowerCase();

        if (key.match(/x|o|c/) || (keycode == escapeKey)){ // close lightbox
            this.end();
        } else if ((key == 'p') || (keycode == 37)){ // display previous image
            if (this.activeImage != 0){
                this.disableKeyboardNav();
                this.changeImage(this.activeImage - 1);
            }
        } else if ((key == 'n') || (keycode == 39)){ // display next image
            if (this.activeImage != (this.imageArray.length - 1)){
                this.disableKeyboardNav();
                this.changeImage(this.activeImage + 1);
            }
        }
    },

    //
    //  preloadNeighborImages()
    //  Preload previous and next images.
    //
    preloadNeighborImages: function(){
        var preloadNextImage, preloadPrevImage;
        if (this.imageArray.length > this.activeImage + 1){
            preloadNextImage = new Image();
            preloadNextImage.src = this.imageArray[this.activeImage + 1][0];
        }
        if (this.activeImage > 0){
            preloadPrevImage = new Image();
            preloadPrevImage.src = this.imageArray[this.activeImage - 1][0];
        }

    },

    //
    //  end()
    //
    end: function() {
        this.disableKeyboardNav();
        $('lightbox').hide();
        new Effect.Fade($('overlay'), { duration: this.overlayDuration });
        $$('select', 'object', 'embed').invoke('setStyle', 'visibility:visible');

        //redirect here//
        if (this.options.startPage != this.imageArray[this.activeImage][4]) {
            location.href = this.options.returnURL + "page=" + this.imageArray[this.activeImage][4];
        }
    },

    //
    //  getPageSize()
    //
    getPageSize: function() {

         var xScroll, yScroll;

        if (window.innerHeight && window.scrollMaxY) {
            xScroll = window.innerWidth + window.scrollMaxX;
            yScroll = window.innerHeight + window.scrollMaxY;
        } else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
            xScroll = document.body.scrollWidth;
            yScroll = document.body.scrollHeight;
        } else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
            xScroll = document.body.offsetWidth;
            yScroll = document.body.offsetHeight;
        }

        var windowWidth, windowHeight;

        if (self.innerHeight) {    // all except Explorer
            if(document.documentElement.clientWidth){
                windowWidth = document.documentElement.clientWidth;
            } else {
                windowWidth = self.innerWidth;
            }
            windowHeight = self.innerHeight;
        } else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
            windowWidth = document.documentElement.clientWidth;
            windowHeight = document.documentElement.clientHeight;
        } else if (document.body) { // other Explorers
            windowWidth = document.body.clientWidth;
            windowHeight = document.body.clientHeight;
        }

        // for small pages with total height less then height of the viewport
        if(yScroll < windowHeight){
            pageHeight = windowHeight;
        } else {
            pageHeight = yScroll;
        }

        // for small pages with total width less then width of the viewport
        if(xScroll < windowWidth){
            pageWidth = xScroll;
        } else {
            pageWidth = windowWidth;
        }

        return [pageWidth,pageHeight];
    }
}