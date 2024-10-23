<?php

    // get all achievements
    $achievements = get_posts(
        array(
            'post_type'  => 'achievement-type',
            'numberposts'      => -1,
            'post_status'    => 'publish'
        )
    );

?>

<main class="l-main">
    <div class="content-wrapper content-wrapper--with-bg">

        <div class="page-content page-template-dashboard">
            <section class="dashboard-header dashboard-wrapper">

                <!-- content here -->

                <h3 class="page-title"> Achievements </h3>

                <?php if( empty($achievements) ): ?>
                    <h3 class="text-center"> No achievements available </h3>
                <?php else: ?>

                    <ul class="nav nav-tabs nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-tab-id="in-progress-achiv" data-toggle="tab" href="#">In Progress</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab-id="completed-achiv" data-toggle="tab" href="#">Completed</a>
                        </li>

                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="in-progress-achiv">

                            <div class="row gap-3">



                    <?php foreach ( $achievements as $achievement ): ?>
                            <?php
                                if (has_post_thumbnail( $achievement->ID ) ):
                                    $achievement_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $achievement->ID ), 'single-post-thumbnail' );
                                    $achievement_thumbnail_url = $achievement_thumbnail[0];
                                else:
                                    $achievement_thumbnail_url = '';
                                endif;
                            ?>

                        <div class="col-lg-6 col-md-6 col-sm-12 col-12 achievement-card">
                            <img src="<?= $achievement_thumbnail_url ?>" alt="">
                            <div>
                                <h3> <?= $achievement->post_name ?> </h3>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 65%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <p> Lorem ipsum dolor sit amet, consectetur adipisicing elit. </p>
                            </div>
                        </div>

                    <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>




                <!-- end content -->


            </section>
        </div>
    </div>
</main>