//<![CDATA[
Event.observe(window, 'load', function() {
    // The number of unique, embedded instances
    var nodeCount = anselnodes.length;

    // Holds any lightbox json
    var lightboxData = new Array();

    // Iterate over each embedded instance and create the DOM elements.
    for (var n = 0; n < nodeCount; n++) {

        // j is the textual name of the container, used as a key
        var j = anselnodes[n];

        // Do we have any lightbox data?
        if (typeof anseljson[j]['lightbox'] != 'undefined') {
            lightboxData = lightboxData.concat(anseljson[j]['lightbox']);
        }

        // Top level DOM node for this embedded instannce
        var mainNode = $(j);

        // Used if we have requested the optional paging feature
        if (anseljson[j]['perpage']) {
            var pagecount = anseljson[j]['perpage'];
        } else {
            var pagecount = anseljson[j]['data'].size();
        }

        // For each image in this instance, create the DOM structure
        for (var i = 0; i < pagecount; i++) {
            // Need a nested function and closures to force new scope
            (function() {
                var jx = j;
                var ix = i;
                var imgContainer = new Element('span', {className: 'anselGalleryWidget'});
                if (!anseljson[jx]['hideLinks']) {
                    if (anseljson[jx]['linkToGallery']) {
                        var idx = 6;
                    } else {
                        var idx = 5;
                    }
                    var imgLink = imgContainer.appendChild(new Element('a',
                        {
                            href: anseljson[jx]['data'][ix][idx],
                            title: anseljson[jx]['data'][ix][2]
                         }));
                   var lb_data = {image: anseljson[jx]['data'][ix][3]};
                   imgLink.appendChild(new Element('img', {src: anseljson[jx]['data'][ix][0]}));
                   // Attach the lightbox action if we have lightbox data
                   if (typeof anseljson[j]['lightbox'] != 'undefined') {
                       imgLink.observe('click', function(e) {ansel_lb.start(lb_data.image); e.stop();});
                   }
                } else {
                    imgContainer.appendChild(new Element('img', {src: anseljson[jx]['data'][ix][0]}));
                    // Attach the lightbox action if we have lightbox data
                    if (typeof anseljson[j]['lightbox'] != 'undefined') {
                        imgLink.observe('click', function(e) {ansel_lb.start(lb_data.image); e.stop();});
                    }
                }

                mainNode.appendChild(imgContainer);
            })();
        }

        if (anseljson[j]['perpage'] > 0) {
            (function() {
                var jx = j;

                var nextLink = new Element('a',{href: '#', title: 'Next Image', className: 'anselNext', style: 'text-decoration:none;width:40%;float:right;'});
                nextLink.update('>>');
                var arg1 = {node: jx, page: 1};
                nextLink.observe('click', function(e) {displayPage(e, arg1)});

                var prevLink = new Element('a',{href: '#', title: 'Previous Image', className: 'anselPrev', style: 'text-decoration:none;width:40%;float:right;'});
                prevLink.update('<<');
                var arg2 = {node: jx, page: -1};
                prevLink.observe('click', function(e) {displayPage(e, arg2)});
                $(jx).appendChild(nextLink);
                $(jx).appendChild(prevLink);
                Horde_ToolTips.attachBehavior(jx);
                Event.observe(window, 'unload', Horde_ToolTips.out.bind(Horde_ToolTips));

            })();
        } else {
            (function () {
                var jx = j;
                Horde_ToolTips.attachBehavior(jx);
            })();
        }
    }
    if (lightboxData.length) {
        lbOptions['gallery_json'] = lightboxData;
        ansel_lb = new Lightbox(lbOptions);
    }

    Event.observe(window, 'unload', Horde_ToolTips.out.bind(Horde_ToolTips));
 });

/**
 * Display the images from the requested page for the requested node.
 *
 * @param string $node   The DOM id of the embedded widget.
 * @param integer $page  The requested page number.
 */
function displayPage(event, args) {
    var node = args.node;
    var page = args.page;
    var perpage = anseljson[node]['perpage'];
    var imgcount = anseljson[node]['data'].size();
    var pages = Math.ceil(imgcount / perpage) - 1;
    var oldPage = anseljson[node]['page'];

    page = oldPage + page;

    /* Rollover? */
    if (page > pages) {
        page = 0;
    }
    if (page < 0) {
        page = pages;
    }

    var mainNode = $(node);
    mainNode.update();
    var start = page * perpage;
    var end = Math.min(imgcount - 1, start + perpage - 1);
    for (var i = start; i <= end; i++) {
        var imgContainer = mainNode.appendChild(new Element('span', {className: 'anselGalleryWidget'}));
        var imgLink = imgContainer.appendChild(new Element('a',
            {
                href: anseljson[node]['data'][i][5],
                alt: anseljson[node]['data'][i][2],
                title: anseljson[node]['data'][i][2]
             }));
        imgLink.appendChild(new Element('img', {src: anseljson[node]['data'][i][0]}));
    }

     var nextLink = new Element('a',{href: '', title: 'Next Image', style: 'text-decoration:none;width:40%;float:right;'});
     nextLink.update('>>');

     var args = {node: node, page: ++oldPage};
     nextLink.observe('click', function(e) {displayPage(e, args);}.bind());

     var prevLink = new Element('a',{href: '', title: 'Previous Image', style: 'text-decoration:none;width:40%;float:right;'});
     prevLink.update('<<');

     var args = {node: node, page: --oldPage};
     prevLink.observe('click', function(e) {displayPage(e, args);}.bind());

     mainNode.appendChild(nextLink);
     mainNode.appendChild(prevLink);

     Horde_ToolTips.attachBehavior(node);
     anseljson[node]['page'] = page;
     event.stop();
}
//]
