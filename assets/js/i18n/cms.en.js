/* English/UK initialisation for the jQuery UI date picker plugin. */
/* Written by Stuart. */
( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( [ "../widgets/datepicker" ], factory );
	} else {

		// Browser globals
		factory( jQuery.datepicker );
	}
}( function( datepicker ) {

datepicker.regional[ "en-GB" ] = {
	closeText: "Done",
	prevText: "Prev",
	nextText: "Next",
	currentText: "Today",
	monthNames: [ "January","February","March","April","May","June",
	"July","August","September","October","November","December" ],
	monthNamesShort: [ "Jan", "Feb", "Mar", "Apr", "May", "Jun",
	"Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ],
	dayNames: [ "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday" ],
	dayNamesShort: [ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ],
	dayNamesMin: [ "Su","Mo","Tu","We","Th","Fr","Sa" ],
	weekHeader: "Wk",
	dateFormat: "dd/mm/yy",
	firstDay: 1,
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: "" };
datepicker.setDefaults( datepicker.regional[ "en-GB" ] );

return datepicker.regional[ "en-GB" ];

} ) );

/*!
 * Bootstrap-select v1.13.16 (https://developer.snapappointments.com/bootstrap-select)
 *
 * Copyright 2012-2020 SnapAppointments, LLC
 * Licensed under MIT (https://github.com/snapappointments/bootstrap-select/blob/master/LICENSE)
 */

(function (root, factory) {
  if (root === undefined && window !== undefined) root = window;
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module unless amdModuleId is set
    define(["jquery"], function (a0) {
      return (factory(a0));
    });
  } else if (typeof module === 'object' && module.exports) {
    // Node. Does not work with strict CommonJS, but
    // only CommonJS-like environments that support module.exports,
    // like Node.
    module.exports = factory(require("jquery"));
  } else {
    factory(root["jQuery"]);
  }
}(this, function (jQuery) {

(function ($) {
  $.fn.selectpicker.defaults = {
    noneSelectedText: 'Nothing selected',
    noneResultsText: 'No results match {0}',
    countSelectedText: function (numSelected, numTotal) {
      return (numSelected == 1) ? '{0} item selected' : '{0} items selected';
    },
    maxOptionsText: function (numAll, numGroup) {
      return [
        (numAll == 1) ? 'Limit reached ({n} item max)' : 'Limit reached ({n} items max)',
        (numGroup == 1) ? 'Group limit reached ({n} item max)' : 'Group limit reached ({n} items max)'
      ];
    },
    selectAllText: 'Select All',
    deselectAllText: 'Deselect All',
    multipleSeparator: ', '
  };
})(jQuery);


}));
//# sourceMappingURL=defaults-en_US.js.map
/**
 * British English translation for bootstrap-datepicker
 * Xavier Dutreilh <xavier@dutreilh.com>
 */
;(function($){
	$.fn.datepicker.dates['en-GB'] = {
		days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
		daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
		daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
		months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
		monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
		today: "Today",
		monthsTitle: "Months",
		clear: "Clear",
		weekStart: 1,
		format: "dd/mm/yyyy"
	};
}(jQuery));

(function ($, window) {
    if (window.jQuery === undefined) {
        console.error('Plugin "jQuery" required by "locale.js" is missing!');
        return;
    }
    if (window.moment === undefined) {
        console.error('Plugin "moment" required by "locale.js" is missing!');
        return;
    }

    var locale = $('html').attr('lang');
    if (locale !== undefined) {
        // moment
        window.moment.locale(locale);

        // bootstrap-datepicker
        $.fn.datepicker.defaults.language = locale;
    }

})(jQuery, window);

