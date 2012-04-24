$.mobile.page.prototype.options.addBackBtn = true;

// Setup event bindings to populate views on pagebeforeshow
KronolithMobile.date = new Date();
$("#dayview").live("pagebeforeshow", function() {
    KronolithMobile.view = "day";
    $(".kronolithDayDate").html(KronolithMobile.date.toString("ddd") + " " + KronolithMobile.date.toString("d"));
    KronolithMobile.loadEvents(KronolithMobile.date, KronolithMobile.date, "day");
});

$("#monthview").live("pagebeforeshow", function(event, ui) {
    KronolithMobile.view = "month";
    // (re)build the minical only if we need to
    if (!$(".kronolithMinicalDate").data("date") ||
        ($(".kronolithMinicalDate").data("date").toString("M") != KronolithMobile.date.toString("M"))) {
        KronolithMobile.moveToMonth(KronolithMobile.date);
    }
});

$("#eventview").live("pageshow", function(event, ui) {
    KronolithMobile.view = "event";
});

// Set up overview
$("#overview").live("pageshow", function(event, ui) {
    KronolithMobile.view = "overview";
    if (!KronolithMobile.haveOverview) {
        KronolithMobile.loadEvents(KronolithMobile.date, KronolithMobile.date.clone().addDays(7), "overview");
        KronolithMobile.haveOverview = true;
    }
});
