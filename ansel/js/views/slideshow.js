AnselSlideShowView = {

    onload: function()
    {
        $('PrevLink').observe('click', SlideController.prev);
        $('NextLink').observe('click', SlideController.next);
        $('ssPause').observe('click', SlideController.pause);
        $('ssPlay').observe('click', SlideController.play);
    }
};
document.observe('dom:loaded', AnselSlideShowView.onload);