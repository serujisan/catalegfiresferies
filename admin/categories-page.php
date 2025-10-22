<?php
/**
 * Página para ver todas las categorías del catálogo
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener todas las categorías estándar de WordPress
$categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
));

?>
<div class="wrap">
    <h1><?php _e('Categories del Catàleg', 'catalegfiresferies'); ?></h1>
    
    <?php if (is_wp_error($categories)): ?>
        <div class="notice notice-error">
            <p><?php _e('Error carregant les categories.', 'catalegfiresferies'); ?></p>
        </div>
    <?php elseif (empty($categories)): ?>
        <div class="notice notice-warning">
            <p><?php _e('No hi ha categories creades. Importa el fitxer RTF i crea les categories des de la pàgina de configuració.', 'catalegfiresferies'); ?></p>
        </div>
    <?php else: ?>
        
        <div class="cff-categories-list" style="margin-top: 20px;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('ID', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Nom', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Slug', 'catalegfiresferies'); ?></th>
                        <th><?php _e('Categoria Pare', 'catalegfiresferies'); ?></th>
                        <th style="width: 100px; text-align: center;"><?php _e('Posts', 'catalegfiresferies'); ?></th>
                        <th style="width: 150px;"><?php _e('Accions', 'catalegfiresferies'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): 
                        $parent_name = '';
                        if ($category->parent != 0) {
                            $parent = get_term($category->parent, 'category');
                            if ($parent && !is_wp_error($parent)) {
                                $parent_name = $parent->name;
                            }
                        }
                        
                        $edit_link = admin_url('term.php?taxonomy=category&tag_ID=' . $category->term_id . '&post_type=post');
                        $view_link = get_term_link($category);
                    ?>
                    <tr>
                        <td><?php echo esc_html($category->term_id); ?></td>
                        <td>
                            <strong>
                                <?php if ($category->parent != 0): ?>
                                    <span style="color: #999;">— </span>
                                <?php endif; ?>
                                <?php echo esc_html($category->name); ?>
                            </strong>
                        </td>
                        <td><code><?php echo esc_html($category->slug); ?></code></td>
                        <td>
                            <?php if ($parent_name): ?>
                                <span class="dashicons dashicons-arrow-up-alt" style="color: #2271b1;"></span>
                                <?php echo esc_html($parent_name); ?>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge" style="background: #2271b1; color: white; padding: 2px 8px; border-radius: 3px;">
                                <?php echo esc_html($category->count); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($edit_link); ?>" class="button button-small">
                                <?php _e('Editar', 'catalegfiresferies'); ?>
                            </a>
                            <?php if (!is_wp_error($view_link)): ?>
                                <a href="<?php echo esc_url($view_link); ?>" class="button button-small" target="_blank">
                                    <?php _e('Veure', 'catalegfiresferies'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                <h3 style="margin-top: 0;"><?php _e('Resum', 'catalegfiresferies'); ?></h3>
                <?php 
                $total_categories = count($categories);
                $parent_categories = count(array_filter($categories, function($cat) { return $cat->parent == 0; }));
                $child_categories = $total_categories - $parent_categories;
                $total_posts = array_sum(array_map(function($cat) { return $cat->count; }, $categories));
                ?>
                <p>
                    <strong><?php _e('Total categories:', 'catalegfiresferies'); ?></strong> <?php echo esc_html($total_categories); ?><br>
                    <strong><?php _e('Categories pare:', 'catalegfiresferies'); ?></strong> <?php echo esc_html($parent_categories); ?><br>
                    <strong><?php _e('Categories filles:', 'catalegfiresferies'); ?></strong> <?php echo esc_html($child_categories); ?><br>
                    <strong><?php _e('Total posts:', 'catalegfiresferies'); ?></strong> <?php echo esc_html($total_posts); ?>
                </p>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<style>
.cff-categories-list .wp-list-table th,
.cff-categories-list .wp-list-table td {
    padding: 12px;
}
.cff-categories-list .badge {
    font-weight: bold;
    font-size: 12px;
}
</style>
