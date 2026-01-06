/**
 * Settings page JavaScript for Environment Indicator.
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize color pickers.
		if (typeof $.fn.wpColorPicker !== 'undefined') {
			$('.i365ei-color-picker').wpColorPicker();
		}

		// Toggle manual environment based on auto-detect checkbox.
		function toggleManualEnvironment() {
			if ($('#i365ei_auto_detect').is(':checked')) {
				$('#i365ei_manual_environment_wrapper').fadeTo(300, 0.5).find('input').prop('disabled', true);
			} else {
				$('#i365ei_manual_environment_wrapper').fadeTo(300, 1).find('input').prop('disabled', false);
			}
		}

		// Toggle custom labels inputs.
		function toggleCustomLabels() {
			if ($('#i365ei_custom_labels').is(':checked')) {
				$('#i365ei_custom_labels_inputs').slideDown(300);
			} else {
				$('#i365ei_custom_labels_inputs').slideUp(300);
			}
		}

		// Toggle custom colors inputs.
		function toggleCustomColors() {
			if ($('#i365ei_custom_colors').is(':checked')) {
				$('#i365ei_custom_colors_inputs').slideDown(300);
			} else {
				$('#i365ei_custom_colors_inputs').slideUp(300);
			}
		}

		// Toggle role visibility inputs.
		function toggleRoleVisibility() {
			if ($('#i365ei_role_visibility').is(':checked')) {
				$('#i365ei_role_visibility_inputs').slideDown(300);
			} else {
				$('#i365ei_role_visibility_inputs').slideUp(300);
			}
		}

		// Initialize on page load.
		toggleManualEnvironment();
		toggleCustomLabels();
		toggleCustomColors();
		toggleRoleVisibility();

		// Bind change events.
		$('#i365ei_auto_detect').on('change', toggleManualEnvironment);
		$('#i365ei_custom_labels').on('change', toggleCustomLabels);
		$('#i365ei_custom_colors').on('change', toggleCustomColors);
		$('#i365ei_role_visibility').on('change', toggleRoleVisibility);

		// Export settings functionality.
		$('#i365ei_export_settings').on('click', function(e) {
			e.preventDefault();

			var data = $('#i365ei_export_data').val();
			var blob = new Blob([data], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var link = document.createElement('a');
			var timestamp = new Date().toISOString().slice(0, 10);

			link.href = url;
			link.download = 'environment-indicator-settings-' + timestamp + '.json';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			URL.revokeObjectURL(url);
		});
	});

})(jQuery);
