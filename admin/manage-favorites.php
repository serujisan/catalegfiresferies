<?php
/**
 * Página para gestionar posts favoritos por categoría
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener todas las categorías de WordPress
$categories = get_categories(array(
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));

// Categoría seleccionada
$selected_category = isset($_GET['cat']) ? intval($_GET['cat']) : 0;

?>
<div class="wrap">
    <h1><?php _e('Gestionar Posts Favorits', 'catalegfiresferies'); ?></h1>
    <p><?php _e('Selecciona una categoria i marca els posts com a favorits. Després podràs ordenar-los a la pàgina de Categories Pare.', 'catalegfiresferies'); ?></p>
    
    <!-- Selector de categoría -->
    <div class="cff-category-selector" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2><?php _e('Selecciona una Categoria', 'catalegfiresferies'); ?></h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="catalegfiresferies-manage-favorites">
            <select name="cat" id="category-select" class="regular-text" onchange="this.form.submit()">
                <option value="0"><?php _e('-- Tria una categoria --', 'catalegfiresferies'); ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat->term_id; ?>" <?php selected($selected_category, $cat->term_id); ?>>
                        <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?> posts)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    
    <?php if ($selected_category > 0): 
        $category = get_category($selected_category);
        if (!$category || is_wp_error($category)) {
            echo '<div class="notice notice-error"><p>Categoria no trobada.</p></div>';
            return;
        }
        
        // Obtener posts de esta categoría
        $posts = get_posts(array(
            'category' => $selected_category,
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        
        if (empty($posts)) {
            echo '<div class="notice notice-warning"><p>No hi ha posts publicats en aquesta categoria.</p></div>';
        } else {
            // Obtener favoritos actuales
            $favorites_table = $wpdb->prefix . 'cff_favorites';
            $current_favorites = $wpdb->get_col($wpdb->prepare(
                "SELECT post_id FROM $favorites_table WHERE wp_category_id = %d AND is_favorite = 1",
                $selected_category
            ));
    ?>
    
    <div class="cff-posts-manager" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>
            <?php printf(__('Posts de: %s', 'catalegfiresferies'), esc_html($category->name)); ?>
            <span style="color: #999; font-weight: normal;">(<?php echo count($posts); ?> posts)</span>
        </h2>
        
        <p>
            <button id="select-all" class="button"><?php _e('Marcar tots', 'catalegfiresferies'); ?></button>
            <button id="deselect-all" class="button"><?php _e('Desmarcar tots', 'catalegfiresferies'); ?></button>
            <button id="save-favorites" class="button button-primary" data-category-id="<?php echo $selected_category; ?>">
                <?php _e('Guardar Favorits', 'catalegfiresferies'); ?>
            </button>
        </p>
        
        <div class="cff-posts-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($posts as $post): 
                $is_favorite = in_array($post->ID, $current_favorites);
                $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium');
            ?>
                <div class="cff-post-card" style="border: 2px solid <?php echo $is_favorite ? '#2271b1' : '#ddd'; ?>; padding: 10px; border-radius: 5px; background: <?php echo $is_favorite ? '#f0f6fc' : 'white'; ?>;">
                    <label style="cursor: pointer; display: block;">
                        <input type="checkbox" 
                               class="cff-favorite-checkbox" 
                               data-post-id="<?php echo $post->ID; ?>"
                               <?php checked($is_favorite); ?>
                               style="margin-bottom: 10px;">
                        
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" 
                                 alt="<?php echo esc_attr($post->post_title); ?>" 
                                 style="width: 100%; height: 150px; object-fit: cover; border-radius: 3px; margin-bottom: 10px;">
                        <?php else: ?>
                            <div style="width: 100%; height: 150px; background: #f0f0f1; border-radius: 3px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #999;">
                                <?php _e('Sense imatge', 'catalegfiresferies'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <strong style="display: block; margin-bottom: 5px;"><?php echo esc_html($post->post_title); ?></strong>
                        
                        <div style="font-size: 12px; color: #666;">
                            ID: <?php echo $post->ID; ?><br>
                            <?php echo get_the_date('', $post->ID); ?>
                        </div>
                    </label>
                    
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                        <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small" target="_blank">
                            <?php _e('Editar', 'catalegfiresferies'); ?>
                        </a>
                        <a href="<?php echo get_permalink($post->ID); ?>" class="button button-small" target="_blank">
                            <?php _e('Veure', 'catalegfiresferies'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <p style="margin-top: 20px;">
            <button id="save-favorites-bottom" class="button button-primary" data-category-id="<?php echo $selected_category; ?>">
                <?php _e('Guardar Favorits', 'catalegfiresferies'); ?>
            </button>
        </p>
        
        <div id="save-message" style="display: none; margin-top: 15px; padding: 10px; background: #00a32a; color: white; border-radius: 3px;">
            <?php _e('Favorits guardats correctament!', 'catalegfiresferies'); ?>
        </div>
    </div>
    
    <?php 
        }
    endif; 
    ?>
</div>

<style>
.cff-post-card input[type="checkbox"]:checked ~ * {
    opacity: 1;
}
.cff-post-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<script>
var cffAjaxLocal = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('cff_nonce'); ?>'
};

jQuery(document).ready(function($) {
    var ajaxConfig = (typeof cffAjax !== 'undefined') ? cffAjax : cffAjaxLocal;
    
    // Actualizar estilo al cambiar checkbox
    $('.cff-favorite-checkbox').on('change', function() {
        var card = $(this).closest('.cff-post-card');
        if ($(this).is(':checked')) {
            card.css({
                'border-color': '#2271b1',
                'background': '#f0f6fc'
            });
        } else {
            card.css({
                'border-color': '#ddd',
                'background': 'white'
            });
        }
    });
    
    // Marcar todos
    $('#select-all').on('click', function() {
        $('.cff-favorite-checkbox').prop('checked', true).trigger('change');
    });
    
    // Desmarcar todos
    $('#deselect-all').on('click', function() {
        $('.cff-favorite-checkbox').prop('checked', false).trigger('change');
    });
    
    // Guardar favoritos
    $('#save-favorites, #save-favorites-bottom').on('click', function() {
        var categoryId = $(this).data('category-id');
        var selectedPosts = [];
        
        $('.cff-favorite-checkbox:checked').each(function() {
            selectedPosts.push($(this).data('post-id'));
        });
        
        $(this).prop('disabled', true).text('<?php _e('Guardant...', 'catalegfiresferies'); ?>');
        
        $.post(ajaxConfig.ajax_url, {
            action: 'cff_save_favorites',
            nonce: ajaxConfig.nonce,
            category_id: categoryId,
            post_ids: selectedPosts
        }, function(response) {
            if (response.success) {
                $('#save-message').fadeIn().delay(2000).fadeOut();
                $('#save-favorites, #save-favorites-bottom')
                    .prop('disabled', false)
                    .text('<?php _e('Guardar Favorits', 'catalegfiresferies'); ?>');
            } else {
                alert('Error: ' + response.data);
                $('#save-favorites, #save-favorites-bottom')
                    .prop('disabled', false)
                    .text('<?php _e('Guardar Favorits', 'catalegfiresferies'); ?>');
            }
        });
    });
});
</script>
