//! moment.js locale configuration
//! locale : Russian [ru]
//! author : Viktorminator : https://github.com/Viktorminator
//! author : Menelion Elensúle : https://github.com/Oire
//! author : Коренберг Марк : https://github.com/socketpair

;(function (global, factory) {
   typeof exports === 'object' && typeof module !== 'undefined'
       && typeof require === 'function' ? factory(require('../moment')) :
   typeof define === 'function' && define.amd ? define(['../moment'], factory) :
   factory(global.moment)
}(this, (function (moment) { 'use strict';

    //! moment.js locale configuration

    function plural(word, num) {
        var forms = word.split('_');
        return num % 10 === 1 && num % 100 !== 11
            ? forms[0]
            : num % 10 >= 2 && num % 10 <= 4 && (num % 100 < 10 || num % 100 >= 20)
            ? forms[1]
            : forms[2];
    }
    function relativeTimeWithPlural(number, withoutSuffix, key) {
        var format = {
            ss: withoutSuffix ? 'секунда_секунди_секунд' : 'секунду_секунди_секунд',
            mm: withoutSuffix ? 'хвилина_хвилини_хвилин' : 'хвилину_хвилини_хвилин',
            hh: 'година_години_годин',
            dd: 'день_дня_днiв',
            ww: 'тиждень_тижня_тижнiв',
            MM: 'мiсяць_мiсяця_мiсяцiв',
            yy: 'рiк_року_рокiв',
        };
        if (key === 'm') {
            return withoutSuffix ? 'хвилина' : 'хвилину';
        } else {
            return number + ' ' + plural(format[key], +number);
        }
    }
    var monthsParse = [
        /^сiч/i,
        /^лют/i,
        /^бер/i,
        /^квi/i,
        /^тра/i,
        /^чер/i,
        /^лип/i,
        /^сер/i,
        /^вер/i,
        /^жов/i,
        /^лис/i,
        /^гру/i,
    ];

    // http://new.gramota.ru/spravka/rules/139-prop : § 103
    // Сокращения месяцев: http://new.gramota.ru/spravka/buro/search-answer?s=242637
    // CLDR data:          http://www.unicode.org/cldr/charts/28/summary/ru.html#1753
    var ua = moment.defineLocale('ua', {
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
        monthsParse: monthsParse,
        longMonthsParse: monthsParse,
        shortMonthsParse: monthsParse,

                    monthsRegex: /^(січен[я]|січ\.?|люто[го]|лют?\.?|березен[я]|берез\.?|квітен[я]|квіт\.?|травен[я]|трав\.?|червн[я]|черв\.?|липн[я]|лип\.?|серпен[я]|серп\.?|вересн[я]|вер?\.?|жовтен[я]|жовт\.?|листопад[а]|лист?\.?|груден[я]|груд\.?)/i,
                    monthsShortRegex: /^(січен[я]|січ\.?|люто[го]|лют?\.?|березен[я]|берез\.?|квітен[я]|квіт\.?|травен[я]|трав\.?|червн[я]|черв\.?|липн[я]|лип\.?|серпен[я]|серп\.?|вересн[я]|вер?\.?|жовтен[я]|жовт\.?|листопад[а]|лист?\.?|груден[я]|груд\.?)/i,
                    monthsStrictRegex: /^(січен[яь]|лютого?|березен[яь]|квітен[яь]|травен[яь]|червн[яь]|липн[яь]|серпен[яь]|вересн[яь]|жовтен[яь]|листопад[а]|груден[яь])/i,
                    monthsShortStrictRegex: /^(січ\.|лют\.|берез\.|квіт\.|трав\.|червн\.|липн\.|серп\.|верес\.|жовт\.|лист\.|груд\.)/i,
        longDateFormat: {
            LT: 'H:mm',
            LTS: 'H:mm:ss',
            L: 'DD.MM.YYYY',
            LL: 'D MMMM YYYY г.',
            LLL: 'D MMMM YYYY г., H:mm',
            LLLL: 'dddd, D MMMM YYYY г., H:mm',
        },
        calendar: {
            sameDay: '[Сьогодні, в] LT',
            nextDay: '[Завтра, в] LT',
            lastDay: '[Вчора, в] LT',
            nextWeek: function (now) {
                if (now.week() !== this.week()) {
                    switch (this.day()) {
                        case 0:
                            return '[В наступне] dddd, [в] LT';
                        case 1:
                        case 2:
                        case 4:
                            return '[В наступний] dddd, [в] LT';
                        case 3:
                        case 5:
                        case 6:
                            return '[В Наступної] dddd, [в] LT';
                    }
                } else {
                    if (this.day() === 2) {
                        return '[Во] dddd, [в] LT';
                    } else {
                        return '[В] dddd, [в] LT';
                    }
                }
            },
            lastWeek: function (now) {
                if (now.week() !== this.week()) {
                    switch (this.day()) {
                        case 0:
                            return '[В минуле] dddd, [в] LT';
                        case 1:
                        case 2:
                        case 4:
                            return '[Минулого] dddd, [в] LT';
                        case 3:
                        case 5:
                        case 6:
                            return '[Минулої] dddd, [в] LT';
                    }
                } else {
                    if (this.day() === 2) {
                        return '[Во] dddd, [в] LT';
                    } else {
                        return '[В] dddd, [в] LT';
                    }
                }
            },
            sameElse: 'L',
        },
        relativeTime: {
            future: 'через %s',
            past: '%s назад',
            s: 'декілька секунд',
            ss: relativeTimeWithPlural,
            m: relativeTimeWithPlural,
            mm: relativeTimeWithPlural,
            h: 'година',
            hh: relativeTimeWithPlural,
            d: 'день',
            dd: relativeTimeWithPlural,
            w: 'тиждень',
            ww: relativeTimeWithPlural,
            M: 'місяць',
            MM: relativeTimeWithPlural,
            y: 'рік',
            yy: relativeTimeWithPlural,
        },
                  meridiemParse: /ночі|ранку|дня|вечора/i,
                    isPM: function(e) {
                        return /^(дня|вечора)$/.test(e)
                    },
          meridiem: function (hour, minute, isLower) {
            if (hour < 4) {
                return 'ночі';
            } else if (hour < 12) {
                return 'ранку';
            } else if (hour < 17) {
                return 'дня';
            } else {
                return 'вечора';
            }
        },                      
                        
                        
        dayOfMonthOrdinalParse: /\d{1,2}-(й|го|я)/,
        ordinal: function (number, period) {
                switch (period) {
                case 'M':
                case 'd':
                case 'DDD':
                    return number + '-й';
                case 'D':
                    return number + '-го';
                case 'w':
                case 'W':
                    return number + '-я';
                default:
                    return number;
            }
        },
        week: {
            dow: 1, // Monday is the first day of the week.
            doy: 4, // The week that contains Jan 4th is the first week of the year.
        },
    });
          
    return ua;

})));
