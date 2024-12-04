<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package BuddyBoss_Theme
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>


<div id="page" class="site">

    <div class="ajax-loader-wrapper">
        <div id="ajax-loader"><div class="spinner"></div></div>
    </div>


    <header class="l-header">
        <div class="l-header__inner clearfix">
            <div class="c-header-icon js-hamburger">
                <div class="hamburger-toggle"><span class="bar-top"></span><span class="bar-mid"></span><span
                        class="bar-bot"></span></div>
            </div>

            <div class="c-search">
                <input class="c-search__input u-input" placeholder="Search..." type="text"/>
            </div>
            <div class="header-icons-group">

                <?php
                if (is_user_logged_in()) {

                    ?>
                    <ul id="bp-nav-menu-notifications-default" class="bp-nav-menu-submenu">
                        <?php
                        $notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
                        $count         = ! empty( $notifications ) ? count( $notifications ) : 0;
                        $alert_class   = (int) $count > 0 ? 'pending-count alert' : 'count no-alert';
                        $menu_title    = '<span id="ab-pending-notifications" class="' . $alert_class . '">' . number_format_i18n( $count ) . '</span>';
                        $menu_link     = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );
                        if ( ! empty( $notifications ) ) {
                            foreach ( (array) $notifications as $notification ) {
                                ?>
                                <li id="bp-nav-menu-notification-<?php echo $notification->id; ?>">
                                    <a class="bp-nav-menu-item" href="<?php echo $notification->href; ?>">
                                        <?php echo $notification->content; ?>
                                    </a>
                                </li>
                                <?php
                            }
                        } else {
                            ?>
                            <li id="bp-nav-menu-no-notifications">
                                <a class="bp-nav-menu-item" href="<?php echo $menu_link; ?>">
                                    <?php echo __( 'No new notifications', 'buddypress' ); ?>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                }
                ?>

                <div class="c-header-icon logout">
                    <?php if( is_user_logged_in() ): ?>
                        <?= do_shortcode('[ajax_logout_button]') ?>
                    <?php endif;?>
                </div>
            </div>
        </div>
    </header>
    <div class="l-sidebar">
        <div class="logo">
            <a href="<?= site_url() ?>" class="logo__txt">
                <img alt="" src="<?= get_stylesheet_directory_uri() ?>/assets/images/logo.png">
            </a>
        </div>
        <div class="l-sidebar__content">
            <?php if( is_user_logged_in() ): ?>
            <nav class="c-menu js-menu">
                <ul class="u-list">
                    <li class="c-menu__item is-active load-template-part" data-toggle="tooltip" title="Dashboard" data-template-name="student-dashboard">
                        <a href="#dashboard" class="c-menu__item__inner"><i class="fa fa-home"></i>
                            <span class="c-menu-item__title"> Dashboard </span>
                        </a>
                    </li>
                    <li class="c-menu__item load-template-part" data-toggle="tooltip" title="Courses" data-template-name="courses">
                        <a href="#courses" class="c-menu__item__inner"><i class="fa fa-puzzle-piece"></i>
                            <span class="c-menu-item__title">Courses</span>
                        </a>
                    </li>

                    <li class="c-menu__item load-template-part" data-toggle="tooltip" title="Settings" data-template-name="achievements">
                        <a href="#achievements" class="c-menu__item__inner"><i class="fa-solid fa-trophy"></i>
                            <span class="c-menu-item__title">Achievements</span>
                        </a>
                    </li>
                    <li class="c-menu__item load-template-part" data-toggle="tooltip" title="Settings" data-template-name="membership">
                        <a href="#" class="c-menu__item__inner"><i class="fas fa-credit-card"></i>
                            <span class="c-menu-item__title">My Subscriptions</span>
                        </a>
                    </li>
                    <li class="c-menu__item load-template-part" data-toggle="tooltip" title="Settings" data-template-name="settings">
                        <a href="#settings" class="c-menu__item__inner"><i class="fa fa-cogs"></i>
                            <span class="c-menu-item__title">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif;?>
        </div>
    </div>

    <div id="content" class="site-content template-container">


