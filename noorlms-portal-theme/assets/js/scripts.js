"use strict";

var Dashboard = function () {
    var global = {
        tooltipOptions: {
            placement: "right"
        },
        menuClass: ".c-menu"
    };

    var menuChangeActive = function menuChangeActive(el) {
        var hasSubmenu = $(el).hasClass("has-submenu");
        $(global.menuClass + " .is-active").removeClass("is-active");
        $(el).addClass("is-active");

        // if (hasSubmenu) {
        // 	$(el).find("ul").slideDown();
        // }
    };

    var sidebarChangeWidth = function sidebarChangeWidth() {
        var $menuItemsTitle = $("li .menu-item__title");

        $("body").toggleClass("sidebar-is-reduced sidebar-is-expanded");
        $(".hamburger-toggle").toggleClass("is-opened");

        if ($("body").hasClass("sidebar-is-expanded")) {
            $('[data-toggle="tooltip"]').tooltip("destroy");
        } else {
            $('[data-toggle="tooltip"]').tooltip(global.tooltipOptions);
        }
    };

    return {
        init: function init() {
            $(".js-hamburger").on("click", sidebarChangeWidth);

            $(".js-menu li").on("click", function (e) {
                menuChangeActive(e.currentTarget);
            });

            $('[data-toggle="tooltip"]').tooltip(global.tooltipOptions);
        }
    };
}();

Dashboard.init();
//# sourceURL=pen.js









document.addEventListener('DOMContentLoaded', function () {

    if( jQuery('.home-courses.splide').length ){
        new Splide('.home-courses.splide', {
            type   : 'loop',
            perPage: 3,
            perMove: 1,
            autoplay: true,
            interval: 3000,
            gap     : '3rem',
            pagination: true,
            arrows: false,
            speed: 300,
            paginationDirection: 'ltr',
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
                    perPage: 3,
                }
            }
        }).mount();
    }

});




let table = new DataTable('#attendance-reports-table');


