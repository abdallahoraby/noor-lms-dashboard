jQuery( document ).ready(function(){

    // Hide the "Expand All" button from PMS Account -> My Courses tab when there are no LearnDash Courses to be displayed
    if ( jQuery('#ld-main-course-list .ld-alert-warning').length > 0 ) {
        jQuery('#ld-profile .ld-item-list-actions .ld-expand-button').css('display', 'none');
    }

});