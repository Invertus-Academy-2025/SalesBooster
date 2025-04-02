/**
 * Sales Booster Carousel Initialization
 */
$(document).ready(function() {
    if ($('.js-salesbooster-carousel').length > 0) {

        $('.js-salesbooster-carousel').slick({
            slidesToShow: 4,
            slidesToScroll: 1,

            arrows: true,
            infinite: true,

            dots: false,
            autoplay: true,
            autoplaySpeed: 2000,

            prevArrow: '<button type="button" class="slick-prev slick-arrow"><i class="material-icons rtl-flip">keyboard_arrow_left</i></button>',
            nextArrow: '<button type="button" class="slick-next slick-arrow"><i class="material-icons rtl-flip">keyboard_arrow_right</i></button>',

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
