(function($) {
	$(function() {
		// Make tables pretty and sortable
		$('.plugin-usage-table').dynatable({
			features: {
				pushState: false
			},
			readers: {
		      'integer': function(el, record) {
		        return Number(el.innerHTML) || 0;
		      }
		    }
		});
	});
})(jQuery);
