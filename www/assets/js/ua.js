! function(e, t) {
    "object" == typeof exports && "object" == typeof module ? module.exports = t(require("moment"), require("fullcalendar")) : "function" == typeof define && define.amd ? define(["moment", "fullcalendar"], t) : "object" == typeof exports ? t(require("moment"), require("fullcalendar")) : t(e.moment, e.FullCalendar)
}("undefined" != typeof self ? self : this, function(e, t) {
    return function(e) {
        function t(n) {
            if (r[n]) return r[n].exports;
            var s = r[n] = {
                i: n,
                l: !1,
                exports: {}
            };
            return e[n].call(s.exports, s, s.exports, t), s.l = !0, s.exports
        }
        var r = {};
        return t.m = e, t.c = r, t.d = function(e, r, n) {
            t.o(e, r) || Object.defineProperty(e, r, {
                configurable: !1,
                enumerable: !0,
                get: n
            })
        }, t.n = function(e) {
            var r = e && e.__esModule ? function() {
                return e.default
            } : function() {
                return e
            };
            return t.d(r, "a", r), r
        }, t.o = function(e, t) {
            return Object.prototype.hasOwnProperty.call(e, t)
        }, t.p = "", t(t.s = 181)
    }({
        0: function(t, r) {
            t.exports = e
        },
        1: function(e, r) {
            e.exports = t
        },
        181: function(e, t, r) {
            Object.defineProperty(t, "__esModule", {
                value: !0
            }), r(182);
            var n = r(1);
            n.datepickerLocale("ua", "ua", {
                closeText: "Закрити",
                prevText: "&#x3C;Пред",
                nextText: "След&#x3E;",
                currentText: "Сьогодні",
                monthNames: ["Січень", "Лютий", "Березень", "Квітень", "Травень", "Червень", "Липня", "Серпень", "Вересень", "Жовтень", "Листопад", "Грудень"],
                monthNamesShort: ["Січ", "Лют", "Берез", "Квіт", "Трав", "Черв", "Лип", "Серп", "Верес", "Жовт", "Листоп", "Груд"],
                dayNames: ["Неділя", "Понеділок", "Вівторок", "Середа", "Четвер", "П'ятниця", "Субота"],
                dayNamesShort: ["Нед", "Пон", "Вівт", "Сер", "Чет", "П'ятн", "Субота"],
                dayNamesMin: ["Нд", "Пн", "Вр", "Ср", "Чт", "Пт", "Сб"],
                weekHeader: "Нед",
                dateFormat: "dd.mm.yy",
                firstDay: 1,
                isRTL: !1,
                showMonthAfterYear: !1,
                yearSuffix: ""
            }), n.locale("ua", {
                buttonText: {
                    month: "Місяць",
                    week: "Тиждень",
                    day: "День",
                    list: "Порядок денний"
                },
                allDayText: "Весь день",
                eventLimitText: function(e) {
                    return "+ ще " + e
                },
                noEventsMessage: "Немає подій для відображення"
            })
        },
        182: function(e, t, r) {
            ! function(e, t) {
                t(r(0))
            }(0, function(e) {
                function t(e, t) {
                    var r = e.split("_");
                    return t % 10 == 1 && t % 100 != 11 ? r[0] : t % 10 >= 2 && t % 10 <= 4 && (t % 100 < 10 || t % 100 >= 20) ? r[1] : r[2]
                }

                function r(e, r, n) {
                    var s = {
                        ss: r ? "секунда_секунди_секунд" : "секунду_секунди_секунд",
                        mm: r ? "хвилина_хвилини_хвилин" : "хвилину_хвилини_хвилин",
                        hh: "година_години_годин",
                        dd: "день_дня_днів",
                        MM: "місяць_місяці_місяців",
                        yy: "рік_року_років"
                    };
                    return "m" === n ? r ? "хвилина" : "хвилину" : e + " " + t(s[n], +e)
                }
                var n = [/^січ/i, /^лют/i, /^берез/i, /^квіт/i, /^трав/i, /^черв/i, /^лип/i, /^серп/i, /^верес/i, /^жовт/i, /^листоп/i, /^груд/i];
                return e.defineLocale("ua", {
                    months: {
                        format: "cічня_лютого_березня_квітня_травня_червень_липеня_серпня_вересня_жовтня_листопада_грудня".split("_"),
                        standalone: "січень_лютий_березень_квітень_травень_червень_липень_серпень_вереснь_жовтень_листопад_грудень".split("_")
                    },
                    monthsShort: {
                        format: "січ._лют._бер._квіт._трав_черв_лип_серп._вер._жовт._лист._груд.".split("_"),
                        standalone: "січ._лют._бер._квіт._трав_черв_лип_серп._вер._жовт._лист._груд.".split("_")
                    },
                    weekdays: {
                        standalone: "неділя_понеділок_вівторок_середа_четвер_п'ятниця_субота".split("_"),
                        format: "неділя_понеділок_вівторок_середа_четвер_п'ятниця_субота".split("_"),
                        isFormat: /\[ ?[Вв] ?(?:прошлую|следующую|эту)? ?\] ?dddd/
                    },
                    weekdaysShort: "нд_пн_вт_ср_чт_пт_сб".split("_"),
                    weekdaysMin: "нд_пн_вт_ср_чт_пт_сб".split("_"),
                    monthsParse: n,
                    longMonthsParse: n,
                    shortMonthsParse: n,
                    monthsRegex: /^(січен[я]|січ\.?|люто[го]|лют?\.?|березен[я]|берез\.?|квітен[я]|квіт\.?|травен[я]|трав\.?|червн[я]|черв\.?|липн[я]|лип\.?|серпен[я]|серп\.?|вересн[я]|вер?\.?|жовтен[я]|жовт\.?|листопад[а]|лист?\.?|груден[я]|груд\.?)/i,
                    monthsShortRegex: /^(січен[я]|січ\.?|люто[го]|лют?\.?|березен[я]|берез\.?|квітен[я]|квіт\.?|травен[я]|трав\.?|червн[я]|черв\.?|липн[я]|лип\.?|серпен[я]|серп\.?|вересн[я]|вер?\.?|жовтен[я]|жовт\.?|листопад[а]|лист?\.?|груден[я]|груд\.?)/i,
                    monthsStrictRegex: /^(січен[яь]|лютого?|березен[яь]|квітен[яь]|травен[яь]|червн[яь]|липн[яь]|серпен[яь]|вересн[яь]|жовтен[яь]|листопад[а]|груден[яь])/i,
                    monthsShortStrictRegex: /^(січ\.|лют\.|берез\.|квіт\.|трав\.|червн\.|липн\.|серп\.|верес\.|жовт\.|лист\.|груд\.)/i,
                    longDateFormat: {
                        LT: "H:mm",
                        LTS: "H:mm:ss",
                        L: "DD.MM.YYYY",
                        LL: "D MMMM YYYY г.",
                        LLL: "D MMMM YYYY г., H:mm",
                        LLLL: "dddd, D MMMM YYYY г., H:mm"
                    },
                    calendar: {
                        sameDay: "[Сьогодні в] LT",
                        nextDay: "[Завтра в] LT",
                        lastDay: "[Вчора в] LT",
                        nextWeek: function(e) {
                            if (e.week() === this.week()) return 2 === this.day() ? "[у] dddd [в] LT" : "[У] dddd [в] LT";
                            switch (this.day()) {
                                case 0:
                                    return "[В наступне] dddd [в] LT";
                                case 1:
                                case 2:
                                case 4:
                                    return "[В наступний] dddd [в] LT";
                                case 3:
                                case 5:
                                case 6:
                                    return "[Наступної] dddd [в] LT"
                            }
                        },
                        lastWeek: function(e) {
                            if (e.week() === this.week()) return 2 === this.day() ? "[Во] dddd [в] LT" : "[В] dddd [в] LT";
                            switch (this.day()) {
                                case 0:
                                    return "[В минуле] dddd [в] LT";
                                case 1:
                                case 2:
                                case 4:
                                    return "[Минулого] dddd [в] LT";
                                case 3:
                                case 5:
                                case 6:
                                    return "[Минулої] dddd [в] LT"
                            }
                        },
                        sameElse: "L"
                    },
                    relativeTime: {
                        future: "через %s",
                        past: "%s назад",
                        s: "декілька секунд",
                        ss: r,
                        m: r,
                        mm: r,
                        h: "година",
                        hh: r,
                        d: "день",
                        dd: r,
                        M: "місяць",
                        MM: r,
                        y: "рік",
                        yy: r
                    },
                    meridiemParse: /ночі|ранку|дня|вечора/i,
                    isPM: function(e) {
                        return /^(дня|вечора)$/.test(e)
                    },
                    meridiem: function(e, t, r) {
                        return e < 4 ? "ночі" : e < 12 ? "ранку" : e < 17 ? "дня" : "вечора"
                    },
                    dayOfMonthOrdinalParse: /\d{1,2}-(й|го|я)/,
                    ordinal: function(e, t) {
                        switch (t) {
                            case "M":
                            case "d":
                            case "DDD":
                                return e + "-й";
                            case "D":
                                return e + "-го";
                            case "w":
                            case "W":
                                return e + "-я";
                            default:
                                return e
                        }
                    },
                    week: {
                        dow: 1,
                        doy: 4
                    }
                })
            })
        }
    })
});