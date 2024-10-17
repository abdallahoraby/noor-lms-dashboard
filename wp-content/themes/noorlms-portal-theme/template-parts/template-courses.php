<?php
/**
 * Template for displaying courses list.\
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

                <h3 class="page-title"> Courses </h3>

                <div class="courses-main">
                    <div class="courses-filter" >
                        <div class="courses-filter__inner" >
                            <div class="searchbar" >
                                <input type="text" name="search" class="input-search" placeholder="Search..." autocomplete="off">
                                <div class="filters" >
                                    <a href="#" class="filter-menu status">
                                        <span data-val="">Status</span>
                                        <ul style="display: none;">
                                            <li class="optstatus" data-val="not_started">Not Yet Started</li>
                                            <li class="optstatus" data-val="in_progress">In Progress</li>
                                            <li class="optstatus" data-val="completed">Complete</li>
                                        </ul>
                                    </a>

                                    <a href="#" class="filter-menu subject">
                                        <span data-val="">Subject</span>
                                        <?php
                                            $course_categories = get_terms( array(
                                                'taxonomy' => 'course_category',
                                                'hide_empty' => true
                                            ) );

                                            if( !empty($course_categories) ):

                                        ?>

                                        <ul class="categories-dp" style="display: none;">
                                            <?php
                                                foreach ( $course_categories as $course_category ):
                                                    $course_category_id = $course_category->term_id;
                                                    $course_category_slug = $course_category->slug;
                                                    $course_category_name = $course_category->name;
                                            ?>
                                            <li class="optsubject" data-course-cat-id="<?= $course_category_id ?>"> <?= $course_category_name ?> </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php endif; ?>
                                    </a>

                                </div>
                            </div>
                            <!-- end searchbar -->

                        </div>
                    </div>

                    <?php
                        $user_id = get_current_user_id();
                        $user_courses = get_user_enrolled_courses($user_id);
                    ?>

                    <?php if( empty($user_courses) ): ?>
                        <div class="text-center"> No courses available. </div>
                    <?php else: ?>

                    <?php
                        foreach ( $user_courses as $user_course_id ):
                            $term_obj_list = get_the_terms( $user_course_id, 'course_category' );
                            if( !empty( $term_obj_list ) ):
                                $courses_list[] = array(
                                    'course_category_id' => $term_obj_list[0]->term_id,
                                    'course_category_name' => $term_obj_list[0]->name,
                                    'course_id' => $user_course_id
                                );
                            endif;
                        endforeach;


                        $groupedCourses = [];

                        foreach ($courses_list as $course):
                            $category = $course['course_category_id'];

                            if (!isset($groupedCourses[$category])) {
                                $groupedCourses[$category] = [];
                            }

                            $groupedCourses[$category][] = array(
                                'category_id' => $course['course_category_id'],
                                'category_name' => $course['course_category_name'],
                                'course_id' => $course['course_id'],
                            );
                        endforeach;

                    ?>
                        <div class="courses-container">

                        <?php
                        if( !empty($groupedCourses) ):
                            foreach ( $groupedCourses as $user_course_category_id ):
                                $category_name = $user_course_category_id[0]['category_name'];
                                $category_id = $user_course_category_id[0]['category_id'];
                        ?>


                            <div class="courses-wrapper splide" id="course_cat_<?= $category_id ?>">
                                <h3> <?= $category_name ?> </h3>

                                <div class="courses-slider splide__track">
                                    <div class="splide__list">
                                        <?php
                                            foreach ( $user_course_category_id as $user_course ):
                                                $course_permalink = get_the_permalink($user_course['course_id']);
                                                $course_thumbnail = get_the_post_thumbnail_url($user_course['course_id']);
                                        ?>
                                            <div class="single-course splide__slide" style="background: url(<?= $course_thumbnail ?>)">
                                                <span> start course </span>
                                                <a href="<?= $course_permalink ?>"> <i class="fa-solid fa-play"></i> </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                </div>
                            </div>


                            <?php endforeach; ?>
                        <?php endif; ?>

                        </div>

                    <?php endif; ?>




                </div>

                <!-- end content -->

            </section>
        </div>
    </div>
</main>


<script>

    jQuery(document).ready(function($) {

        // splide default options
        let splideOptions = {
            type   : 'slide',  // Enable looping
            perPage: 4,       // Show 4 items at once
            perMove: 1,
            autoplay: false, // Enable autoplay
            interval: 3000,   // Time between slides (3 seconds)
            gap     : '1rem', // Space between slides
            pagination: false, // Disable pagination
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
                    perPage: 4,
                }
            }
        };


        if (jQuery('.splide').length) {
            jQuery('.splide').each( function (){
                new Splide('#'+$(this).attr('id'), splideOptions).mount();
            });
        }

        // toggle show/hide for courses select
        jQuery('.filter-menu').on('click', function() {
            jQuery('.filter-menu').not(this).find('ul').hide();
            jQuery(this).find('ul').toggle();
        });

    });
</script>