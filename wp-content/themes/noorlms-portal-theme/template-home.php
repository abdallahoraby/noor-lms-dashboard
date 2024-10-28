<?php
/**
 * Template Name: Home
 */
?>
<?php get_header(); ?>


<?php

    if (is_user_logged_in()):
        $user = wp_get_current_user();
        if ( in_array('student', (array) $user->roles) ):
            //get_template_part('template-parts/template-student-dashboard', null);
            // load template using ajax request
            $script_data = <<<SCRIPT
                <script>
                    jQuery(document).ready(function($) {
                        $('.ajax-loader-wrapper').show();
                            $.ajax({
                                url: ajax_object.ajax_url, // AJAX URL from wp_localize_script
                                type: 'POST',
                                data: {
                                    action: 'load_template_part', // The AJAX action we defined in PHP
                                    template_name: 'student-dashboard',
                                    security: ajax_object.nonce // Security nonce
                                },
                                success: function(response) {
                                    if (response.success) {
                                        // Append the loaded template part to the desired container
                                        $('.template-container').html(response.data);
                                    } else {
                                        $('.template-container').html('Try reloading the page.');
                                        console.log('[Error loading template part]');
                                    }
                                },
                                error: function() {
                                    console.log('[Error loading template part]');
                                },
                                complete: function() {
                                    // Hide loader when the AJAX call is complete
                                    $('.ajax-loader-wrapper').hide();
                                }
                            });
                        });
                </script>
            SCRIPT;

            echo $script_data;
        endif;
    else:
        // Redirect to the login page
        wp_redirect(wp_login_url());
        exit;
    endif;

?>

<?php get_footer(); ?>
