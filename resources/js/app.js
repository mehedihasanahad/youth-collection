import './bootstrap';
import Swiper from 'swiper/bundle';

window.Swiper = Swiper;

new Swiper('.best-deals-swiper', {
    slidesPerView: 1.5,
    spaceBetween: 14,
    navigation: {
        nextEl: '.best-deals-swiper-next',
        prevEl: '.best-deals-swiper-prev',
    },
    breakpoints: {
        480:  { slidesPerView: 2.2, spaceBetween: 14 },
        640:  { slidesPerView: 3,   spaceBetween: 16 },
        1024: { slidesPerView: 4,   spaceBetween: 16 },
        1280: { slidesPerView: 5,   spaceBetween: 18 },
    },
});


new Swiper('.reviews-swiper', {
    slidesPerView: 1.2,
    spaceBetween: 14,
    grabCursor: true,
    breakpoints: {
        480:  { slidesPerView: 2,   spaceBetween: 14 },
        640:  { slidesPerView: 2.5, spaceBetween: 16 },
        1024: { slidesPerView: 3,   spaceBetween: 16 },
        1280: { slidesPerView: 4,   spaceBetween: 18 },
    },
});

new Swiper('#heroSwiper', {
    slidesPerView: 1,
    speed: 600,
    loop: false,
    rewind: true,
    autoplay: {
        delay: 4500,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
    },
    navigation: {
        nextEl: '#heroSwiper .swiper-button-next',
        prevEl: '#heroSwiper .swiper-button-prev',
    },
    pagination: {
        el: '#heroSwiper .swiper-pagination',
        clickable: true,
        renderBullet: function (index, className) {
            return '<span class="' + className + '">' + String(index + 1).padStart(2, '0') + '</span>';
        },
    },
});
