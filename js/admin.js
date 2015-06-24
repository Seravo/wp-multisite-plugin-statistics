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
		//Make ajax requests by pushing buttons
		$('.column-select-all').click(function (e) {
		    $(this).closest('table').find('td input:checkbox').prop('checked', this.checked);
		});
		$('input#deactivate-network-plugins').click(function (e) {
			e.preventDefault();
			$.ajax({
				type: "POST",
				url: ajax_object.ajax_url,
				data: {
					action: "deactivate_network_plugins",
					plugins: $('form#deactivate-network').serialize()
				},
				success: function(response) {
					alert('SUCCESS: ' + response);
					//Just reload the window so dynatable updates itself
					window.location.href = window.location.href+'?page=multisite_plugin_stats';
				},
				error: function(response) {
					alert('ERROR:' + response);
				}
			});
		});

		$('input#deactivate-plugins').click(function (e) {
			e.preventDefault();
			$.ajax({
				type: "POST",
				url: ajax_object.ajax_url,
				data: {
					action: "deactivate_plugins",
					plugins: $('form#deactivate-plugins').serialize()
				},
				success: function(response) {
					alert('SUCCESS: ' + response);
					//Just reload the window so dynatable updates itself
					window.location.href = window.location.href+'?page=multisite_plugin_stats';
				},
				error: function(response) {
					alert('ERROR:' + response);
				}
			});
		});

		$('input#delete-plugins').click(function (e) {
			e.preventDefault();
			$plugins = $('form#delete-plugins').serialize()
			if ($plugins) {
				window.location.href = window.location.href+'?action2=delete-selected&_wpnonce='+ajax_object.nonce+'&'+$plugins;
			}else {
				alert("Choose some plugins to delete!");
			}
		});
		
		
	});
})(jQuery);