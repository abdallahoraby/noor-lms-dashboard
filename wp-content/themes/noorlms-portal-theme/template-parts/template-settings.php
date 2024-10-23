<main class="l-main">
    <div class="content-wrapper content-wrapper--with-bg">

        <div class="page-content page-template-dashboard">
            <section class="dashboard-header dashboard-wrapper">

                <!-- content here -->

                <h3 class="page-title"> Settings </h3>

                <div class="tab-settings">
                    <ul class="nav nav-tabs nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-tab-id="login-info" data-toggle="tab" href="#"> Login Information </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab-id="notifications" data-toggle="tab" href="#"> Notification Preferences </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab-id="account-info" data-toggle="tab" href="#"> Account Information </a>
                        </li>

                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="login-info">
                            <?php bp_get_template_part( 'members/single/settings/general' ); ?>
                        </div>
                        <div class="tab-pane" id="notifications">
                            <?php bp_get_template_part( 'members/single/settings/notifications' ); ?>
                        </div>

                        <div class="tab-pane" id="account-info">
                            <?php bp_get_template_part( 'members/single/settings/profile' ); ?>
                        </div>
                    </div>

                </div>



                <!-- end content -->

            </section>
        </div>
    </div>
</main>


<script>
    /* start tabs function */
    $(function() {
        "use strict";

        jQuery('.nav-link').on('click', function (){

            let tab_id = jQuery(this).data('tab-id');

            jQuery('.tab-pane').removeClass('active');
            jQuery('#'+tab_id).addClass('active').slideDown();

            jQuery('.nav-link').removeClass('active');
            jQuery(this).addClass('active');

        });

    }); /* End tabs function */
</script>