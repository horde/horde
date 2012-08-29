/**
 * Version: 1.0 Alpha-1
 * Build Date: 13-Nov-2007
 * Copyright (c) 2006-2007, Coolite Inc. (http://www.coolite.com/). All rights reserved.
 * License: Licensed under The MIT License. See license.txt and http://www.datejs.com/license/.
 * Website: http://www.datejs.com/ or http://www.coolite.com/datejs/
 */
Date.CultureInfo = {
    /* Culture Name */
    name: "tr-TR",
    englishName: "Turkish (Turkey)",
    nativeName: "Türkçe (Türkiye)",

    /* Day Name Strings */
    dayNames: ["Pazar", "Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi"],
    abbreviatedDayNames: ["Paz", "Pzt", "Sal", "Çar", "Per", "Cum", "Cmt"],
    shortestDayNames: ["Pz", "Pt", "Sa", "Ça", "Pe", "Cu", "Ct"],
    firstLetterDayNames: ["P", "P", "S", "Ç", "P", "C", "C"],

    /* Month Name Strings */
    monthNames: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"],
    abbreviatedMonthNames: ["Oca", "Şub", "Mar", "Nis", "May", "Tem", "Haz", "Ağu", "Eyl", "Eki", "Kas", "Ara"],

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
     * The correct dateElementOrder is required by the parser to
     * determine the expected order of the date elements in the
     * string being parsed.
     *
     * NOTE: It is VERY important this value be correct for each Culture.
     */
    dateElementOrder: "dmy",

    /* Standard date and time format patterns */
    formatPatterns: {
        shortDate: "d/M/yyyy",
        longDate: "dddd, dd, MMMM, yyyy",
        shortTime: "h:mm tt",
        longTime: "h:mm:ss tt",
        fullDateTime: "dddd, dd, MMMM, yyyy h:mm:ss tt",
        sortableDateTime: "dd-MM-yyyyTHH:mm:ss",
        universalSortableDateTime: "dd-MM-yyyy HH:mm:ssZ",
        rfc1123: "ddd, dd MMM yyyy HH:mm:ss GMT",
        monthDay: "dd MMMM",
        yearMonth: "MMMM, yyyy"
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
     * to the Datejs Discussions located at
     *     http://groups.google.com/group/date-js
     *
     * Please mark the subject with [CultureInfo]. Example:
     *    Subject: [CultureInfo] Translated "da-DK" Danish(Denmark)
     *
     * We will add the modified patterns to the master source files.
     *
     * As well, please review the list of "Future Strings" section below.
     */
    regexPatterns: {
        jan: /^oca(k)?/i,
        feb: /^şub(at)?/i,
        mar: /^mar(t)?/i,
        apr: /^nis(an)?/i,
        may: /^may(ıs)?/i,
        jun: /^haz(iran)?/i,
        jul: /^tem(muz)?/i,
        aug: /^ağu(stos)?/i,
        sep: /^eyl(ül)?/i,
        oct: /^eki(m)?/i,
        nov: /^kas(ım)?/i,
        dec: /^ara(lık)?/i,
        sun: /^pa(z(ar)?)?/i,
        mon: /^pa(z(artesi)?)?/i,
        tue: /^sa(l(ı)?)?/i,
        wed: /^ça(r(şamba)?)?/i,
        thu: /^pe(r(ş(e(mbe)?)?)?)?/i,
        fri: /^cu(m(a)?)?/i,
        sat: /^cu(m(artesi)?)?/i,
        future: /^ertesi|birdahaki|sonraki|önümüzdeki/i,
        past: /^evvelsi|önceki|geçen?/i,
        add: /^(\+|sonra|dan)/i,
        subtract: /^(\-|önce)/i,
        yesterday: /^dün/i,
        today: /^bu(gün)?/i,
        tomorrow: /^yarın/i,
        now: /^şi(mdi)?/i,
        millisecond: /^ms|mili(saniye)?/i,
        second: /^san(iye)?/i,
        minute: /^dak(ika)?s?/i,
        hour: /^s(aa)?t?/i,
        week: /^h(af)?ta/i,
        month: /^a(y)?/i,
        day: /^g(ün)?/i,
        year: /^y(ıl)?/i,
        shortMeridian: /^(a|p)/i,
        longMeridian: /^(a\.?m?\.?|p\.?m?\.?)/i,
        timezone: /^((e(s|d)t|c(s|d)t|m(s|d)t|p(s|d)t)|((gmt)?\s*(\+|\-)\s*\d\d\d\d?)|gmt)/i,
        ordinalSuffix: /^\s*(inci|ıncı)/i,
        timeContext: /^\s*(\:|a|p)/i
    },

    abbreviatedTimeZoneStandard: { GMT: "+0200", EST: "-0400", CST: "-0500", MST: "-0600", PST: "-0700" },
    abbreviatedTimeZoneDST: { GMT: "+0200", EDT: "-0500", CDT: "-0600", MDT: "-0700", PDT: "-0800" }

};

/********************
 ** Future Strings **
 ********************
 *
 * The following list of strings are not currently being used, but
 * may be incorporated later. We would appreciate any help translating
 * the strings below.
 *
 * If you modify this file, please post your revised CultureInfo file
 * to the Datejs Discussions located at
 *     http://groups.google.com/group/date-js
 *
 * Please mark the subject with [CultureInfo]. Example:
 *    Subject: [CultureInfo] Translated "da-DK" Danish(Denmark)
 *
 * English Name        Translated
 * ------------------  -----------------
 * date                tarih
 * time                zaman
 * calendar            takvim
 * show                göster
 * hourly              saatlik
 * daily               günlük
 * weekly              haftalık
 * bi-weekly           iki haftada bir
 * monthly             aylık
 * bi-monthly          iki ayda bir
 * quarter             çeyrek
 * quarterly           her çeyrekte
 * yearly              yıllık
 * annual              yıldönümü
 * annually            her yıl
 * annum               yılda
 * again               tekrar
 * between             arasında
 * after               sonra
 * from now            bundan sonra
 * repeat              tekrar
 * times               kere
 * per                 başına
 */
