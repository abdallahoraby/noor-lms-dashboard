(function( $ ) {

    // Listen for our change to our trigger type selection
    $('.requirements-list').on( 'change', '.select-trigger-type', function() {

        // Grab our selected trigger type and achievement selector
        var trigger_type = $(this).val();
        var score_input = $(this).siblings('.qsm-quiz-score');
        var min_score_input = $(this).siblings('.qsm-quiz-min-score');
        var max_score_input = $(this).siblings('.qsm-quiz-max-score');
        
        // Toggle score field visibility
        if(
            trigger_type === 'gamipress_qsm_complete_quiz_score'
            || trigger_type === 'gamipress_qsm_complete_specific_quiz_score'
            || trigger_type === 'gamipress_qsm_complete_quiz_max_score'
            || trigger_type === 'gamipress_qsm_complete_specific_quiz_max_score'
        ) {
            score_input.show();
        }else {
            score_input.hide();
        }

        // Toggle min and max score fields visibility
        if(
            trigger_type === 'gamipress_qsm_complete_quiz_between_score'
            || trigger_type === 'gamipress_qsm_complete_specifc_quiz_between_score'
        ) {
            min_score_input.show();
            max_score_input.show();
        }else {
            min_score_input.hide();
            max_score_input.hide();
        }

    } );

    // Loop requirement list items to show/hide score input on initial load
    $('.requirements-list li').each(function() {

        // Grab our selected trigger type and achievement selector
        var trigger_type = $(this).find('.select-trigger-type').val();
        var score_input = $(this).find('.qsm-quiz-score');
        var min_score_input = $(this).find('.qsm-quiz-min-score');
        var max_score_input = $(this).find('.qsm-quiz-max-score');

        // Toggle score fields visibility
        if(
            trigger_type === 'gamipress_qsm_complete_quiz_score'
            || trigger_type === 'gamipress_qsm_complete_specific_quiz_score'
            || trigger_type === 'gamipress_qsm_complete_quiz_max_score'
            || trigger_type === 'gamipress_qsm_complete_specific_quiz_max_score'
        ) { 
            score_input.show();
        }else{
            score_input.hide();
        }

        // Toggle min and max score fields visibility
        if(
            trigger_type === 'gamipress_qsm_complete_quiz_between_score'
            || trigger_type === 'gamipress_qsm_complete_specifc_quiz_between_score'
        ) {
            min_score_input.show();
            max_score_input.show();
        }else {
            min_score_input.hide();
            max_score_input.hide();
        }

    });

    $('.requirements-list').on( 'update_requirement_data', '.requirement-row', function(e, requirement_details, requirement) {
        
        // Add score field
        if(
            requirement_details.trigger_type === 'gamipress_qsm_complete_quiz_score'
            || requirement_details.trigger_type === 'gamipress_qsm_complete_specific_quiz_score'
            || requirement_details.trigger_type === 'gamipress_qsm_complete_quiz_max_score'
            || requirement_details.trigger_type === 'gamipress_qsm_complete_specific_quiz_max_score'
        ) {
            requirement_details.qsm_score = requirement.find( '.qsm-quiz-score input' ).val()
        }
        
        // Add min and max score fields
        if(
            requirement_details.trigger_type === 'gamipress_qsm_complete_quiz_between_score'
            || requirement_details.trigger_type === 'gamipress_qsm_complete_specifc_quiz_between_score'
        ) {
            requirement_details.qsm_min_score = requirement.find( '.qsm-quiz-min-score input' ).val();
            requirement_details.qsm_max_score = requirement.find( '.qsm-quiz-max-score input' ).val();   
        }

    } );

})( jQuery );