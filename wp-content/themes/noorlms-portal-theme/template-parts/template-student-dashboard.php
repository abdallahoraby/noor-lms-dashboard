<?php

    $user = get_user_by('id', get_current_user_id());
    $role_name = '';
    if( in_array('student', (array) $user->roles) ):
        $role_name = 'Student';
    endif;


?>

<main class="l-main">
    <div class="content-wrapper content-wrapper--with-bg">
        <div class="page-content page-template-dashboard">
            <section class="dashboard-header dashboard-wrapper">

                <div class="dashboard-user" >
                    <div class="dashboard-user__profile" >

                        <div class="avatar-container" >
                            <a href="#" class="edit-avatar"> <i class="fas fa-pen-fancy"></i> </a>
                            <?php //bp_get_template_part( 'members/single/profile/change-avatar' ); ?>
                        </div>

                        <div class="dashboard-user__welcome" >

                            <div class="name-classroom" >
                                <h1> <?= $user->data->display_name ?> </h1>

                                <div class="classrooms" ><div class="dashboard-user__classrooms" ><a class="dashboard-user__classroom" href="#"> <?= $role_name ?> </a></div></div>
                            </div>

                            <div class="divider" ></div>

                            <div class="actions" >


                                <div class="assigned-tasks action practice-btn" >
                                    <img class="action-img" src="<?= get_stylesheet_directory_uri() ?>/assets/images/book.png" alt="View Courses">

                                    <h3> Have you practiced today? </h3>
                                    <a class="btn-view-tasks" href="#course-library">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>


                                <div class="assigned-tasks load-template-courses action">
                                    <img class="action-img" src="<?= get_stylesheet_directory_uri() ?>/assets/images/online-course.png" alt="View Courses">

                                    <h3>View your courses.</h3>
                                    <a class="btn-view-tasks" href="#course-library">
                                        <i class="fas fa-check"></i>
                                    </a>
                                </div>


                            </div>

                        </div>
                    </div>
                </div>
            </section>

            <section class="dashboard-middle-content">
                <div class="row">
                    <div class="col-md-8 col-sm-12 col-12">
                        <div class="d-flex flex-column gap-3">
                            <div class="pickup-what-left">
                                <div class="d-flex gap-3 align-items-center">
                                    <img src="../assets/images/video-conference.png" alt="">
                                    <h2> Pickup where you left off... </h2>
                                </div>

                                <?php
                                    // get free trial courses
                                    $category_slug = 'free-trial';
                                    $free_trial_courses = get_courses_by_category_slug($category_slug);
                                    if( !empty($free_trial_courses) ):
                                        foreach ($free_trial_courses as $free_trial_course):
                                            $free_trial_courses_ids[] = $free_trial_course['id'];
                                        endforeach;
                                    else:
                                        $free_trial_courses_ids = [];
                                    endif;
                                    $user_id = get_current_user_id();
                                    $user_courses = get_user_enrolled_courses($user_id);
                                    $user_courses = array_unique(array_merge($user_courses, $free_trial_courses_ids));
                                ?>

                                <?php if( empty($user_courses) ): ?>
                                    <div class="text-center"> No courses available. </div>
                                <?php else: ?>

                                    <div class="d-flex gap-5 home-courses splide">
                                        <div class="splide__track">
                                            <div class="splide__list">

                                                <?php
                                                    foreach ( $user_courses as $user_course_id ):
                                                        $course_permalink = get_the_permalink($user_course_id);
                                                        $course_thumbnail = get_the_post_thumbnail_url($user_course_id);
                                                        $course_title = get_the_title($user_course_id);
                                                ?>
                                                    <a href="<?= $course_permalink ?>" class="latest-course splide__slide">
                                                        <h4> <?= $course_title ?> </h4>
                                                        <img src="<?= $course_thumbnail ?>" alt="">
                                                    </a>
                                                <?php endforeach; ?>

                                            </div>
                                        </div>
                                    </div>

                                <?php endif; ?>

                            </div>
                        </div>

                        <div class="learn-today">
                            <div class="heading-with-icon d-flex gap-3 align-items-center">
                                <img src="../assets/images/book-icon.svg" alt="">
                                <h3> What will you learn today? </h3>
                            </div>

                            <div class="d-flex gap-5 justify-content-between today-courses">
                                <div class="course-of-today d-flex flex-column gap-3 justify-content-between">
                                    <h3> Islamic Studies </h3>
                                    <a href="#courses" class="btn btn-light load-template-courses"> Start </a>
                                </div>

                                <div class="course-of-today light-blue d-flex flex-column gap-3 justify-content-between">
                                    <h3> Learn to Read </h3>
                                    <a href="#courses" class="btn btn-light load-template-courses" > Start </a>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="col-md-4 col-sm-12 col-12 px-4" id="practice-section">
                        <?php get_template_part('template-parts/template-practice-module') ?>
                    </div>

                </div>

            </section>

            <section class="dashboard-footer-content mt-5">
                <div class="row">
                    <div class="col-md-8">
                        <div class="my-goals">
                            <div class="d-flex justify-content-start gap-3 align-items-center">
                                <div class="heading-with-icon d-flex gap-3 align-items-center">
                                    <img src="<?= get_stylesheet_directory_uri() ?>/assets/images/coin.png" alt="">
                                    <h3> Goals </h3>
                                </div>
                                <div class="goals-score">
                                    3/10
                                </div>
                            </div>
                            <div class="goals-wrapper">
                                <p> Complete your goals and earn coins. </p>
                                <ul class="goals-check">
                                    <li class="d-flex justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-check active"></i>
                                            Post in Classroom
                                        </div>
                                        <span> <img src="<?= get_stylesheet_directory_uri() ?>/assets/images/coin.png" alt=""> 20 </span>
                                    </li>
                                    <li class="d-flex justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-check"></i>
                                            Log a Practice Session
                                        </div>
                                        <span> <img src="<?= get_stylesheet_directory_uri() ?>/assets/images/coin.png" alt=""> 250 </span>
                                    </li>
                                    <li class="d-flex justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-check active"></i>
                                            Start a Course
                                        </div>
                                        <span> <img src="<?= get_stylesheet_directory_uri() ?>/assets/images/coin.png" alt=""> 200 </span>
                                    </li>
                                    <li class="d-flex justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-check active"></i>
                                            Attempt a Quiz
                                        </div>
                                        <span> <img src="<?= get_stylesheet_directory_uri() ?>/assets/images/coin.png" alt=""> 400 </span>
                                    </li>
                                    <li class="d-flex justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-check"></i>
                                            Update your avatar
                                        </div>
                                        <span> <img src="<?= get_stylesheet_directory_uri() ?>/assets/images/coin.png" alt=""> 50 </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 px-4">
                        <div class="achievements-tracker">
                            <div class="d-flex justify-content-start gap-3 align-items-center">
                                <div class="heading-with-icon d-flex gap-3 align-items-center">
                                    <img src="../assets/images/firststep.png" alt="">
                                    <h3> Achievements </h3>
                                </div>
                                <div class="achievements-score">
                                    5/20
                                </div>
                            </div>

                            <div class="achievements-wrapper load-template-part" data-template-name="achievements">
                                <div class="row">
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
                                    <?php if( empty($achievements) ): ?>
                                        <h3 class="text-center"> No achievements available </h3>
                                    <?php else: ?>
                                        <?php foreach ( $achievements as $achievement ): ?>
                                            <?php
                                            if (has_post_thumbnail( $achievement->ID ) ):
                                                $achievement_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $achievement->ID ), 'single-post-thumbnail' );
                                                $achievement_thumbnail_url = $achievement_thumbnail[0];
                                            else:
                                                $achievement_thumbnail_url = '';
                                            endif;
                                            ?>

                                            <div class="col-lg-3 col-md-3 col-sm-4 col-6"><img src="<?= $achievement_thumbnail_url ?>" alt=""> </div>

                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                </div>

                                <a href="#" class="view-all-achievements"> View All </a>

                            </div>

                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</main>



<script>

    jQuery(document).ready(function($) {

        // splide default options
        let splideOptions = {
            type   : 'slide',  // Enable looping
            perPage: 2,       // Show 4 items at once
            perMove: 1,
            autoplay: true, // Enable autoplay
            interval: 3000,   // Time between slides (3 seconds)
            gap     : '1rem', // Space between slides
            pagination: true, // Disable pagination
            arrows: true,      // Enable navigation arrows
            speed: 300,
            paginationDirection: 'ltr',
            heightRatio: 0.14,
            easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
            drag: true,
            pauseOnHover: true,
            lazyLoad: 'sequential',
            breakpoints: {
                0: {
                    perPage: 1,
                },
                640: {
                    perPage: 1,
                },
                1600: {
                    perPage: 2,
                }
            }
        };


        if (jQuery('.splide').length) {
            new Splide('.splide', splideOptions).mount();
        }


    });
</script>