<?php
/**
 * Página para gestionar categorías padre del plugin
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_parent = $wpdb->prefix . 'cff_parent_categories';
$table_relations = $wpdb->prefix . 'cff_category_relations';

// Obtener todas las categorías padre
$parent_categories = $wpdb->get_results("SELECT * FROM $table_parent ORDER BY order_num ASC, name ASC");

// Obtener todas las categorías de WordPress
$wp_categories = get_categories(array(
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));

?>
<div class="wrap">
    <h1><?php _e('Categories Pare del Catàleg', 'catalegfiresferies'); ?></h1>
    <p><?php _e('Les categories pare són grups personalitzats que poden contenir múltiples categories de WordPress.', 'catalegfiresferies'); ?></p>
    
    <!-- Formulario para crear nueva categoría padre -->
    <div class="cff-card" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2><?php _e('Afegir Nova Categoria Pare', 'catalegfiresferies'); ?></h2>
        <form id="cff-new-parent-form">
            <table class="form-table">
                <tr>
                    <th><label for="parent_name"><?php _e('Nom', 'catalegfiresferies'); ?></label></th>
                    <td><input type="text" id="parent_name" name="name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="parent_slug"><?php _e('Slug', 'catalegfiresferies'); ?></label></th>
                    <td>
                        <input type="text" id="parent_slug" name="slug" class="regular-text" required>
                        <p class="description"><?php _e('URL amigable (només lletres, números i guions). S\'utilitzarà per crear el shortcode.', 'catalegfiresferies'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="parent_description"><?php _e('Descripció', 'catalegfiresferies'); ?></label></th>
                    <td><textarea id="parent_description" name="description" rows="3" class="large-text"></textarea></td>
                </tr>
            </table>
            <p>
                <button type="submit" class="button button-primary"><?php _e('Crear Categoria Pare', 'catalegfiresferies'); ?></button>
            </p>
        </form>
    </div>
    
    <!-- Lista de categorías padre existentes -->
    <div class="cff-parent-list">
        <h2><?php _e('Categories Pare Existents', 'catalegfiresferies'); ?></h2>
        
        <?php if (empty($parent_categories)): ?>
            <div class="notice notice-info">
                <p><?php _e('No hi ha categories pare creades. Crea la primera categoria pare utilitzant el formulari de dalt.', 'catalegfiresferies'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('ID', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Nom', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Slug', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Categories WP', 'catalegfiresferies'); ?></th>
                        <th style="width: 200px;"><?php _e('Shortcode', 'catalegfiresferies'); ?></th>
                        <th style="width: 200px;"><?php _e('Accions', 'catalegfiresferies'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parent_categories as $parent): 
                        // Obtener categorías WP asignadas
                        $assigned_cats = $wpdb->get_results($wpdb->prepare(
                            "SELECT wp_category_id FROM $table_relations WHERE parent_id = %d ORDER BY order_num ASC",
                            $parent->id
                        ));
                        $assigned_count = count($assigned_cats);
                    ?>
                    <tr>
                        <td><?php echo esc_html($parent->id); ?></td>
                        <td><strong><?php echo esc_html($parent->name); ?></strong></td>
                        <td><code><?php echo esc_html($parent->slug); ?></code></td>
                        <td>
                            <span class="badge" style="background: #2271b1; color: white; padding: 2px 8px; border-radius: 3px; font-weight: bold;">
                                <?php echo esc_html($assigned_count); ?>
                            </span>
                        </td>
                        <td>
                            <code style="background: #f0f0f1; padding: 5px 10px; border-radius: 3px; font-size: 11px;">
                                [cataleg_parent slug="<?php echo esc_attr($parent->slug); ?>"]
                            </code>
                        </td>
                        <td>
                            <a href="?page=catalegfiresferies-parent-categories&action=edit&id=<?php echo $parent->id; ?>" class="button button-small">
                                <?php _e('Gestionar', 'catalegfiresferies'); ?>
                            </a>
                            <button class="button button-small button-link-delete cff-delete-parent" data-id="<?php echo $parent->id; ?>" data-name="<?php echo esc_attr($parent->name); ?>">
                                <?php _e('Eliminar', 'catalegfiresferies'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Si hay una categoría seleccionada para editar -->
    <?php 
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_id = intval($_GET['id']);
        $edit_parent = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_parent WHERE id = %d", $edit_id));
        
        if ($edit_parent) {
            // Obtener relaciones actuales
            $current_relations = $wpdb->get_results($wpdb->prepare(
                "SELECT wp_category_id, order_num FROM $table_relations WHERE parent_id = %d ORDER BY order_num ASC",
                $edit_id
            ));
            $assigned_ids = array_column($current_relations, 'wp_category_id');
            ?>
            <div class="cff-card" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc; border-left: 4px solid #2271b1;">
                <h2><?php printf(__('Gestionar: %s', 'catalegfiresferies'), esc_html($edit_parent->name)); ?></h2>
                
                <h3><?php _e('Assignar Categories de WordPress', 'catalegfiresferies'); ?></h3>
                <p><?php _e('Selecciona les categories de WordPress que vols vincular a aquesta categoria pare:', 'catalegfiresferies'); ?></p>
                
                <form id="cff-assign-categories-form" data-parent-id="<?php echo $edit_id; ?>">
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                        <?php foreach ($wp_categories as $wp_cat): ?>
                            <label style="display: block; padding: 5px 0;">
                                <input type="checkbox" name="wp_categories[]" value="<?php echo $wp_cat->term_id; ?>" 
                                    <?php checked(in_array($wp_cat->term_id, $assigned_ids)); ?>>
                                <?php echo esc_html($wp_cat->name); ?> 
                                <span style="color: #999;">(<?php echo $wp_cat->count; ?> posts)</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p style="margin-top: 15px;">
                        <button type="submit" class="button button-primary"><?php _e('Guardar Categories', 'catalegfiresferies'); ?></button>
                        <a href="?page=catalegfiresferies-parent-categories" class="button"><?php _e('Cancel·lar', 'catalegfiresferies'); ?></a>
                    </p>
                </form>
                
                <?php if (!empty($assigned_ids)): ?>
                    <hr style="margin: 30px 0;">
                    <h3><?php _e('Ordenar Categories i Posts Favorits', 'catalegfiresferies'); ?></h3>
                    <p><?php _e('Pots ordenar les categories assignades i els posts favorits de cada categoria arrossegant-los:', 'catalegfiresferies'); ?></p>
                    
                    <div id="cff-sortable-categories" class="cff-sortable-container">
                        <?php foreach ($current_relations as $relation): 
                            $cat = get_category($relation->wp_category_id);
                            if (!$cat || is_wp_error($cat)) continue;
                            
                            // Obtener posts favoritos de esta categoría PARA ESTA CATEGORIA PADRE
                            $favorites_table = $wpdb->prefix . 'cff_favorites';
                            $favorite_posts = $wpdb->get_results($wpdb->prepare(
                                "SELECT post_id, order_num FROM $favorites_table 
                                 WHERE parent_id = %d AND wp_category_id = %d AND is_favorite = 1 
                                 ORDER BY order_num ASC",
                                $edit_id,
                                $cat->term_id
                            ));
                            
                            // Obtener TODOS los posts de la categoría para el selector
                            $all_posts = get_posts(array(
                                'category' => $cat->term_id,
                                'numberposts' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC',
                                'post_status' => 'publish'
                            ));
                            
                            $favorite_post_ids = array_column($favorite_posts, 'post_id');
                        ?>
                            <div class="cff-category-block" data-category-id="<?php echo $cat->term_id; ?>" style="background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0;">
                                <h4 class="cff-drag-handle" style="cursor: move; margin: 0 0 10px 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">
                                    <span class="dashicons dashicons-menu"></span>
                                    <?php echo esc_html($cat->name); ?>
                                    <span style="color: #999; font-weight: normal; font-size: 14px;">(<?php echo $cat->count; ?> posts)</span>
                                </h4>
                                
                                <div class="cff-favorite-posts">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <strong><?php _e('Posts Favorits:', 'catalegfiresferies'); ?></strong>
                                        <button class="button button-small cff-toggle-favorites" 
                                                data-category-id="<?php echo $cat->term_id; ?>"
                                                data-parent-id="<?php echo $edit_id; ?>">
                                            <?php _e('Seleccionar Favorits', 'catalegfiresferies'); ?>
                                        </button>
                                    </div>
                                    
                                    <!-- Selector de favoritos (oculto por defecto) -->
                                    <div class="cff-favorites-selector" id="selector-<?php echo $cat->term_id; ?>" style="display: none; background: #f9f9f9; padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 3px;">
                                        <p><strong><?php _e('Marca els posts que vols mostrar com a favorits:', 'catalegfiresferies'); ?></strong></p>
                                        <div style="max-height: 300px; overflow-y: auto; margin-bottom: 10px;">
                                            <?php foreach ($all_posts as $post): ?>
                                                <label style="display: block; padding: 5px 0;">
                                                    <input type="checkbox" 
                                                           class="cff-post-checkbox" 
                                                           data-post-id="<?php echo $post->ID; ?>"
                                                           <?php checked(in_array($post->ID, $favorite_post_ids)); ?>>
                                                    <?php echo esc_html($post->post_title); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="button button-primary cff-save-favorites" 
                                                data-category-id="<?php echo $cat->term_id; ?>"
                                                data-parent-id="<?php echo $edit_id; ?>">
                                            <?php _e('Guardar Favorits', 'catalegfiresferies'); ?>
                                        </button>
                                        <button class="button cff-cancel-favorites" data-category-id="<?php echo $cat->term_id; ?>">
                                            <?php _e('Cancel·lar', 'catalegfiresferies'); ?>
                                        </button>
                                    </div>
                                    
                                    <!-- Lista de favoritos seleccionados (ordenable) -->
                                    <?php if (empty($favorite_posts)): ?>
                                        <p style="color: #999;" class="cff-no-favorites-msg"><?php _e('Sense posts favorits. Fes clic a "Seleccionar Favorits" per afegir-ne.', 'catalegfiresferies'); ?></p>
                                    <?php else: ?>
                                        <ul class="cff-sortable-posts" data-category-id="<?php echo $cat->term_id; ?>" style="list-style: none; padding: 0; margin: 10px 0 0 0;">
                                            <?php foreach ($favorite_posts as $fav_post): 
                                                $post = get_post($fav_post->post_id);
                                                if (!$post) continue;
                                            ?>
                                                <li class="cff-post-item" data-post-id="<?php echo $post->ID; ?>" style="background: #f9f9f9; padding: 10px; margin: 5px 0; border-left: 3px solid #2271b1; cursor: move;">
                                                    <span class="dashicons dashicons-menu"></span>
                                                    <?php echo esc_html($post->post_title); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p style="margin-top: 15px;">
                        <button id="cff-save-order" class="button button-primary" data-parent-id="<?php echo $edit_id; ?>">
                            <?php _e('Guardar Ordre', 'catalegfiresferies'); ?>
                        </button>
                    </p>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    ?>
</div>

<style>
.cff-sortable-container {
    margin: 15px 0;
}
.cff-drag-handle {
    cursor: move;
    user-select: none;
}
.cff-sortable-posts {
    min-height: 50px;
}
.ui-sortable-helper {
    opacity: 0.8;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
</style>

<script>
var cffAjaxLocal = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('cff_nonce'); ?>'
};

jQuery(document).ready(function($) {
    // Usar cffAjaxLocal como fallback si cffAjax no está disponible
    var ajaxConfig = (typeof cffAjax !== 'undefined') ? cffAjax : cffAjaxLocal;
    
    // Auto-generar slug desde nombre
    $('#parent_name').on('input', function() {
        var name = $(this).val();
        var slug = name.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        $('#parent_slug').val(slug);
    });
    
    // Crear nueva categoría padre
    $('#cff-new-parent-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'cff_save_parent_category',
            nonce: ajaxConfig.nonce,
            name: $('#parent_name').val(),
            slug: $('#parent_slug').val(),
            description: $('#parent_description').val()
        };
        
        $.post(ajaxConfig.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Categoria pare creada correctament!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Eliminar categoría padre
    $('.cff-delete-parent').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        if (!confirm('Segur que vols eliminar la categoria "' + name + '"? Això no eliminarà les categories de WordPress associades.')) {
            return;
        }
        
        $.post(ajaxConfig.ajax_url, {
            action: 'cff_delete_parent_category',
            nonce: ajaxConfig.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                alert('Categoria eliminada correctament!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Guardar categorías asignadas
    $('#cff-assign-categories-form').on('submit', function(e) {
        e.preventDefault();
        
        var parentId = $(this).data('parent-id');
        var selectedCategories = [];
        $(this).find('input[name="wp_categories[]"]:checked').each(function() {
            selectedCategories.push($(this).val());
        });
        
        $.post(ajaxConfig.ajax_url, {
            action: 'cff_save_category_relations',
            nonce: ajaxConfig.nonce,
            parent_id: parentId,
            categories: selectedCategories
        }, function(response) {
            if (response.success) {
                alert('Categories guardades correctament!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Sortable para categorías
    $('#cff-sortable-categories').sortable({
        handle: '.cff-drag-handle',
        axis: 'y',
        opacity: 0.8
    });
    
    // Sortable para posts favoritos
    $('.cff-sortable-posts').sortable({
        axis: 'y',
        opacity: 0.8
    });
    
    // Toggle selector de favoritos
    $('.cff-toggle-favorites').on('click', function() {
        var catId = $(this).data('category-id');
        $('#selector-' + catId).slideToggle();
    });
    
    // Cancelar selección de favoritos
    $('.cff-cancel-favorites').on('click', function() {
        var catId = $(this).data('category-id');
        $('#selector-' + catId).slideUp();
    });
    
    // Guardar favoritos
    $('.cff-save-favorites').on('click', function() {
        var catId = $(this).data('category-id');
        var parentId = $(this).data('parent-id');
        var selectedPosts = [];
        
        $('#selector-' + catId + ' .cff-post-checkbox:checked').each(function() {
            selectedPosts.push($(this).data('post-id'));
        });
        
        $(this).prop('disabled', true).text('Guardant...');
        
        $.post(ajaxConfig.ajax_url, {
            action: 'cff_save_category_favorites',
            nonce: ajaxConfig.nonce,
            parent_id: parentId,
            category_id: catId,
            post_ids: selectedPosts
        }, function(response) {
            if (response.success) {
                alert('Favorits guardats! Recarrega la pàgina per veure els canvis.');
                location.reload();
            } else {
                alert('Error: ' + response.data);
                $('.cff-save-favorites').prop('disabled', false).text('Guardar Favorits');
            }
        });
    });
    
    // Guardar orden
    $('#cff-save-order').on('click', function() {
        var parentId = $(this).data('parent-id');
        var categoryOrder = [];
        var postsOrder = {};
        
        // Recoger orden de categorías
        $('#cff-sortable-categories .cff-category-block').each(function(index) {
            var catId = $(this).data('category-id');
            categoryOrder.push({
                wp_category_id: catId,
                order: index
            });
            
            // Recoger orden de posts de esta categoría
            var posts = [];
            $(this).find('.cff-sortable-posts .cff-post-item').each(function(postIndex) {
                posts.push({
                    post_id: $(this).data('post-id'),
                    order: postIndex
                });
            });
            postsOrder[catId] = posts;
        });
        
        $.post(ajaxConfig.ajax_url, {
            action: 'cff_save_favorites_order',
            nonce: ajaxConfig.nonce,
            parent_id: parentId,
            category_order: categoryOrder,
            posts_order: postsOrder
        }, function(response) {
            if (response.success) {
                alert('Ordre guardat correctament!');
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
</script>
