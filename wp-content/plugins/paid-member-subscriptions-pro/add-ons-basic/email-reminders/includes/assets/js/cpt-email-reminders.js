/**
 * JS for Email Reminders admin cpt screen
 *
 */

jQuery( function($) {

    // When publishing or updating the Email Reminder must have a title
    $(document).on('click', '#publish, #save-post', function() {

        var emailReminderTitle = $('#title').val().trim();

        if ( emailReminderTitle === '' ) {

            alert('Email Reminder must have a name.');
            
            return false;

        }

    });

    // Select Available Tags for Email Reminder on click
    $('.cozmoslabs-tags-list input').click( function() {
        this.select();
    });

    $('.cozmoslabs-tags-list-heading').on('click', function() {
        let tagsList = $(this).siblings('.cozmoslabs-tags-list');

        // Hide/Show Tags List
        if (tagsList.css('display') === 'none') {
            tagsList.css('display', 'grid');
        } else {
            tagsList.css('display', 'none');
        }

        // Mark the Heading if the Tags List is opened
        $(this).toggleClass('cozmoslabs-tags-list-open', tagsList.is(':visible'));
    });


    // Change "Publish" button text
    $(document).ready( function(){

        $('input#publish').val('Save Email Reminder');

    });

    // Show the admin emails list if the Send To select has the value "admin"
    $(document).ready( function(){

        if( $('#pms-email-reminder-send-to').val() === 'admin' ) {
            $('#pms-email-reminder-admin-emails-wrapper').css('display', 'flex');
        } else {
            $('#pms-email-reminder-admin-emails-wrapper').hide();
        }

    });

    // Show / hide the admin emails list when the Send To select changes
    $(document).on( 'change', '#pms-email-reminder-send-to', function(){

        if( $(this).val() === 'admin' ) {
            $('#pms-email-reminder-admin-emails-wrapper').css('display', 'flex');
        } else {
            $('#pms-email-reminder-admin-emails-wrapper').hide();
        }

    });

    /**
     * Add Link to PMS Docs next to page title
     * */
    $(document).ready( function (){
        $(function(){
            $('.wp-admin.edit-php.post-type-pms-email-reminders .wrap .wp-heading-inline').append('<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/email-reminders/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>');
        });
    });

    // Show hide the correct Trigger Event line based on the Trigger Type selection
    $(document).ready( function(){

        if( $( 'input[name="pms_email_reminder_trigger_type"]:checked' ).val() === 'delayed' ) {
            $('.pms-email-reminder-trigger-events-delayed').css('display', 'flex').attr( 'disabled', false );
            $('.pms-email-reminder-trigger-events-delayed select').attr( 'disabled', false );
            $('.pms-email-reminder-trigger-events-instant').hide();
            $('.pms-email-reminder-trigger-events-instant select').attr( 'disabled', true );
        } else if( $( 'input[name="pms_email_reminder_trigger_type"]:checked' ).val() === 'instant' ) {
            $('.pms-email-reminder-trigger-events-instant').css('display', 'flex');
            $('.pms-email-reminder-trigger-events-instant select').attr( 'disabled', false );
            $('.pms-email-reminder-trigger-events-delayed').hide();
            $('.pms-email-reminder-trigger-events-delayed select').attr( 'disabled', true );
        }

    });

    // Show / hide the admin emails list when the Send To select changes
    $(document).on( 'click', 'input[name="pms_email_reminder_trigger_type"]', function(){

        if( $(this).val() === 'delayed' ) {
            $('.pms-email-reminder-trigger-events-delayed').css('display', 'flex').attr( 'disabled', false );
            $('.pms-email-reminder-trigger-events-delayed select').attr( 'disabled', false );
            $('.pms-email-reminder-trigger-events-instant').hide();
            $('.pms-email-reminder-trigger-events-instant select').attr( 'disabled', true );
        } else if( $(this).val() === 'instant' ) {
            $('.pms-email-reminder-trigger-events-instant').css('display', 'flex');
            $('.pms-email-reminder-trigger-events-instant select').attr( 'disabled', false );
            $('.pms-email-reminder-trigger-events-delayed').hide();
            $('.pms-email-reminder-trigger-events-delayed select').attr( 'disabled', true );
        }

    });

});
