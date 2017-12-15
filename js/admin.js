(function($) {
	$(function() {
		// Make tables pretty and sortable
		$('.plugin-usage-table').dynatable({
			readers: {
		      'integer': function(el, record) {
		        return Number(el.innerHTML) || 0;
		      }
		    }
		});
	});
})(jQuery);
