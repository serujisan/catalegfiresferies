<?php
/**
 * Página de administración del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

$imported_content = get_option('cff_imported_content', '');
?>

<div class="wrap">
    <h1><?php _e('Catàleg Fires i Fèries', 'catalegfiresferies'); ?></h1>
    
    <div class="cff-admin-container">
        
        <!-- Sección de importación -->
        <div class="cff-admin-section">
            <h2><?php _e('Importar fitxer RTF', 'catalegfiresferies'); ?></h2>
            <p><?php _e('Puja el fitxer catalogo.rtf per importar el contingut.', 'catalegfiresferies'); ?></p>
            
            <form id="cff-import-form" method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="rtf_file"><?php _e('Fitxer RTF:', 'catalegfiresferies'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="rtf_file" id="rtf_file" accept=".rtf" required>
                            <p class="description"><?php _e('Selecciona un fitxer .rtf per importar', 'catalegfiresferies'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="cff-import-btn">
                        <?php _e('Importar fitxer', 'catalegfiresferies'); ?>
                    </button>
                    <span class="spinner" style="float: none; margin: 0 10px;"></span>
                </p>
            </form>
            
            <div id="cff-import-result" style="margin-top: 20px;"></div>
        </div>
        
        <!-- Categorías extraidas -->
        <?php 
        $categories_data = get_option('cff_categories_data', array());
        if (!empty($categories_data)): 
        ?>
        <div class="cff-admin-section">
            <h2><?php _e('Categories extretes del fitxer', 'catalegfiresferies'); ?></h2>
            <p><?php echo sprintf(__('S\'han trobat <strong>%d categories</strong> al fitxer RTF.', 'catalegfiresferies'), count($categories_data)); ?></p>
            
            <div style="margin: 20px 0;">
                <button type="button" class="button button-primary" id="cff-create-categories-btn">
                    <?php _e('Crear Categories a WordPress', 'catalegfiresferies'); ?>
                </button>
                <button type="button" class="button button-secondary" id="cff-import-posts-btn">
                    <?php _e('Importar Posts com a Esborranys', 'catalegfiresferies'); ?>
                </button>
                <span class="spinner" id="cff-action-spinner" style="float: none; margin: 0 10px;"></span>
            </div>
            
            <div id="cff-action-result" style="margin-top: 15px;"></div>
            
            <details style="margin-top: 20px;">
                <summary style="cursor: pointer; font-weight: 600;"><?php _e('Veure llista de categories', 'catalegfiresferies'); ?></summary>
                <div style="max-height: 400px; overflow-y: auto; margin-top: 10px; border: 1px solid #ddd; padding: 15px;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Categoria', 'catalegfiresferies'); ?></th>
                                <th><?php _e('Proveïdors', 'catalegfiresferies'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories_data as $cat): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($cat['titulo']); ?></strong><br>
                                        <small><?php echo esc_html($cat['data_valor']); ?></small>
                                    </td>
                                    <td><?php echo count($cat['proveedores']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
        <?php endif; ?>
        
        <!-- Instrucciones de uso -->
        <div class="cff-admin-section">
            <h2><?php _e('Com utilitzar el plugin', 'catalegfiresferies'); ?></h2>
            
            <h3><?php _e('1. Configurar categories', 'catalegfiresferies'); ?></h3>
            <p><?php _e('Crea categories pare i subcategories a Entrades → Categories. Les categories pare agruparan les subcategories.', 'catalegfiresferies'); ?></p>
            
            <h3><?php _e('2. Assignar posts a categories', 'catalegfiresferies'); ?></h3>
            <p><?php _e('Edita els posts existents i assigna\'ls a les subcategories creades. Pots marcar-los com a favorits des del metabox "Configuració Catàleg".', 'catalegfiresferies'); ?></p>
            
            <h3><?php _e('3. Mostrar el catàleg', 'catalegfiresferies'); ?></h3>
            <p><?php _e('Utilitza el shortcode <code>[cataleg_festes]</code> a qualsevol pàgina per mostrar el catàleg complet.', 'catalegfiresferies'); ?></p>
            
            <h4><?php _e('Paràmetres del shortcode:', 'catalegfiresferies'); ?></h4>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><code>categoria</code> - <?php _e('Slug de la categoria pare per mostrar només una secció', 'catalegfiresferies'); ?></li>
                <li><code>mostrar_favoritos</code> - <?php _e('Mostrar només favorits (si/no)', 'catalegfiresferies'); ?></li>
                <li><code>posts_por_pagina</code> - <?php _e('Nombre de posts per pàgina (-1 per tots)', 'catalegfiresferies'); ?></li>
            </ul>
            
            <h4><?php _e('Exemples:', 'catalegfiresferies'); ?></h4>
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;">
[cataleg_festes]

[cataleg_festes categoria="fires"]

[cataleg_festes mostrar_favoritos="no" posts_por_pagina="10"]
            </pre>
        </div>
        
    </div>
</div>

<style>
.cff-admin-container {
    max-width: 1200px;
}

.cff-admin-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.cff-admin-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.cff-admin-section h3 {
    margin-top: 20px;
    color: #0073aa;
}

.cff-imported-content {
    margin-top: 15px;
}

#cff-import-result {
    padding: 10px;
    border-radius: 4px;
}

#cff-import-result.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

#cff-import-result.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

#cff-action-result {
    padding: 10px;
    border-radius: 4px;
}

#cff-action-result.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

#cff-action-result.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style>
