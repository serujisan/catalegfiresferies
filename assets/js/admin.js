/**
 * JavaScript del admin - Catàleg Fires i Fèries
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Manejo del formulario de importación RTF
        $('#cff-import-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'cff_import_rtf');
            formData.append('nonce', cffAjax.nonce);
            
            var $btn = $('#cff-import-btn');
            var $spinner = $('.spinner');
            var $result = $('#cff-import-result');
            
            // Mostrar spinner
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.removeClass('success error').html('');
            
            $.ajax({
                url: cffAjax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').html(
                            '<strong>Èxit!</strong> ' + response.data.message
                        );
                        
                        // Recargar la página después de 2 segundos para mostrar el contenido
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $result.addClass('error').html(
                            '<strong>Error:</strong> ' + response.data.message
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $result.addClass('error').html(
                        '<strong>Error:</strong> No s\'ha pogut importar l\'arxiu. ' + error
                    );
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Crear categorías
        $('#cff-create-categories-btn').on('click', function() {
            var $btn = $(this);
            var $spinner = $('#cff-action-spinner');
            var $result = $('#cff-action-result');
            
            if (!confirm('¿Estàs segur que vols crear les categories?')) {
                return;
            }
            
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.removeClass('success error').html('');
            
            $.ajax({
                url: cffAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cff_create_categories',
                    nonce: cffAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').html(
                            '<strong>Èxit!</strong> ' + response.data.message
                        );
                    } else {
                        $result.addClass('error').html(
                            '<strong>Error:</strong> ' + response.data.message
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $result.addClass('error').html(
                        '<strong>Error:</strong> No s\'ha pogut crear les categories. ' + error
                    );
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Importar posts
        $('#cff-import-posts-btn').on('click', function() {
            var $btn = $(this);
            var $spinner = $('#cff-action-spinner');
            var $result = $('#cff-action-result');
            
            if (!confirm('¿Vols importar els posts com a esborranys? Això pot trigar una estona.')) {
                return;
            }
            
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.removeClass('success error').html('<p>Important posts, si us plau espera...</p>');
            
            $.ajax({
                url: cffAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cff_import_posts',
                    nonce: cffAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').html(
                            '<strong>Èxit!</strong> ' + response.data.message
                        );
                    } else {
                        $result.addClass('error').html(
                            '<strong>Error:</strong> ' + response.data.message
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $result.addClass('error').html(
                        '<strong>Error:</strong> No s\'ha pogut importar els posts. ' + error
                    );
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
    });
    
})(jQuery);
