Date.CultureInfo = {
    /* Culture Name */
    name: "lv-LV",
    englishName: "Latvian (Latvia)",
    nativeName: "latviešu (Latvija)",

    /* Day Name Strings */
    dayNames: ["svētdiena", "pirmdiena", "otrdiena", "trešdiena", "ceturtdiena", "piektdiena", "sestdiena"],
    abbreviatedDayNames: ["Sv", "Pr", "Ot", "Tr", "Ce", "Pk", "Se"],
    shortestDayNames: ["Sv", "Pr", "Ot", "Tr", "Ce", "Pk", "Se"],
    firstLetterDayNames: ["S", "P", "O", "T", "C", "P", "S"],

    /* Month Name Strings */
    monthNames: ["janvāris", "februāris", "marts", "aprīlis", "maijs", "jūnijs", "jūlijs", "augusts", "septembris", "oktobris", "novembris", "decembris"],
    abbreviatedMonthNames: ["Jan", "Feb", "Mar", "Apr", "Mai", "Jūn", "Jūl", "Aug", "Sep", "Okt", "Nov", "Dec"],

    /* AM/PM Designators */
    amDesignator: "AM",
    pmDesignator: "PM",

    firstDayOfWeek: 1,
    twoDigitYearMax: 2029,

    /**
     * The dateElementOrder is based on the order of the
     * format specifiers in the formatPatterns.DatePattern.
     *
     * Example:
     <pre>
     shortDatePattern    dateElementOrder
     ------------------  ----------------
     "M/d/yyyy"          "mdy"
     "dd/MM/yyyy"        "dmy"
     "yyyy-MM-dd"        "ymd"
     </pre>
     *
     * The correct dateElementOrder is required by the parser to
     * determine the expected order of the date elements in the
     * string being parsed.
     */
    dateElementOrder: "ymd",

    /* Standard date and time format patterns */
    formatPatterns: {
        shortDate: "yyyy.MM.dd.",
        longDate: "dddd, yyyy. ga\\da d. MMMM",
        shortTime: "H:mm",
        longTime: "H:mm:ss",
        fullDateTime: "dddd, yyyy. ga\\da d. MMMM H:mm:ss",
        sortableDateTime: "yyyy-MM-ddTHH:mm:ss",
        universalSortableDateTime: "yyyy-MM-dd HH:mm:ssZ",
        rfc1123: "ddd, dd MMM yyyy HH:mm:ss GMT",
        monthDay: "d. MMMM",
        yearMonth: "yyyy. MMMM"
    },

    /**
     * NOTE: If a string format is not parsing correctly, but
     * you would expect it parse, the problem likely lies below.
     *
     * The following regex patterns control most of the string matching
     * within the parser.
     *
     * The Month name and Day name patterns were automatically generated
     * and in general should be (mostly) correct.
     *
     * Beyond the month and day name patterns are natural language strings.
     * Example: "next", "today", "months"
     *
     * These natural language string may NOT be correct for this culture.
     * If they are not correct, please translate and edit this file
     * providing the correct regular expression pattern.
     *
     * If you modify this file, please post your revised CultureInfo file
     * to the Datejs Forum located at http://www.datejs.com/forums/.
     *
     * Please mark the subject of the post with [CultureInfo]. Example:
     *    Subject: [CultureInfo] Translated "da-DK" Danish(Denmark)
     *
     * We will add the modified patterns to the master source files.
     *
     * As well, please review the list of "Future Strings" section below.
     */
    regexPatterns: {
        jan: /^jan(vāris)?/i,
        feb: /^feb(ruāris)?/i,
        mar: /^mar(ts)?/i,
        apr: /^apr(īlis)?/i,
        may: /^mai(js)?/i,
        jun: /^jūn(ijs)?/i,
        jul: /^jūl(ijs)?/i,
        aug: /^aug(usts)?/i,
        sep: /^sep(tembris)?/i,
        oct: /^okt(obris)?/i,
        nov: /^nov(embris)?/i,
        dec: /^dec(embris)?/i,

        sun: /^svēt(dien(a)?)?/i,
        mon: /^pirm(dien(a)?)?/i,
        tue: /^otr(dien(a)?)?/i,
        wed: /^treš(dien(a)?)?/i,
        thu: /^ceturt(dien(a)?)?/i,
        fri: /^piekt(dien(a)?)?/i,
        sat: /^sest(dien(a)?)?/i,

        future: /^nākoš(ā|ais)?/i,
        past: /^pagāj(uš(ā|ais)?)?|iepr(iekšēj(ā|ais)?)?/i,
        add: /^(\+|pēc)/i,
        subtract: /^(\-|pirms)/i,

        yesterday: /^vakar(dien(a)?)?/i,
        today: /^šodien(a)?/i,
        tomorrow: /^rīt(dien(a)?)?/i,
        now: /^tagad/i,

        millisecond: /^ms|milisekunde(s)?/i,
        second: /^sec|sekunde(s)?/i,
        minute: /^min|minūte(s)?/i,
        hour: /^h|stunda(s)?/i,
        week: /^w|nedēļa(s)?/i,
        month: /^m|mēne(sis|ši)/i,
        day: /^d|diena(s)?/i,
        year: /^y|gad(s|i)/i,

        shortMeridian: /^(a|p)/i,
        longMeridian: /^(a\.?m?\.?|p\.?m?\.?)/i,
        timezone: /^((e(s|d)t|c(s|d)t|m(s|d)t|p(s|d)t)|((gmt)?\s*(\+|\-)\s*\d\d\d\d?)|gmt|utc)/i,
        ordinalSuffix: /^\s*(st|nd|rd|th)/i,
        timeContext: /^\s*(\:|a(?!u|p)|p)/i
    },

        timezones: [{name:"UTC", offset:"-000"}, {name:"GMT", offset:"-000"}, {name:"EST", offset:"-0500"}, {name:"EDT", offset:"-0400"}, {name:"CST", offset:"-0600"}, {name:"CDT", offset:"-0500"}, {name:"MST", offset:"-0700"}, {name:"MDT", offset:"-0600"}, {name:"PST", offset:"-0800"}, {name:"PDT", offset:"-0700"}]

};

