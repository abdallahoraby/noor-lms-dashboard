<?php
/**
 * Template for displaying User Subscriptions.\
 * @author  Abdallah Oraby
 * @package NoorLMS Portal Theme
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

?>

<main class="l-main">
    <div class="content-wrapper content-wrapper--with-bg">

        <div class="page-content page-template-dashboard">
            <section class="dashboard-header dashboard-wrapper">

                <!-- content here -->
                <h3 class="page-title"> Subscriptions </h3>

                <?php
                    $all_subscriptions = pms_get_subscription_plans();
                    if(!empty($all_subscriptions)):
                        $all_subscriptions_ids = array_column($all_subscriptions, 'id');
                    endif;
                ?>

                <?php if( pms_is_member_of_plan( $all_subscriptions_ids ) ): ?>

                <?= do_shortcode('[pms-account]') ?>

                <?php else:?>

                    <div class="d-flex flex-column justify-content-center align-items-center mt-5 mb-5">
                        <h3 class="text-center"> Sorry, you don't have any subscriptions now. </h3>
                        <a href="https://noorworld.com/pricing" class="button button-primary mt-3"> Subscribe NOW ! </a>
                    </div>

                <?php endif; ?>

            </section>

        </div>
    </div>
</main>

<div id="modalContent" style="display: none">

    <div class="form-group">
        <label for="cancel-reason">Why You need to cancel your subscription?</label>
        <select class="form-control" id="cancel-reason">
            <option value="cancel-1">Service no longer meets my needs.</option>
            <option value="cancel-2">Found a better alternative.</option>
            <option value="cancel-3">Too expensive/cost is too high.</option>
            <option value="cancel-4">Not using the service enough.</option>
            <option value="cancel-5">Difficult to use/poor user experience.</option>
            <option value="cancel-6">Technical issues or bugs.</option>
            <option value="cancel-7">Lack of features I need.</option>
            <option value="cancel-8">Customer service didn’t meet my expectations.</option>
            <option value="cancel-9">Privacy or security concerns.</option>
            <option value="cancel-10">Temporary subscription; no longer needed.</option>
            <option value="cancel-11">Moving to a location where the service isn’t available.</option>
            <option value="cancel-12">Other (Please specify below)</option>

        </select>
    </div>

    <div class="form-group">
        <label for="cancel-other-reason">Comment:</label>
        <textarea class="form-control" id="cancel-other-reason" rows="3"></textarea>
    </div>

</div>



<input type="hidden" id="cancel-url" value="<?= pms_get_cancel_url() ?>">
<input type="hidden" id="user_id" value="<?= get_current_user_id() ?>">


<script>

    jQuery(document).ready(function($) {

        // show support popup for cancel subscription
        jQuery('a.pms-account-subscription-action-link.pms-account-subscription-action-link__cancel').on('click', function(e) {
            e.preventDefault();
            let pre = document.createElement('div');
            //custom style.
            pre.style.maxHeight = "auto";
            pre.style.margin = "0";
            pre.style.padding = "24px";
            pre.innerHTML = $('#modalContent').html();
            // pre.appendChild(document.createTextNode($('#modalContent').html()));
            //show as confirm
            alertify.confirm(pre, function(){
                let cancel_url = $('#cancel-url').val();
                let user_id = $('#user_id').val();
                let cancel_reason = $(pre).find('#cancel-reason option:selected').text();
                let cancel_comment = $(pre).find('#cancel-other-reason').val();
                // confirm user cancellation
                $.ajax({
                    url: ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'add_log',
                        user_id: user_id,
                        cancel_reason: cancel_reason,
                        cancel_comment: cancel_comment
                    },
                    success: function(response) {
                        if (response.success) {
                            alertify.success('Data saved successfully');
                            window.location.href = cancel_url;
                        }
                    }
                });
            },function(){
                alertify.error('Closed');
            }).set({labels:{ok:'Proceed to Cancel', cancel: 'Decline'}, padding: false})
                .set('closable', false)
                .set({'closableByDimmer': false})
                .set('movable', false);
        });

    });
</script>