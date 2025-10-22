<?php
/**
 * Script para verificar y crear tablas del plugin
 * Accede a: wp-admin/admin.php?page=catalegfiresferies-setup-tables
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

// Manejar acciones
if (isset($_GET['action']) && isset($_GET['_wpnonce'])) {
    if ($_GET['action'] === 'recreate' && wp_verify_nonce($_GET['_wpnonce'], 'cff_recreate_tables')) {
        // Eliminar tablas existentes
        $tables = array(
            $wpdb->prefix . 'cff_favorites',
            $wpdb->prefix . 'cff_category_relations',
            $wpdb->prefix . 'cff_parent_categories'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        echo '<div class="notice notice-success"><p><strong>Taules eliminades correctament.</strong> Recarrega la pàgina per crear-les de nou.</p></div>';
    }
}

echo '<div class="wrap">';
echo '<h1>Configuració de Taules - Catàleg Fires i Fèries</h1>';
echo '<p>Aquesta pàgina et permet verificar i gestionar les taules de la base de dades del plugin.</p>';

// Tabla 1: Categorías padre
$table_parent = $wpdb->prefix . 'cff_parent_categories';
$exists_parent = $wpdb->get_var("SHOW TABLES LIKE '$table_parent'");

echo '<h2>Taula: ' . $table_parent . '</h2>';
if ($exists_parent) {
    echo '<p style="color: green;">✓ La taula existeix</p>';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_parent");
    echo '<p>Registres: ' . $count . '</p>';
} else {
    echo '<p style="color: red;">✗ La taula NO existeix</p>';
    
    $sql = "CREATE TABLE $table_parent (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(200) NOT NULL,
        slug varchar(200) NOT NULL,
        description text,
        order_num int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    $exists_after = $wpdb->get_var("SHOW TABLES LIKE '$table_parent'");
    if ($exists_after) {
        echo '<p style="color: green;">✓ Taula creada correctament</p>';
    } else {
        echo '<p style="color: red;">✗ Error creant la taula</p>';
        echo '<pre>' . $wpdb->last_error . '</pre>';
    }
}

// Tabla 2: Relaciones
$table_relations = $wpdb->prefix . 'cff_category_relations';
$exists_relations = $wpdb->get_var("SHOW TABLES LIKE '$table_relations'");

echo '<h2>Taula: ' . $table_relations . '</h2>';
if ($exists_relations) {
    echo '<p style="color: green;">✓ La taula existeix</p>';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_relations");
    echo '<p>Registres: ' . $count . '</p>';
} else {
    echo '<p style="color: red;">✗ La taula NO existeix</p>';
    
    $sql = "CREATE TABLE $table_relations (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        parent_id mediumint(9) NOT NULL,
        wp_category_id bigint(20) NOT NULL,
        order_num int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY parent_id (parent_id),
        KEY wp_category_id (wp_category_id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    $exists_after = $wpdb->get_var("SHOW TABLES LIKE '$table_relations'");
    if ($exists_after) {
        echo '<p style="color: green;">✓ Taula creada correctament</p>';
    } else {
        echo '<p style="color: red;">✗ Error creant la taula</p>';
        echo '<pre>' . $wpdb->last_error . '</pre>';
    }
}

// Tabla 3: Favoritos
$table_favorites = $wpdb->prefix . 'cff_favorites';
$exists_favorites = $wpdb->get_var("SHOW TABLES LIKE '$table_favorites'");

echo '<h2>Taula: ' . $table_favorites . '</h2>';
if ($exists_favorites) {
    echo '<p style="color: green;">✓ La taula existeix</p>';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_favorites");
    echo '<p>Registres: ' . $count . '</p>';
    
    // Verificar estructura
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_favorites");
    echo '<details><summary>Estructura de la taula</summary><pre>';
    print_r($columns);
    echo '</pre></details>';
} else {
    echo '<p style="color: red;">✗ La taula NO existeix</p>';
    
    $sql = "CREATE TABLE $table_favorites (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        parent_id mediumint(9) NOT NULL,
        post_id bigint(20) NOT NULL,
        wp_category_id bigint(20) NOT NULL,
        order_num int(11) DEFAULT 0,
        is_favorite tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY parent_id (parent_id),
        KEY post_id (post_id),
        KEY wp_category_id (wp_category_id),
        UNIQUE KEY parent_post_category (parent_id, post_id, wp_category_id)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    $exists_after = $wpdb->get_var("SHOW TABLES LIKE '$table_favorites'");
    if ($exists_after) {
        echo '<p style="color: green;">✓ Taula creada correctament</p>';
    } else {
        echo '<p style="color: red;">✗ Error creant la taula</p>';
        echo '<pre>' . $wpdb->last_error . '</pre>';
    }
}

echo '<hr>';
echo '<h2 style="color: #d63638;">Zona de Perill</h2>';
echo '<p>Si tens problemes amb l\'estructura de les taules o necessites actualitzar-les a la nova versió:</p>';
echo '<div style="background: #fff3cd; border-left: 4px solid #d63638; padding: 15px; margin: 20px 0;">';
echo '<p><strong style="color: #d63638;">⚠️ ATENCIÓ:</strong> Aquesta acció eliminarà <strong>TOTES</strong> les taules del plugin i les seves dades:</p>';
echo '<ul>';
echo '<li>Categories pare</li>';
echo '<li>Relacions de categories</li>';
echo '<li>Favorits i ordenacions</li>';
echo '</ul>';
echo '<p><strong>NO es poden recuperar les dades després d\'eliminar-les.</strong> Només utilitza aquesta opció si estàs segur.</p>';
$recreate_url = wp_nonce_url(
    admin_url('admin.php?page=catalegfiresferies-setup-tables&action=recreate'),
    'cff_recreate_tables'
);
echo '<p><a href="' . $recreate_url . '" class="button button-link-delete" onclick="return confirm(\'Segur que vols ELIMINAR TOTES les taules i dades del plugin? Aquesta acció NO es pot desfer.\');">Eliminar i Recrear Taules</a></p>';
echo '</div>';
echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=catalegfiresferies-parent-categories') . '" class="button button-primary">Tornar a Categories Pare</a></p>';
echo '</div>';
