/**
 * Initialise chosen
 *
 */

jQuery(document).ready(function() {
    jQuery('#restricted-file-types').chosen();

    var chosen = jQuery('#restricted-file-types').data('chosen');
    var chosenSearch = chosen.search_field;

    jQuery(chosenSearch).on('keyup', function(event) {
        if (event.keyCode === 13) { // Enter key
            var newOption = jQuery(this).val();
            if (newOption !== '') {
                // Check if the option already exists
                if (jQuery('#restricted-file-types option[value="' + newOption + '"]').length === 0) {
                    // Add the new option to the select element
                    jQuery('#restricted-file-types').append('<option value="' + newOption + '" selected>' + newOption + '</option>');
                    // Trigger chosen:updated to refresh the Chosen UI
                    jQuery('#restricted-file-types').trigger('chosen:updated');
                }
                // Clear the input field
                jQuery(this).val('');
            }
        }
    });

    var $checkbox = jQuery('#file-restriction-enable');
    var $select = jQuery('#restricted-file-types');

    // Initialize Chosen on your select
    $select.chosen();

    // Function to toggle the select disabled state via Chosen
    function toggleSelect() {
        // Check the checkbox state and apply the disabled property
        if ($checkbox.is(':checked')) {

            jQuery('#pms-restricted-file-types').css({
                'pointer-events': 'none',
                'opacity': '0.5',
            });
        } else {

            jQuery('#pms-restricted-file-types').css({
                'pointer-events': 'all',
                'opacity': '1',
            });
        }
    }

    // Initial check to set the state when the page loads
    toggleSelect();

    // Update in real-time when the checkbox changes
    $checkbox.change(toggleSelect);
});

// Function that copies the shortcode from a text
jQuery(document).ready(function() {
    jQuery('.pms-shortcode_copy-text').click(function (e) {
        e.preventDefault();

        navigator.clipboard.writeText(jQuery(this).text());

        // Show copy message
        var copyMessage = jQuery(this).next('.pms-copy-message');
        copyMessage.fadeIn(400).delay(2000).fadeOut(400);

    })
});
