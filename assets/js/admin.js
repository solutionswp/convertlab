/**
 * Admin JavaScript
 *
 * @package ConvertLab
 * @since 1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		var fieldIndex = $('#convertlab-fields-container .convertlab-field-row').length;

		// Add new field
		$('#convertlab-add-field').on('click', function() {
			var fieldHtml = '<div class="convertlab-field-row" data-index="' + fieldIndex + '">' +
				'<select name="fields[' + fieldIndex + '][type]">' +
				'<option value="email">Email</option>' +
				'<option value="text">Text</option>' +
				'<option value="name">Name</option>' +
				'<option value="phone">Phone</option>' +
				'</select>' +
				'<input type="text" name="fields[' + fieldIndex + '][name]" placeholder="Field name" />' +
				'<input type="text" name="fields[' + fieldIndex + '][label]" placeholder="Label" />' +
				'<input type="text" name="fields[' + fieldIndex + '][placeholder]" placeholder="Placeholder" />' +
				'<label><input type="checkbox" name="fields[' + fieldIndex + '][required]" value="1" /> Required</label>' +
				'<button type="button" class="button button-small convertlab-remove-field">Remove</button>' +
				'</div>';

			$('#convertlab-fields-container').append(fieldHtml);
			fieldIndex++;
		});

		// Remove field
		$(document).on('click', '.convertlab-remove-field', function() {
			$(this).closest('.convertlab-field-row').remove();
		});
	});
})(jQuery);

