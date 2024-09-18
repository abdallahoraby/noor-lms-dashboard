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
    height: 250,
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


document.addEventListener('DOMContentLoaded', function () {

    if( jQuery('.islamic.splide').length ){
        new Splide('.islamic.splide', splideOptions).mount();
    }

    if( jQuery('.quran.splide').length ){
        new Splide('.quran.splide', {
            type   : 'slide',
            perPage: 4,
            perMove: 1,
            autoplay: false,
            interval: 3000,
            gap     : '1rem',
            pagination: false,
            arrows: true,
            paginationDirection: 'rtl',
            speed: 300,
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
        }).mount();
    }


});

/* start tabs function */
$(function() {
    "use strict";

    $('a[href*="#"]').on('click', function(event) {
        {
            // Figure out element to scroll to
            var target = $(this);
            target = target.length ? target : $('[name=' + this.slice(1) + ']');
            // Does a scroll target exist?
            if (target.length) {
                // Only prevent default if animation is actually gonna happen
                event.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top
                }, 1000, function() {
                    // Callback after animation
                    // Must change focus!
                    var $target = $(target);
                    $target.focus();
                    if ($target.is(":focus")) { // Checking if the target was focused
                        return false;
                    } else {
                        $target.attr('tabindex', '-1'); // Adding tabindex for elements not focusable
                        $target.focus(); // Set focus again
                    };
                });
            }
        }
    });

}); /* End tabs function */