/**
 * Sales Booster Carousel Initialization
 */
$(document).ready(function() {
    // Check if the element exists before trying to initialize
    if ($('.js-salesbooster-carousel').length > 0) {

        $('.js-salesbooster-carousel').slick({
            // --- Core Settings ---
            slidesToShow: 4,   // Number of items to show at once (adjust as needed)
            slidesToScroll: 1, // Number of items to scroll on arrow click

            // --- Ensure Arrows and Looping ---
            arrows: true,       // Explicitly ensure arrows are requested
            infinite: true,     // *** THIS IS KEY for looping *** Allows scrolling indefinitely

            // --- Other Optional Settings ---
            dots: false,        // Show pagination dots (set to true if you want them)
            autoplay: true,    // Set to true if you want automatic sliding
            autoplaySpeed: 2000, // Time in ms for autoplay

            // --- Custom Arrow HTML (Optional but recommended for styling) ---
            // If your theme uses Material Icons (common in PS 1.7+):
            prevArrow: '<button type="button" class="slick-prev slick-arrow"><i class="material-icons rtl-flip">keyboard_arrow_left</i></button>',
            nextArrow: '<button type="button" class="slick-next slick-arrow"><i class="material-icons rtl-flip">keyboard_arrow_right</i></button>',

            // --- Responsive Settings (keep these) ---
            responsive: [
                {
                    breakpoint: 1199,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 575,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
});