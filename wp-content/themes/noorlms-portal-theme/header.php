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
                <div class="c-header-icon logout">
                    <?= do_shortcode('[ajax_logout_button]') ?>
                </div>
            </div>
        </div>
    </header>
    <div class="l-sidebar">
        <div class="logo">
            <div class="logo__txt">
                <img alt="" src="<?= get_stylesheet_directory_uri() ?>/assets/images/logo.png">
            </div>
        </div>
        <div class="l-sidebar__content">
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
                    <li class="c-menu__item load-template-part" data-toggle="tooltip" title="Settings" data-template-name="settings">
                        <a href="#settings" class="c-menu__item__inner"><i class="fa fa-cogs"></i>
                            <span class="c-menu-item__title">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div id="content" class="site-content template-container">


