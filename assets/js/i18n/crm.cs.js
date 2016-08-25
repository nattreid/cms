/* Czech initialisation for the jQuery UI date picker plugin. */
/* Written by Tomas Muller (tomas@tomas-muller.net). */
( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( [ "../widgets/datepicker" ], factory );
	} else {

		// Browser globals
		factory( jQuery.datepicker );
	}
}( function( datepicker ) {

datepicker.regional.cs = {
	closeText: "Zavřít",
	prevText: "&#x3C;Dříve",
	nextText: "Později&#x3E;",
	currentText: "Nyní",
	monthNames: [ "leden","únor","březen","duben","květen","červen",
	"červenec","srpen","září","říjen","listopad","prosinec" ],
	monthNamesShort: [ "led","úno","bře","dub","kvě","čer",
	"čvc","srp","zář","říj","lis","pro" ],
	dayNames: [ "neděle", "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota" ],
	dayNamesShort: [ "ne", "po", "út", "st", "čt", "pá", "so" ],
	dayNamesMin: [ "ne","po","út","st","čt","pá","so" ],
	weekHeader: "Týd",
	dateFormat: "dd.mm.yy",
	firstDay: 1,
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: "" };
datepicker.setDefaults( datepicker.regional.cs );

return datepicker.regional.cs;

} ) );

/*!
 * Bootstrap-select v1.10.0 (http://silviomoreto.github.io/bootstrap-select)
 *
 * Copyright 2013-2016 bootstrap-select
 * Licensed under MIT (https://github.com/silviomoreto/bootstrap-select/blob/master/LICENSE)
 */

(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module unless amdModuleId is set
    define(["jquery"], function (a0) {
      return (factory(a0));
    });
  } else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but
    // only CommonJS-like environments that support module.exports,
    // like Node.
    module.exports = factory(require("jquery"));
  } else {
    factory(jQuery);
  }
}(this, function (jQuery) {

(function ($) {
  $.fn.selectpicker.defaults = {
    noneSelectedText: 'Nic není vybráno',
    noneResultsText: 'Žádné výsledky {0}',
    countSelectedText: 'Označeno {0} z {1}',
    maxOptionsText: ['Limit překročen ({n} {var} max)', 'Limit skupiny překročen ({n} {var} max)', ['položek', 'položka']],
    multipleSeparator: ', '
  };
})(jQuery);


}));
