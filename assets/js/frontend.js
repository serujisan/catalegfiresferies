/**
 * JavaScript del frontend - Catàleg Fires i Fèries
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Animación suave al hacer scroll a categorías
        $('.cff-categoria-link').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 800);
        });
        
        // Efecto de carga lazy para imágenes
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.classList.add('cff-loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('.cff-post-thumbnail img').forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    });
    
})(jQuery);
