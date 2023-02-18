FullCalendar.globalLocales.push(function () {
  'use strict';

  var ua = {
    code: 'ua',
    week: {
      dow: 1, // Monday is the first day of the week.
      doy: 4, // The week that contains Jan 4th is the first week of the year.
    },
    buttonText: {
      prev: 'Попер',
      next: 'Слiд',
      today: 'Сьогоднi',
      month: 'Мiсяць',
      week: 'Тиждень',
      day: 'День',
      list: 'Повiстка дня',
    },
    weekText: 'Тижд.',
    allDayText: 'Весь день',
    moreLinkText(n) {
      return '+ ще ' + n
    },
    noEventsText: 'Нема  подiй',
  };

  return ua;

}());
