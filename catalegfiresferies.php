<?php
/**
 * Plugin Name: Catàleg Fires i Fèries
 * Plugin URI: https://festesmajorsdecatalunya.cat
 * Description: Plugin para gestionar catálogo de fires i fèries con categorías y favoritos
 * Version: 2.0.0
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
define('CFF_VERSION', '2.0.0');
define('CFF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar librería de actualizaciones
require CFF_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Configurar auto-update desde GitHub
$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/USUARIO/catalegfiresferies/', // <-- CAMBIAR POR TU REPO
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
        
        // Metabox para posts
        add_action('add_meta_boxes', array($this, 'add_metabox'));
        add_action('save_post', array($this, 'save_metabox_data'));
        
        // Shortcode
        add_shortcode('cataleg_festes', array($this, 'cataleg_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_cff_import_rtf', array($this, 'ajax_import_rtf'));
        add_action('wp_ajax_cff_create_categories', array($this, 'ajax_create_categories'));
        add_action('wp_ajax_cff_import_posts', array($this, 'ajax_import_posts'));
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Crear tabla personalizada si es necesaria
        global $wpdb;
        $table_name = $wpdb->prefix . 'cff_favorites';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            order_num int(11) DEFAULT 0,
            is_favorite tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar tareas programadas si las hay
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
        if ('toplevel_page_catalegfiresferies' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        wp_enqueue_style('cff-admin', CFF_PLUGIN_URL . 'assets/css/admin.css', array(), CFF_VERSION);
        wp_enqueue_script('cff-admin', CFF_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CFF_VERSION, true);
        
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
    }
    
    /**
     * Página de administración
     */
    public function admin_page() {
        include CFF_PLUGIN_DIR . 'admin/admin-page.php';
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
     * Shortcode para mostrar el catálogo
     */
    public function cataleg_shortcode($atts) {
        $atts = shortcode_atts(array(
            'categoria' => '',
            'mostrar_favoritos' => 'si',
            'posts_por_pagina' => -1
        ), $atts);
        
        ob_start();
        
        // Obtener categorías padres
        $parent_categories = get_categories(array(
            'parent' => 0,
            'hide_empty' => false
        ));
        
        if (!empty($atts['categoria'])) {
            $parent_categories = get_categories(array(
                'slug' => $atts['categoria'],
                'hide_empty' => false
            ));
        }
        
        ?>
        <div class="cff-cataleg">
            <?php foreach ($parent_categories as $parent_cat): ?>
                <div class="cff-categoria-seccion" id="cat-<?php echo $parent_cat->term_id; ?>">
                    <h2 class="cff-categoria-titulo"><?php echo esc_html($parent_cat->name); ?></h2>
                    
                    <?php if (!empty($parent_cat->description)): ?>
                        <div class="cff-categoria-descripcio">
                            <?php echo wpautop($parent_cat->description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Obtener subcategorías
                    $child_categories = get_categories(array(
                        'parent' => $parent_cat->term_id,
                        'hide_empty' => false
                    ));
                    
                    foreach ($child_categories as $child_cat):
                        // Query para posts de esta categoría
                        $args = array(
                            'post_type' => 'post',
                            'cat' => $child_cat->term_id,
                            'posts_per_page' => $atts['posts_por_pagina'],
                            'meta_key' => '_cff_order',
                            'orderby' => array(
                                'meta_value_num' => 'ASC',
                                'date' => 'DESC'
                            )
                        );
                        
                        // Filtrar por favoritos si está activado
                        if ($atts['mostrar_favoritos'] === 'si') {
                            $args['meta_query'] = array(
                                'relation' => 'OR',
                                array(
                                    'key' => '_cff_is_favorite',
                                    'value' => '1',
                                    'compare' => '='
                                ),
                                array(
                                    'key' => '_cff_is_favorite',
                                    'compare' => 'NOT EXISTS'
                                )
                            );
                        }
                        
                        $query = new WP_Query($args);
                        
                        if ($query->have_posts()):
                    ?>
                        <div class="cff-subcategoria">
                            <h3 class="cff-subcategoria-titulo"><?php echo esc_html($child_cat->name); ?></h3>
                            
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
                        </div>
                    <?php
                        endif;
                        wp_reset_postdata();
                    endforeach;
                    ?>
                </div>
            <?php endforeach; ?>
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
        $parent_cat_id = 0;
        
        // Crear categoría padre "Catàleg Fires i Fèries" si no existe
        $parent_cat = get_term_by('slug', 'cataleg-fires-feries', 'category');
        if (!$parent_cat) {
            $parent_result = wp_insert_term(
                'Catàleg Fires i Fèries',
                'category',
                array('slug' => 'cataleg-fires-feries')
            );
            if (!is_wp_error($parent_result)) {
                $parent_cat_id = $parent_result['term_id'];
            }
        } else {
            $parent_cat_id = $parent_cat->term_id;
        }
        
        // Crear subcategorías
        foreach ($categories_data as $cat_data) {
            $slug = sanitize_title($cat_data['data_valor']);
            $existing = get_term_by('slug', $slug, 'category');
            
            if (!$existing) {
                $result = wp_insert_term(
                    $cat_data['titulo'],
                    'category',
                    array(
                        'slug' => $slug,
                        'parent' => $parent_cat_id
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
            $category = get_term_by('slug', $cat_slug, 'category');
            
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
                    'post_status' => 'draft',
                    'post_category' => array($category->term_id)
                ));
                
                if ($post_id && !is_wp_error($post_id)) {
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
}

// Inicializar el plugin
new CatalegFiresFeries();
