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


// toggle show/hide for courses select
jQuery('.filter-menu').on('click', function() {
    jQuery('.filter-menu').not(this).find('ul').hide();
    jQuery(this).find('ul').toggle();
});


$('.courses-slider').owlCarousel({
    loop:false,
    items:3,
    margin:20,
    responsiveClass:true,
    nav: true,
    dots: false,
    navText: [
        '<i class="fas fa-chevron-left"></i>',
        '<i class="fas fa-chevron-right"></i>'
    ],
    responsive:{
        0:{
            items:1,
            nav:true
        },
        600:{
            items:3,
            nav:true
        },
        1200:{
            items:4,
            nav:true,
        }
    }
});