/********************
 ** Future Strings **
 ********************
 *
 * The following list of strings may not be currently being used, but
 * may be incorporated into the Datejs library later.
 *
 * We would appreciate any help translating the strings below.
 *
 * If you modify this file, please post your revised CultureInfo file
 * to the Datejs Forum located at http://www.datejs.com/forums/.
 *
 * Please mark the subject of the post with [CultureInfo]. Example:
 *    Subject: [CultureInfo] Translated "da-DK" Danish(Denmark)b
 *
 * English Name        Translated
 * ------------------  -----------------
 * about               apmēram
 * ago                 pirms
 * date                datums
 * time                laiks
 * calendar            kalendārs
 * show                parādīt
 * hourly              ikstundas
 * daily               ikdienas
 * weekly              iknedēļas
 * bi-weekly           reizi divās nedēļās
 * fortnight           divas nedēļas
 * monthly             ikmēnesi
 * bi-monthly          reizi divos mēnešos
 * quarter             ceturksnis
 * quarterly           ikceturksni
 * yearly              ikgadu
 * annual              ikgadējs
 * annually            ikgadu
 * annum               gadā
 * again               atkal
 * between             starp
 * after               pēc
 * from now            turpmāk
 * repeat              atkārtot
 * times               reizes
 * per                 reizi
 * min (abbrev minute) min
 * morning             rīts
 * noon                pusdienas
 * night               nakts
 * midnight            pusnakts
 * mid-night           pusnakts
 * evening             vakars
 * final               gala
 * future              nākotne
 * spring              pavasaris
 * summer              vasara
 * fall                rudens
 * winter              ziema
 * end of              beigas
 * end                 beigas
 * long                garš
 * short               īss
 */ 
