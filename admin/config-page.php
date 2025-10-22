<?php
/**
 * Página de configuración del catálogo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener todas las configuraciones guardadas
$all_configs = get_option('cff_catalog_configs', array());
$current_config_id = isset($_GET['config']) ? sanitize_key($_GET['config']) : '';

// Si se está editando una configuración existente
$current_config = array();
if (!empty($current_config_id) && isset($all_configs[$current_config_id])) {
    $current_config = $all_configs[$current_config_id];
}

$parent_category = isset($current_config['parent_category']) ? $current_config['parent_category'] : 0;
$selected_posts = isset($current_config['selected_posts']) ? $current_config['selected_posts'] : array();
$category_order = isset($current_config['category_order']) ? $current_config['category_order'] : array();
$config_name = isset($current_config['name']) ? $current_config['name'] : '';

// Obtener todas las categorías padre
$parent_categories = get_categories(array(
    'parent' => 0,
    'hide_empty' => false
));
?>

<div class="wrap">
    <h1><?php _e('Configurar Catàlegs', 'catalegfiresferies'); ?></h1>
    
    <!-- Lista de configuraciones existentes -->
    <?php if (!empty($all_configs)): ?>
        <div class="cff-config-section">
            <h2><?php _e('Configuracions Existents', 'catalegfiresferies'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Nom', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Shortcode', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Categoria Pare', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Accions', 'catalegfiresferies'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_configs as $config_id => $config): ?>
                        <?php
                        $parent_cat = get_category($config['parent_category']);
                        $is_current = ($config_id === $current_config_id);
                        ?>
                        <tr<?php if ($is_current) echo ' style="background: #e8f5e9;"'; ?>>
                            <td><strong><?php echo esc_html($config['name']); ?></strong></td>
                            <td><code>[cataleg_custom id="<?php echo esc_attr($config_id); ?>"]</code></td>
                            <td><?php echo $parent_cat ? esc_html($parent_cat->name) : '-'; ?></td>
                            <td>
                                <a href="<?php echo add_query_arg('config', $config_id); ?>" class="button button-small">
                                    <?php _e('Editar', 'catalegfiresferies'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=cff_delete_config&config=' . $config_id), 'cff_delete_config'); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('<?php _e('Segur que vols eliminar aquesta configuració?', 'catalegfiresferies'); ?>');">
                                    <?php _e('Eliminar', 'catalegfiresferies'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <a href="<?php echo remove_query_arg('config'); ?>" class="button button-secondary">
                    <?php _e('+ Nova Configuració', 'catalegfiresferies'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="cff-config-form">
        <input type="hidden" name="action" value="cff_save_catalog_config">
        <input type="hidden" name="config_id" value="<?php echo esc_attr($current_config_id); ?>">
        <?php wp_nonce_field('cff_save_catalog_config', 'cff_config_nonce'); ?>
        
        <div class="cff-config-container">
            
            <!-- Nombre de la configuración -->
            <div class="cff-config-section">
                <h2><?php _e('1. Nom de la Configuració', 'catalegfiresferies'); ?></h2>
                <input type="text" name="config_name" id="cff-config-name" class="widefat" 
                       value="<?php echo esc_attr($config_name); ?>" 
                       placeholder="<?php _e('Ex: Catàleg Proveïdors Musicals', 'catalegfiresferies'); ?>" 
                       required>
            </div>
            
            <!-- Selección de categoría padre -->
            <div class="cff-config-section">
                <h2><?php _e('2. Selecciona la Categoria Pare', 'catalegfiresferies'); ?></h2>
                <p class="description"><?php _e('Totes les categories filles d\'aquesta es mostraran al catàleg.', 'catalegfiresferies'); ?></p>
                
                <select name="parent_category" id="cff-parent-category" class="widefat">
                    <option value="0"><?php _e('-- Selecciona una categoria --', 'catalegfiresferies'); ?></option>
                    <?php foreach ($parent_categories as $cat): ?>
                        <option value="<?php echo $cat->term_id; ?>" <?php selected($parent_category, $cat->term_id); ?>>
                            <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?> posts)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <p class="submit">
                    <button type="button" id="cff-load-categories" class="button button-primary">
                        <?php _e('Carregar Categories Filles', 'catalegfiresferies'); ?>
                    </button>
                </p>
            </div>
            
            <!-- Configuración de categorías hijas -->
            <div id="cff-child-categories" class="cff-config-section" style="display: none;">
                <h2><?php _e('3. Ordena les Categories i Selecciona Posts', 'catalegfiresferies'); ?></h2>
                <p class="description"><?php _e('Arrossega les categories per ordenar-les i selecciona els posts per cada una.', 'catalegfiresferies'); ?></p>
                
                <div id="cff-categories-list" class="cff-sortable-categories"></div>
                <input type="hidden" name="category_order" id="cff-category-order" value="">
            </div>
            
            <!-- Botón guardar -->
            <div class="cff-config-section" id="cff-save-section" style="display: none;">
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Guardar Configuració', 'catalegfiresferies'); ?>
                    </button>
                </p>
            </div>
            
        </div>
    </form>
    
    <!-- Preview -->
    <div class="cff-config-section" id="cff-preview-section" style="display: none;">
        <h2><?php _e('3. Vista Prèvia', 'catalegfiresferies'); ?></h2>
        <p class="description"><?php _e('Shortcode:', 'catalegfiresferies'); ?> <code>[cataleg_custom]</code></p>
        <div id="cff-preview-content"></div>
    </div>
</div>

<style>
.cff-config-container {
    max-width: 1200px;
}

.cff-config-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.cff-config-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.cff-sortable-categories {
    min-height: 100px;
}

.cff-category-item {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    margin: 15px 0;
    cursor: move;
    transition: box-shadow 0.3s ease;
}

.cff-category-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.cff-category-item.sortable-ghost {
    opacity: 0.4;
    background: #e3f2fd;
}

.cff-category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: move;
    padding: 10px;
    background: #0073aa;
    color: #fff;
    border-radius: 4px;
    margin-bottom: 15px;
}

.cff-category-header::before {
    content: "⋮⋮";
    font-size: 20px;
    margin-right: 10px;
    opacity: 0.7;
}

.cff-category-header h3 {
    margin: 0;
    color: #fff;
}

.cff-posts-selector {
    padding: 15px;
    background: #fff;
    border-radius: 4px;
}

.cff-post-item {
    display: flex;
    align-items: center;
    padding: 10px;
    margin: 8px 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: move;
}

.cff-post-item:hover {
    background: #f0f0f1;
}

.cff-post-item input[type="checkbox"] {
    margin-right: 10px;
}

.cff-post-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 10px;
}

.cff-post-title {
    flex: 1;
}

.cff-drag-handle {
    cursor: move;
    color: #999;
    margin-right: 10px;
}

.cff-selected-posts {
    margin-top: 15px;
    padding: 15px;
    background: #e8f5e9;
    border: 2px dashed #4caf50;
    border-radius: 4px;
    min-height: 100px;
}

.cff-selected-posts h4 {
    margin-top: 0;
    color: #2e7d32;
}

.cff-selected-posts.empty {
    background: #fafafa;
    border-color: #ddd;
}

.sortable-ghost {
    opacity: 0.4;
}

.sortable-chosen {
    background: #e3f2fd;
}
</style>

<script>
jQuery(document).ready(function($) {
    var selectedPosts = <?php echo json_encode($selected_posts); ?>;
    
    // Cargar categorías hijas
    $('#cff-load-categories').on('click', function() {
        var parentId = $('#cff-parent-category').val();
        
        if (!parentId || parentId == '0') {
            alert('<?php _e('Si us plau, selecciona una categoria pare', 'catalegfiresferies'); ?>');
            return;
        }
        
        $(this).prop('disabled', true).text('<?php _e('Carregant...', 'catalegfiresferies'); ?>');
        
        $.post(cffAjax.ajax_url, {
            action: 'cff_load_child_categories',
            parent_id: parentId,
            nonce: '<?php echo wp_create_nonce('cff_load_categories'); ?>'
        }, function(response) {
            if (response.success) {
                $('#cff-categories-list').html(response.data.html);
                $('#cff-child-categories').show();
                $('#cff-save-section').show();
                $('#cff-preview-section').show();
                
                // Inicializar sortable
                initSortable();
            }
            $('#cff-load-categories').prop('disabled', false).text('<?php _e('Carregar Categories Filles', 'catalegfiresferies'); ?>');
        });
    });
    
    function initSortable() {
        // Sortable para categorías
        $('#cff-categories-list').sortable({
            handle: '.cff-category-header',
            placeholder: 'sortable-placeholder',
            update: function() {
                updateCategoryOrder();
            }
        });
        
        // Sortable para posts dentro de cada categoría
        $('.cff-selected-posts').sortable({
            handle: '.cff-drag-handle',
            placeholder: 'sortable-placeholder'
        });
    }
    
    function updateCategoryOrder() {
        var order = [];
        $('#cff-categories-list .cff-category-item').each(function() {
            order.push($(this).data('category-id'));
        });
        $('#cff-category-order').val(order.join(','));
    }
    
    // Antes de enviar el formulario
    $('#cff-config-form').on('submit', function() {
        updateCategoryOrder();
    });
});
</script>
