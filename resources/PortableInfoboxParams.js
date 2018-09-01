(function (window) {
	'use strict';

	var TemplateDataSuggestions = {
		init: function() {
			var original = mw.TemplateData.SourceHandler.prototype.extractParametersFromTemplateCode;
			mw.TemplateData.SourceHandler.prototype.extractParametersFromTemplateCode = function( templateCode ) {
				var infobox, source,
					params = original(templateCode),
					infoboxRegex = /<infobox.*?<\/infobox>/gs,
					sourceRegex = /<[^<\/>]*? source="([^"]*)"[^>]*>/g;

				while( ( infobox = infoboxRegex.exec(templateCode) ) !== null ) {
					while( ( source = sourceRegex.exec(infobox) ) !== null ) {
						if ( $.inArray( source[1], params ) === -1 ) {
							params.push( source[1] );
						}
					}
				}

				return params;
			}
		}
	}

	mw.loader.using('ext.templateDataGenerator.data').then(function() {
		TemplateDataSuggestions.init();
	});
})(window);
