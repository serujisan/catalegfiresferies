<?php
/**
 * Plugin Name: Catàleg Fires i Fèries
 * Plugin URI: https://festesmajorsdecatalunya.cat
 * Description: Plugin para gestionar catálogo de fires i fèries con categorías y favoritos
 * Version: 3.4.0
 * Author: Sergi Maneja
 * Author URI: https://festesmajorsdecatalunya.cat
 * License: GPL2
 * Text Domain: catalegfiresferies
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('CFF_VERSION', '3.4.0');
define('CFF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar librería de actualizaciones
require CFF_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Configurar auto-update desde GitHub
$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/serujisan/catalegfiresferies/',
    __FILE__,
    'catalegfiresferies'
);

// Opcional: especificar rama (main, master, develop, etc.)
$updateChecker->setBranch('main');

// Opcional: si el repo es privado, agregar token de acceso
// $updateChecker->setAuthentication('tu_github_token');

class CatalegFiresFeries {
    
    public function __construct() {
        // Hooks de activación/desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Cargar scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Menú de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Guardar y eliminar configuración del catálogo
        add_action('admin_post_cff_save_catalog_config', array($this, 'save_catalog_config'));
        add_action('admin_post_cff_delete_config', array($this, 'delete_catalog_config'));
        
        // Metabox para posts
        add_action('add_meta_boxes', array($this, 'add_metabox'));
        add_action('save_post', array($this, 'save_metabox_data'));
        
        // Registrar taxonomía personalizada
        add_action('init', array($this, 'register_taxonomy'));
        
        // Shortcode
        add_shortcode('cataleg_festes', array($this, 'cataleg_shortcode'));
        add_shortcode('cataleg_categoria', array($this, 'categoria_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_cff_import_rtf', array($this, 'ajax_import_rtf'));
        add_action('wp_ajax_cff_create_categories', array($this, 'ajax_create_categories'));
        add_action('wp_ajax_cff_import_posts', array($this, 'ajax_import_posts'));
        add_action('wp_ajax_cff_load_child_categories', array($this, 'ajax_load_child_categories'));
        add_action('wp_ajax_cff_save_parent_category', array($this, 'ajax_save_parent_category'));
        add_action('wp_ajax_cff_delete_parent_category', array($this, 'ajax_delete_parent_category'));
        add_action('wp_ajax_cff_save_category_relations', array($this, 'ajax_save_category_relations'));
        add_action('wp_ajax_cff_save_favorites_order', array($this, 'ajax_save_favorites_order'));
        add_action('wp_ajax_cff_save_favorites', array($this, 'ajax_save_favorites'));
        add_action('wp_ajax_cff_save_category_favorites', array($this, 'ajax_save_category_favorites'));
        
        // Shortcode personalizado
        add_shortcode('cataleg_custom', array($this, 'cataleg_custom_shortcode'));
        add_shortcode('cataleg_parent', array($this, 'cataleg_parent_shortcode'));
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Tabla de categorías padre del plugin
        $table_parent = $wpdb->prefix . 'cff_parent_categories';
        $sql_parent = "CREATE TABLE IF NOT EXISTS $table_parent (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            description text,
            order_num int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        dbDelta($sql_parent);
        
        // Tabla de relación entre categorías padre del plugin y categorías de WordPress
        $table_relations = $wpdb->prefix . 'cff_category_relations';
        $sql_relations = "CREATE TABLE IF NOT EXISTS $table_relations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            parent_id mediumint(9) NOT NULL,
            wp_category_id bigint(20) NOT NULL,
            order_num int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parent_id (parent_id),
            KEY wp_category_id (wp_category_id)
        ) $charset_collate;";
        dbDelta($sql_relations);
        
        // Tabla de favoritos y ordenación de posts (por categoría padre)
        $table_favorites = $wpdb->prefix . 'cff_favorites';
        $sql_favorites = "CREATE TABLE IF NOT EXISTS $table_favorites (
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
        dbDelta($sql_favorites);
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar tareas programadas si las hay
    }
    
    /**
     * Registrar taxonomía personalizada
     */
    public function register_taxonomy() {
        $labels = array(
            'name' => __('Categories del Catàleg', 'catalegfiresferies'),
            'singular_name' => __('Categoria del Catàleg', 'catalegfiresferies'),
            'search_items' => __('Cercar Categories', 'catalegfiresferies'),
            'all_items' => __('Totes les Categories', 'catalegfiresferies'),
            'parent_item' => __('Categoria Pare', 'catalegfiresferies'),
            'parent_item_colon' => __('Categoria Pare:', 'catalegfiresferies'),
            'edit_item' => __('Editar Categoria', 'catalegfiresferies'),
            'update_item' => __('Actualitzar Categoria', 'catalegfiresferies'),
            'add_new_item' => __('Afegir Nova Categoria', 'catalegfiresferies'),
            'new_item_name' => __('Nom Nova Categoria', 'catalegfiresferies'),
            'menu_name' => __('Categories Catàleg', 'catalegfiresferies'),
        );
        
        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'cataleg-categoria'),
            'show_in_rest' => true,
        );
        
        register_taxonomy('cataleg_categoria', array('post'), $args);
    }
    
    /**
     * Cargar assets del frontend
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style('cff-frontend', CFF_PLUGIN_URL . 'assets/css/frontend.css', array(), CFF_VERSION);
        wp_enqueue_script('cff-frontend', CFF_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CFF_VERSION, true);
    }
    
    /**
     * Cargar assets del admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'catalegfiresferies') === false && 'post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        // Cargar jQuery UI Sortable
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_style('cff-admin', CFF_PLUGIN_URL . 'assets/css/admin.css', array(), CFF_VERSION);
        wp_enqueue_script('cff-admin', CFF_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), CFF_VERSION, true);
        
        wp_localize_script('cff-admin', 'cffAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cff_nonce')
        ));
    }
    
    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Catàleg Fires i Fèries', 'catalegfiresferies'),
            __('Catàleg Fires', 'catalegfiresferies'),
            'manage_options',
            'catalegfiresferies',
            array($this, 'admin_page'),
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page(
            'catalegfiresferies',
            __('Configurar Catàleg', 'catalegfiresferies'),
            __('Configurar Catàleg', 'catalegfiresferies'),
            'manage_options',
            'catalegfiresferies-config',
            array($this, 'config_page')
        );
        
        add_submenu_page(
            'catalegfiresferies',
            __('Categories Pare', 'catalegfiresferies'),
            __('Categories Pare', 'catalegfiresferies'),
            'manage_options',
            'catalegfiresferies-parent-categories',
            array($this, 'parent_categories_page')
        );
        
        add_submenu_page(
            'catalegfiresferies',
            __('Gestionar Favorits', 'catalegfiresferies'),
            __('Gestionar Favorits', 'catalegfiresferies'),
            'manage_options',
            'catalegfiresferies-manage-favorites',
            array($this, 'manage_favorites_page')
        );
        
        add_submenu_page(
            'catalegfiresferies',
            __('Veure Categories WP', 'catalegfiresferies'),
            __('Veure Categories WP', 'catalegfiresferies'),
            'manage_options',
            'catalegfiresferies-categories',
            array($this, 'categories_page')
        );
        
        add_submenu_page(
            'catalegfiresferies',
            __('Configurar Taules', 'catalegfiresferies'),
            __('Configurar Taules', 'catalegfiresferies'),
            'manage_options',
            'catalegfiresferies-setup-tables',
            array($this, 'setup_tables_page')
        );
    }
    
    /**
     * Página de administración
     */
    public function admin_page() {
        include CFF_PLUGIN_DIR . 'admin/admin-page.php';
    }
    
    /**
     * Página de configuración del catálogo
     */
    public function config_page() {
        include CFF_PLUGIN_DIR . 'admin/config-page.php';
    }
    
    /**
     * Página para gestionar categorías padre
     */
    public function parent_categories_page() {
        include CFF_PLUGIN_DIR . 'admin/parent-categories-page.php';
    }
    
    /**
     * Página para ver categorías de WordPress
     */
    public function categories_page() {
        include CFF_PLUGIN_DIR . 'admin/categories-page.php';
    }
    
    /**
     * Página para gestionar favoritos
     */
    public function manage_favorites_page() {
        include CFF_PLUGIN_DIR . 'admin/manage-favorites.php';
    }
    
    /**
     * Página para configurar tablas
     */
    public function setup_tables_page() {
        include CFF_PLUGIN_DIR . 'admin/setup-tables.php';
    }
    
    /**
     * Añadir metabox a posts
     */
    public function add_metabox() {
        add_meta_box(
            'cff_metabox',
            __('Configuració Catàleg', 'catalegfiresferies'),
            array($this, 'render_metabox'),
            'post',
            'side',
            'high'
        );
    }
    
    /**
     * Renderizar metabox
     */
    public function render_metabox($post) {
        wp_nonce_field('cff_metabox_nonce', 'cff_metabox_nonce_field');
        
        $is_favorite = get_post_meta($post->ID, '_cff_is_favorite', true);
        $order = get_post_meta($post->ID, '_cff_order', true);
        
        ?>
        <div class="cff-metabox">
            <p>
                <label>
                    <input type="checkbox" name="cff_is_favorite" value="1" <?php checked($is_favorite, '1'); ?>>
                    <?php _e('Marcar com a favorit', 'catalegfiresferies'); ?>
                </label>
            </p>
            <p>
                <label><?php _e('Ordre de visualització:', 'catalegfiresferies'); ?></label>
                <input type="number" name="cff_order" value="<?php echo esc_attr($order); ?>" class="widefat" min="0">
            </p>
        </div>
        <?php
    }
    
    /**
     * Guardar datos del metabox
     */
    public function save_metabox_data($post_id) {
        // Verificar nonce
        if (!isset($_POST['cff_metabox_nonce_field']) || 
            !wp_verify_nonce($_POST['cff_metabox_nonce_field'], 'cff_metabox_nonce')) {
            return;
        }
        
        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Guardar favorito
        $is_favorite = isset($_POST['cff_is_favorite']) ? '1' : '0';
        update_post_meta($post_id, '_cff_is_favorite', $is_favorite);
        
        // Guardar orden
        if (isset($_POST['cff_order'])) {
            update_post_meta($post_id, '_cff_order', intval($_POST['cff_order']));
        }
    }
    
    /**
     * Shortcode para mostrar el catálogo (solo favoritos)
     */
    public function cataleg_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columnas' => 4,
            'max_favoritos' => 4,
        ), $atts);
        
        ob_start();
        
        // Obtener todas las categorías de la taxonomía personalizada
        $categories = get_terms(array(
            'taxonomy' => 'cataleg_categoria',
            'hide_empty' => false
        ));
        
        if (empty($categories) || is_wp_error($categories)) {
            return '<p>' . __('No hi ha categories configurades.', 'catalegfiresferies') . '</p>';
        }
        
        $cols = intval($atts['columnas']);
        $max = intval($atts['max_favoritos']);
        ?>
        <div class="cff-cataleg cff-cataleg-grid" style="--cff-cols: <?php echo $cols; ?>">
            <?php foreach ($categories as $categoria): ?>
                <?php
                // Query para obtener solo favoritos de esta categoría
                $args = array(
                    'post_type' => 'post',
                    'posts_per_page' => $max,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'cataleg_categoria',
                            'field' => 'term_id',
                            'terms' => $categoria->term_id
                        )
                    ),
                    'meta_query' => array(
                        array(
                            'key' => '_cff_is_favorite',
                            'value' => '1',
                            'compare' => '='
                        )
                    ),
                    'meta_key' => '_cff_order',
                    'orderby' => 'meta_value_num',
                    'order' => 'ASC'
                );
                
                $query = new WP_Query($args);
                
                if (!$query->have_posts()) {
                    wp_reset_postdata();
                    continue;
                }
                ?>
                
                <div class="cff-categoria-card" id="cat-<?php echo $categoria->term_id; ?>">
                    <h2 class="cff-categoria-titulo">
                        <a href="<?php echo get_term_link($categoria); ?>">
                            <?php echo esc_html($categoria->name); ?>
                        </a>
                    </h2>
                    
                    <?php if (!empty($categoria->description)): ?>
                        <div class="cff-categoria-descripcio">
                            <?php echo wpautop($categoria->description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="cff-posts-grid-mini">
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <article class="cff-post-item-mini cff-favorit">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="cff-post-thumbnail-mini">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('thumbnail'); ?>
                                        </a>
                                        <span class="cff-favorit-badge">⭐</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="cff-post-content-mini">
                                    <h4 class="cff-post-title-mini">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_title(); ?>
                                        </a>
                                    </h4>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="cff-categoria-footer">
                        <a href="<?php echo get_term_link($categoria); ?>" class="cff-veure-tots">
                            <?php _e('Veure tots', 'catalegfiresferies'); ?> →
                        </a>
                    </div>
                </div>
                
                <?php wp_reset_postdata(); ?>
            <?php endforeach; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode para mostrar todos los posts de una categoría
     */
    public function categoria_shortcode($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
            'id' => 0
        ), $atts);
        
        // Obtener categoría
        $categoria = null;
        if (!empty($atts['slug'])) {
            $categoria = get_term_by('slug', $atts['slug'], 'cataleg_categoria');
        } elseif (!empty($atts['id'])) {
            $categoria = get_term($atts['id'], 'cataleg_categoria');
        }
        
        if (!$categoria || is_wp_error($categoria)) {
            return '<p>' . __('Categoria no trobada.', 'catalegfiresferies') . '</p>';
        }
        
        ob_start();
        
        // Query para obtener TODOS los posts de esta categoría
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'cataleg_categoria',
                    'field' => 'term_id',
                    'terms' => $categoria->term_id
                )
            ),
            'meta_key' => '_cff_order',
            'orderby' => array(
                'meta_value_num' => 'ASC',
                'date' => 'DESC'
            )
        );
        
        $query = new WP_Query($args);
        
        ?>
        <div class="cff-categoria-completa">
            <header class="cff-categoria-header">
                <h1 class="cff-categoria-titulo-page"><?php echo esc_html($categoria->name); ?></h1>
                <?php if (!empty($categoria->description)): ?>
                    <div class="cff-categoria-descripcio">
                        <?php echo wpautop($categoria->description); ?>
                    </div>
                <?php endif; ?>
                <p class="cff-categoria-count">
                    <?php printf(__('%d proveïdors', 'catalegfiresferies'), $query->found_posts); ?>
                </p>
            </header>
            
            <?php if ($query->have_posts()): ?>
                <div class="cff-posts-grid">
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <?php
                        $is_favorite = get_post_meta(get_the_ID(), '_cff_is_favorite', true);
                        $favorite_class = $is_favorite ? 'cff-favorit' : '';
                        ?>
                        <article class="cff-post-item <?php echo $favorite_class; ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <div class="cff-post-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                    <?php if ($is_favorite): ?>
                                        <span class="cff-favorit-badge">⭐</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="cff-post-content">
                                <h4 class="cff-post-title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h4>
                                
                                <div class="cff-post-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                                
                                <a href="<?php the_permalink(); ?>" class="cff-post-link">
                                    <?php _e('Veure més', 'catalegfiresferies'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p><?php _e('No hi ha proveïdors en aquesta categoria.', 'catalegfiresferies'); ?></p>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * AJAX: Importar archivo RTF
     */
    public function ajax_import_rtf() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        if (!isset($_FILES['rtf_file'])) {
            wp_send_json_error(array('message' => 'No se ha subido ningún archivo'));
        }
        
        $file = $_FILES['rtf_file'];
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        // Procesar el archivo RTF
        $content = $this->parse_rtf_file($upload['file']);
        
        // Guardar el contenido procesado
        update_option('cff_imported_content', $content);
        
        wp_send_json_success(array(
            'message' => 'Archivo importado correctamente',
            'content' => $content
        ));
    }
    
    /**
     * Parsear archivo RTF y extraer estructura de categorías y proveedores
     */
    private function parse_rtf_file($file_path) {
        $content = file_get_contents($file_path);
        
        // Limpiar códigos RTF para extraer HTML
        $content = preg_replace('/\\\\[a-z]+[0-9-]* ?/', '', $content);
        $content = preg_replace('/[{}]/', '', $content);
        $content = preg_replace('/\\\\/', '', $content);
        $content = trim($content);
        
        // Extraer y procesar categorías
        $categories_data = $this->extract_categories_from_html($content);
        
        // Guardar información estructurada
        update_option('cff_categories_data', $categories_data);
        
        return $content;
    }
    
    /**
     * Extraer categorías y proveedores del HTML
     */
    private function extract_categories_from_html($html) {
        $categories = array();
        
        // Cargar HTML con DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Buscar todos los <article> con clase categoria-proveidor
        $articles = $xpath->query("//article[@class='categoria-proveidor']");
        
        foreach ($articles as $article) {
            $categoria_valor = $article->getAttribute('data-valor');
            
            if (empty($categoria_valor)) {
                continue;
            }
            
            // Buscar el título de la categoría (h2 o h3)
            $titulo_nodes = $xpath->query(".//h2/a | .//h3/a", $article);
            $titulo = '';
            $url_categoria = '';
            
            if ($titulo_nodes->length > 0) {
                $titulo = trim($titulo_nodes->item(0)->textContent);
                $url_categoria = $titulo_nodes->item(0)->getAttribute('href');
            }
            
            // Extraer proveedores (enlaces con imágenes)
            $proveedores = array();
            $enlaces = $xpath->query(".//p/a[img]", $article);
            
            foreach ($enlaces as $enlace) {
                $url = $enlace->getAttribute('href');
                $img = $xpath->query(".//img", $enlace)->item(0);
                
                if ($img) {
                    $proveedores[] = array(
                        'url' => $url,
                        'nombre' => $img->getAttribute('alt'),
                        'imagen' => $img->getAttribute('src'),
                        'title' => $img->getAttribute('title')
                    );
                }
            }
            
            $categories[] = array(
                'data_valor' => $categoria_valor,
                'titulo' => $titulo,
                'url' => $url_categoria,
                'proveedores' => $proveedores
            );
        }
        
        return $categories;
    }
    
    /**
     * AJAX: Crear categorías desde datos importados
     */
    public function ajax_create_categories() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        $categories_data = get_option('cff_categories_data', array());
        
        if (empty($categories_data)) {
            wp_send_json_error(array('message' => 'No hay datos de categorías importadas'));
        }
        
        $created = 0;
        
        // Crear categorías usando taxonomía personalizada
        foreach ($categories_data as $cat_data) {
            $slug = sanitize_title($cat_data['data_valor']);
            $existing = get_term_by('slug', $slug, 'cataleg_categoria');
            
            if (!$existing) {
                $result = wp_insert_term(
                    $cat_data['titulo'],
                    'cataleg_categoria',
                    array(
                        'slug' => $slug
                    )
                );
                
                if (!is_wp_error($result)) {
                    $created++;
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => "S'han creat $created categories correctament",
            'created' => $created
        ));
    }
    
    /**
     * AJAX: Importar posts desde datos del catálogo
     */
    public function ajax_import_posts() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        $categories_data = get_option('cff_categories_data', array());
        
        if (empty($categories_data)) {
            wp_send_json_error(array('message' => 'No hay datos de categorías importadas'));
        }
        
        $created = 0;
        
        foreach ($categories_data as $cat_data) {
            $cat_slug = sanitize_title($cat_data['data_valor']);
            $category = get_term_by('slug', $cat_slug, 'cataleg_categoria');
            
            if (!$category) {
                continue;
            }
            
            foreach ($cat_data['proveedores'] as $index => $proveedor) {
                // Extraer nombre del proveedor de la URL si no hay nombre
                $nombre = !empty($proveedor['nombre']) ? $proveedor['nombre'] : '';
                if (empty($nombre) && !empty($proveedor['title'])) {
                    $nombre = $proveedor['title'];
                }
                if (empty($nombre) && !empty($proveedor['url'])) {
                    $nombre = basename(parse_url($proveedor['url'], PHP_URL_PATH));
                    $nombre = str_replace('-', ' ', $nombre);
                    $nombre = ucwords($nombre);
                }
                
                // Verificar si el post ya existe
                $existing = get_posts(array(
                    'post_type' => 'post',
                    'meta_key' => '_cff_provider_url',
                    'meta_value' => $proveedor['url'],
                    'numberposts' => 1
                ));
                
                if (!empty($existing)) {
                    continue;
                }
                
                // Crear el post
                $post_id = wp_insert_post(array(
                    'post_title' => $nombre,
                    'post_content' => '',
                    'post_status' => 'draft'
                ));
                
                if ($post_id && !is_wp_error($post_id)) {
                    // Asignar a la taxonomía personalizada
                    wp_set_object_terms($post_id, $category->term_id, 'cataleg_categoria');
                    
                    // Guardar metadata
                    update_post_meta($post_id, '_cff_provider_url', $proveedor['url']);
                    update_post_meta($post_id, '_cff_provider_image', $proveedor['imagen']);
                    update_post_meta($post_id, '_cff_order', $index);
                    
                    $created++;
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => "S'han creat $created posts correctament (com a esborranys)",
            'created' => $created
        ));
    }
    
    /**
     * AJAX: Cargar categorías hijas para configuración
     */
    public function ajax_load_child_categories() {
        check_ajax_referer('cff_load_categories', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        $parent_id = intval($_POST['parent_id']);
        $config = get_option('cff_catalog_config', array());
        $selected_posts = isset($config['selected_posts']) ? $config['selected_posts'] : array();
        
        // Obtener categorías hijas
        $child_categories = get_categories(array(
            'parent' => $parent_id,
            'hide_empty' => false
        ));
        
        if (empty($child_categories)) {
            wp_send_json_error(array('message' => 'No hi ha categories filles'));
        }
        
        ob_start();
        
        foreach ($child_categories as $cat) {
            // Obtener posts de esta categoría
            $posts = get_posts(array(
                'category' => $cat->term_id,
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            $cat_selected = isset($selected_posts[$cat->term_id]) ? $selected_posts[$cat->term_id] : array();
            ?>
            <div class="cff-category-item" data-category-id="<?php echo $cat->term_id; ?>">
                <div class="cff-category-header">
                    <h3><?php echo esc_html($cat->name); ?></h3>
                    <span><?php echo count($posts); ?> posts</span>
                </div>
                
                <div class="cff-posts-selector">
                    <h4><?php _e('Posts disponibles:', 'catalegfiresferies'); ?></h4>
                    <div class="cff-available-posts">
                        <?php foreach ($posts as $post): ?>
                            <?php
                            $is_selected = in_array($post->ID, $cat_selected);
                            $thumbnail = get_the_post_thumbnail_url($post->ID, 'thumbnail');
                            ?>
                            <div class="cff-post-item" data-post-id="<?php echo $post->ID; ?>">
                                <input type="checkbox" 
                                       name="selected_posts[<?php echo $cat->term_id; ?>][]" 
                                       value="<?php echo $post->ID; ?>"
                                       <?php checked($is_selected); ?>>
                                <?php if ($thumbnail): ?>
                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="">
                                <?php endif; ?>
                                <span class="cff-post-title"><?php echo esc_html($post->post_title); ?></span>
                                <span class="cff-drag-handle">⋮</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Guardar configuración del catálogo
     */
    public function save_catalog_config() {
        check_admin_referer('cff_save_catalog_config', 'cff_config_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes', 'catalegfiresferies'));
        }
        
        $config_id = isset($_POST['config_id']) && !empty($_POST['config_id']) 
            ? sanitize_key($_POST['config_id']) 
            : 'config_' . time();
        
        $config_name = sanitize_text_field($_POST['config_name']);
        $parent_category = intval($_POST['parent_category']);
        $selected_posts = isset($_POST['selected_posts']) ? $_POST['selected_posts'] : array();
        $category_order = isset($_POST['category_order']) ? explode(',', $_POST['category_order']) : array();
        
        // Sanitizar posts
        foreach ($selected_posts as $cat_id => $post_ids) {
            $selected_posts[$cat_id] = array_map('intval', $post_ids);
        }
        
        // Obtener todas las configuraciones
        $all_configs = get_option('cff_catalog_configs', array());
        
        // Guardar/actualizar configuración
        $all_configs[$config_id] = array(
            'name' => $config_name,
            'parent_category' => $parent_category,
            'selected_posts' => $selected_posts,
            'category_order' => array_map('intval', $category_order)
        );
        
        update_option('cff_catalog_configs', $all_configs);
        
        wp_redirect(add_query_arg(array(
            'page' => 'catalegfiresferies-config',
            'config' => $config_id,
            'saved' => '1'
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Eliminar configuración del catálogo
     */
    public function delete_catalog_config() {
        check_admin_referer('cff_delete_config');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes', 'catalegfiresferies'));
        }
        
        $config_id = isset($_GET['config']) ? sanitize_key($_GET['config']) : '';
        
        if (!empty($config_id)) {
            $all_configs = get_option('cff_catalog_configs', array());
            unset($all_configs[$config_id]);
            update_option('cff_catalog_configs', $all_configs);
        }
        
        wp_redirect(admin_url('admin.php?page=catalegfiresferies-config&deleted=1'));
        exit;
    }
    
    /**
     * Shortcode personalizado para mostrar catálogo configurado
     */
    public function cataleg_custom_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'columnas' => 4,
        ), $atts);
        
        // Obtener configuración específica
        $all_configs = get_option('cff_catalog_configs', array());
        
        if (empty($atts['id']) || !isset($all_configs[$atts['id']])) {
            return '<p>' . __('Configuració no trobada. Especifica: [cataleg_custom id="config_id"]', 'catalegfiresferies') . '</p>';
        }
        
        $config = $all_configs[$atts['id']];
        
        if (empty($config['parent_category']) || empty($config['selected_posts'])) {
            return '<p>' . __('Catàleg no configurat completament.', 'catalegfiresferies') . '</p>';
        }
        
        $parent_id = $config['parent_category'];
        $selected_posts = $config['selected_posts'];
        $category_order = isset($config['category_order']) ? $config['category_order'] : array();
        
        // Obtener categorías hijas
        $child_categories = get_categories(array(
            'parent' => $parent_id,
            'hide_empty' => false
        ));
        
        // Ordenar categorías según el orden guardado
        if (!empty($category_order)) {
            usort($child_categories, function($a, $b) use ($category_order) {
                $pos_a = array_search($a->term_id, $category_order);
                $pos_b = array_search($b->term_id, $category_order);
                if ($pos_a === false) $pos_a = 9999;
                if ($pos_b === false) $pos_b = 9999;
                return $pos_a - $pos_b;
            });
        }
        
        if (empty($child_categories)) {
            return '<p>' . __('No hi ha categories configurades.', 'catalegfiresferies') . '</p>';
        }
        
        ob_start();
        $cols = intval($atts['columnas']);
        ?>
        <div class="cff-cataleg-custom" style="--cff-cols: <?php echo $cols; ?>">
            <?php foreach ($child_categories as $cat): ?>
                <?php
                if (empty($selected_posts[$cat->term_id])) {
                    continue;
                }
                
                $post_ids = $selected_posts[$cat->term_id];
                ?>
                <article class="categoria-proveidor" data-valor="<?php echo esc_attr($cat->slug); ?>">
                    <h3><a href="<?php echo get_category_link($cat); ?>"><?php echo esc_html($cat->name); ?></a></h3>
                    <p>
                        <?php foreach ($post_ids as $post_id): ?>
                            <?php
                            $post = get_post($post_id);
                            if (!$post) continue;
                            $thumbnail = get_the_post_thumbnail_url($post_id, 'medium');
                            ?>
                            <a href="<?php echo get_permalink($post_id); ?>">
                                <?php if ($thumbnail): ?>
                                    <img class="alignnone wp-image-<?php echo $post_id; ?>" 
                                         src="<?php echo esc_url($thumbnail); ?>" 
                                         alt="<?php echo esc_attr($post->post_title); ?>" 
                                         width="274" 
                                         height="274" />
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * AJAX: Guardar categoría padre
     */
    public function ajax_save_parent_category() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cff_parent_categories';
        
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['slug']);
        $description = wp_kses_post($_POST['description']);
        
        // Verificar si el slug ya existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE slug = %s",
            $slug
        ));
        
        if ($exists) {
            wp_send_json_error('Aquest slug ja existeix. Si us plau, tria un altre.');
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'order_num' => 0
            ),
            array('%s', '%s', '%s', '%d')
        );
        
        if ($result) {
            wp_send_json_success(array('id' => $wpdb->insert_id));
        } else {
            wp_send_json_error('Error al crear la categoria');
        }
    }
    
    /**
     * AJAX: Eliminar categoría padre
     */
    public function ajax_delete_parent_category() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        global $wpdb;
        $table_parent = $wpdb->prefix . 'cff_parent_categories';
        $table_relations = $wpdb->prefix . 'cff_category_relations';
        
        $id = intval($_POST['id']);
        
        // Eliminar relaciones primero
        $wpdb->delete($table_relations, array('parent_id' => $id), array('%d'));
        
        // Eliminar categoría padre
        $result = $wpdb->delete($table_parent, array('id' => $id), array('%d'));
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Error al eliminar la categoria');
        }
    }
    
    /**
     * AJAX: Guardar relaciones de categorías
     */
    public function ajax_save_category_relations() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cff_category_relations';
        
        $parent_id = intval($_POST['parent_id']);
        $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        
        // Eliminar relaciones existentes
        $wpdb->delete($table, array('parent_id' => $parent_id), array('%d'));
        
        // Insertar nuevas relaciones
        foreach ($categories as $index => $cat_id) {
            $wpdb->insert(
                $table,
                array(
                    'parent_id' => $parent_id,
                    'wp_category_id' => $cat_id,
                    'order_num' => $index
                ),
                array('%d', '%d', '%d')
            );
        }
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Guardar orden de favoritos
     */
    public function ajax_save_favorites_order() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        global $wpdb;
        $table_relations = $wpdb->prefix . 'cff_category_relations';
        $table_favorites = $wpdb->prefix . 'cff_favorites';
        
        $parent_id = intval($_POST['parent_id']);
        $category_order = isset($_POST['category_order']) ? $_POST['category_order'] : array();
        $posts_order = isset($_POST['posts_order']) ? $_POST['posts_order'] : array();
        
        // Actualizar orden de categorías
        foreach ($category_order as $cat_data) {
            $wpdb->update(
                $table_relations,
                array('order_num' => intval($cat_data['order'])),
                array(
                    'parent_id' => $parent_id,
                    'wp_category_id' => intval($cat_data['wp_category_id'])
                ),
                array('%d'),
                array('%d', '%d')
            );
        }
        
        // Actualizar orden de posts favoritos
        foreach ($posts_order as $cat_id => $posts) {
            $cat_id = intval($cat_id);
            foreach ($posts as $post_data) {
                $post_id = intval($post_data['post_id']);
                $order = intval($post_data['order']);
                
                // Actualizar orden (el registro ya debería existir)
                $wpdb->update(
                    $table_favorites,
                    array('order_num' => $order),
                    array(
                        'parent_id' => $parent_id,
                        'post_id' => $post_id,
                        'wp_category_id' => $cat_id
                    ),
                    array('%d'),
                    array('%d', '%d', '%d')
                );
            }
        }
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Guardar favoritos de una categoría para una categoría padre específica
     */
    public function ajax_save_category_favorites() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        global $wpdb;
        $table_favorites = $wpdb->prefix . 'cff_favorites';
        
        $parent_id = intval($_POST['parent_id']);
        $category_id = intval($_POST['category_id']);
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        // Eliminar favoritos existentes de esta categoría padre + categoría WP
        $wpdb->delete(
            $table_favorites, 
            array(
                'parent_id' => $parent_id,
                'wp_category_id' => $category_id
            ), 
            array('%d', '%d')
        );
        
        // Insertar nuevos favoritos
        foreach ($post_ids as $index => $post_id) {
            $wpdb->insert(
                $table_favorites,
                array(
                    'parent_id' => $parent_id,
                    'post_id' => $post_id,
                    'wp_category_id' => $category_id,
                    'order_num' => $index,
                    'is_favorite' => 1
                ),
                array('%d', '%d', '%d', '%d', '%d')
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Favorits guardats',
            'count' => count($post_ids)
        ));
    }
    
    /**
     * AJAX: Guardar favoritos de una categoría (legacy - para la página independiente)
     */
    public function ajax_save_favorites() {
        check_ajax_referer('cff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        global $wpdb;
        $table_favorites = $wpdb->prefix . 'cff_favorites';
        
        $category_id = intval($_POST['category_id']);
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        // Eliminar todos los favoritos de esta categoría
        $wpdb->delete($table_favorites, array('wp_category_id' => $category_id), array('%d'));
        
        // Insertar nuevos favoritos
        foreach ($post_ids as $index => $post_id) {
            $wpdb->insert(
                $table_favorites,
                array(
                    'post_id' => $post_id,
                    'wp_category_id' => $category_id,
                    'order_num' => $index,
                    'is_favorite' => 1
                ),
                array('%d', '%d', '%d', '%d')
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Favorits guardats',
            'count' => count($post_ids)
        ));
    }
    
    /**
     * Shortcode para mostrar categoría padre con sus categorías hijas y posts favoritos
     */
    public function cataleg_parent_shortcode($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
            'columnas' => 4,
            'max_favoritos' => 4,
            'filtrar' => 'si',
        ), $atts);
        
        if (empty($atts['slug'])) {
            return '<p>' . __('Cal especificar un slug: [cataleg_parent slug="nom-categoria"]', 'catalegfiresferies') . '</p>';
        }
        
        global $wpdb;
        $table_parent = $wpdb->prefix . 'cff_parent_categories';
        $table_relations = $wpdb->prefix . 'cff_category_relations';
        $table_favorites = $wpdb->prefix . 'cff_favorites';
        
        // Obtener categoría padre
        $parent = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_parent WHERE slug = %s",
            $atts['slug']
        ));
        
        if (!$parent) {
            return '<p>' . __('Categoria pare no trobada.', 'catalegfiresferies') . '</p>';
        }
        
        // Obtener categorías de WordPress asignadas (ordenadas)
        $wp_categories = $wpdb->get_results($wpdb->prepare(
            "SELECT wp_category_id FROM $table_relations WHERE parent_id = %d ORDER BY order_num ASC",
            $parent->id
        ));
        
        if (empty($wp_categories)) {
            return '<p>' . __('No hi ha categories assignades a aquesta categoria pare.', 'catalegfiresferies') . '</p>';
        }
        
        $cols = intval($atts['columnas']);
        $max = intval($atts['max_favoritos']);
        $mostrar_filtro = ($atts['filtrar'] === 'si' || $atts['filtrar'] === 'yes' || $atts['filtrar'] === '1');
        
        // Generar ID único para este catálogo
        $catalog_id = 'cff-catalog-' . sanitize_title($parent->slug) . '-' . uniqid();
        
        ob_start();
        ?>
        <div class="cff-cataleg-wrapper" id="<?php echo $catalog_id; ?>">
            <?php if ($mostrar_filtro): ?>
            <!-- Filtro de búsqueda -->
            <div class="cff-search-container" style="margin-bottom: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">
                <input type="text" 
                       class="cff-search-input" 
                       placeholder="<?php _e('Cerca per nom de categoria o proveïdor...', 'catalegfiresferies'); ?>"
                       style="width: 100%; padding: 15px 20px; font-size: 16px; border: 2px solid #ddd; border-radius: 50px; outline: none; transition: all 0.3s;">
                <div class="cff-search-results" style="margin-top: 10px; font-size: 14px; color: #666;"></div>
            </div>
            <?php endif; ?>
            
            <div class="cff-cataleg cff-cataleg-list">
            <?php foreach ($wp_categories as $relation): 
                $categoria = get_category($relation->wp_category_id);
                if (!$categoria || is_wp_error($categoria)) continue;
                
                // Obtener posts favoritos de esta categoría (ordenados) para esta categoría padre
                $favorite_posts = $wpdb->get_results($wpdb->prepare(
                    "SELECT post_id FROM $table_favorites 
                     WHERE parent_id = %d AND wp_category_id = %d AND is_favorite = 1 
                     ORDER BY order_num ASC 
                     LIMIT %d",
                    $parent->id,
                    $categoria->term_id,
                    $max
                ));
                
                if (empty($favorite_posts)) {
                    continue;
                }
                ?>
                
                <div class="cff-categoria-section" data-category-id="<?php echo $categoria->term_id; ?>" data-category-name="<?php echo esc_attr(strtolower($categoria->name)); ?>" style="margin-bottom: 50px;">
                    <h2 class="cff-categoria-titulo" style="font-size: 28px; margin-bottom: 20px; border-bottom: 3px solid #2271b1; padding-bottom: 10px;">
                        <a href="<?php echo get_category_link($categoria); ?>" style="text-decoration: none; color: inherit;">
                            <?php echo esc_html($categoria->name); ?>
                        </a>
                    </h2>
                    
                    <?php if (!empty($categoria->description)): ?>
                        <div class="cff-categoria-descripcio" style="margin-bottom: 20px; color: #666;">
                            <?php echo wpautop($categoria->description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="cff-posts-grid" style="display: grid; grid-template-columns: repeat(<?php echo $cols; ?>, 1fr); gap: 20px; margin-bottom: 20px;">
                        <?php foreach ($favorite_posts as $fav): 
                            $post = get_post($fav->post_id);
                            if (!$post) continue;
                        ?>
                            <article class="cff-post-item cff-favorit" data-post-title="<?php echo esc_attr(strtolower($post->post_title)); ?>">
                                <?php if (has_post_thumbnail($post->ID)): ?>
                                    <div class="cff-post-thumbnail" style="position: relative;">
                                        <a href="<?php echo get_permalink($post->ID); ?>">
                                            <?php echo get_the_post_thumbnail($post->ID, 'medium'); ?>
                                        </a>
                                        <span class="cff-favorit-badge" style="position: absolute; top: 10px; right: 10px; background: #ffb900; padding: 5px 10px; border-radius: 5px; font-size: 18px;">⭐</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="cff-post-content" style="padding: 15px;">
                                    <h4 class="cff-post-title" style="margin: 0 0 10px 0; font-size: 18px;">
                                        <a href="<?php echo get_permalink($post->ID); ?>" style="text-decoration: none; color: inherit;">
                                            <?php echo esc_html($post->post_title); ?>
                                        </a>
                                    </h4>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cff-categoria-footer" style="text-align: right;">
                        <a href="<?php echo get_category_link($categoria); ?>" class="cff-veure-tots" style="display: inline-block; padding: 10px 20px; background: #2271b1; color: white; text-decoration: none; border-radius: 5px; transition: all 0.3s;">
                            <?php _e('Veure tots', 'catalegfiresferies'); ?> →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($mostrar_filtro): ?>
        <script>
        (function() {
            var catalogId = '<?php echo $catalog_id; ?>';
            var catalog = document.getElementById(catalogId);
            var searchInput = catalog.querySelector('.cff-search-input');
            var searchResults = catalog.querySelector('.cff-search-results');
            var sections = catalog.querySelectorAll('.cff-categoria-section');
            
            // Función de búsqueda
            searchInput.addEventListener('input', function() {
                var searchTerm = this.value.toLowerCase().trim();
                var visibleCategories = 0;
                var visiblePosts = 0;
                
                if (searchTerm === '') {
                    // Mostrar todo
                    sections.forEach(function(section) {
                        section.style.display = '';
                        var posts = section.querySelectorAll('.cff-post-item');
                        posts.forEach(function(post) {
                            post.style.display = '';
                        });
                        visibleCategories++;
                        visiblePosts += posts.length;
                    });
                    searchResults.textContent = '';
                } else {
                    // Filtrar
                    sections.forEach(function(section) {
                        var categoryName = section.getAttribute('data-category-name');
                        var posts = section.querySelectorAll('.cff-post-item');
                        var categoryMatch = categoryName.indexOf(searchTerm) !== -1;
                        var visiblePostsInCategory = 0;
                        
                        // Filtrar posts
                        posts.forEach(function(post) {
                            var postTitle = post.getAttribute('data-post-title');
                            if (categoryMatch || postTitle.indexOf(searchTerm) !== -1) {
                                post.style.display = '';
                                visiblePostsInCategory++;
                            } else {
                                post.style.display = 'none';
                            }
                        });
                        
                        // Mostrar/ocultar categoría
                        if (visiblePostsInCategory > 0) {
                            section.style.display = '';
                            visibleCategories++;
                            visiblePosts += visiblePostsInCategory;
                        } else {
                            section.style.display = 'none';
                        }
                    });
                    
                    // Actualizar resultados
                    if (visiblePosts === 0) {
                        searchResults.textContent = '<?php _e('No s\'han trobat resultats', 'catalegfiresferies'); ?>';
                        searchResults.style.color = '#d63638';
                    } else {
                        searchResults.textContent = visibleCategories + ' <?php _e('categories', 'catalegfiresferies'); ?>, ' + visiblePosts + ' <?php _e('proveïdors', 'catalegfiresferies'); ?>';
                        searchResults.style.color = '#2271b1';
                    }
                }
            });
            
            // Focus effect
            searchInput.addEventListener('focus', function() {
                this.style.borderColor = '#2271b1';
                this.style.boxShadow = '0 0 0 3px rgba(34, 113, 177, 0.1)';
            });
            
            searchInput.addEventListener('blur', function() {
                this.style.borderColor = '#ddd';
                this.style.boxShadow = 'none';
            });
        })();
        </script>
        
        <style>
        @media (max-width: 768px) {
            .cff-posts-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        @media (max-width: 480px) {
            .cff-posts-grid {
                grid-template-columns: 1fr !important;
            }
        }
        .cff-post-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .cff-post-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .cff-post-item img {
            width: 100%;
            height: auto;
            display: block;
        }
        .cff-veure-tots:hover {
            background: #135e96;
        }
        </style>
        <?php endif; ?>
        <?php
        
        return ob_get_clean();
    }
}

// Inicializar el plugin
new CatalegFiresFeries();
