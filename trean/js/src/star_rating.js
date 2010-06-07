/**
 * Unobtrusive star rating javascript.
 *
 * $Horde: trean/js/src/star_rating.js,v 1.3 2007/01/13 03:59:37 chuck Exp $
 */

// Star width.
var StarRatingWidth = 25;

var StarRating = {

    attachBehavior: function()
    {
        var raters = document.getElementsByClassName('star-rating');
        for (var i = 0; i < raters.length; ++i) {
            var id = raters[i].id;
            Event.observe(raters[i], 'click', StarRating.click);
        }
    },

    click: function(event)
    {
        var rater = Event.findElement(event, 'ol');
        var bookmarkUrl = rater.getAttribute('for');
        var bookmarkRating = parseInt(Event.findElement(event, 'a').getAttribute('rating'));

        // Ajax call to store the new rating.
        new Ajax.Request(bookmarkUrl, { method: 'get', parameters: { r: bookmarkRating, partial: 1 } });

        // Set the new current rating.
        var currentRating = rater.getElementsByClassName('current-rating')[0];
        currentRating.style.width = (StarRatingWidth * bookmarkRating) + 'px';
        currentRating.innerHTML = (new Array(1 + bookmarkRating).join('*'));

        // Cancel further click action.
        Event.stop(event);
    }

};

Event.observe(window, 'load', StarRating.attachBehavior);
