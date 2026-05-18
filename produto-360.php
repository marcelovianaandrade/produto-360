<?php
/**
 * Plugin Name:       Produto 360
 * Plugin URI:        https://github.com/marceloviana/produto-360
 * Description:       Visualizador de produtos em 360° com 36 imagens sequenciais. Suporta múltiplos produtos independentes via shortcode.
 * Version:           1.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.2
 * Author:            Marcelo Viana de Andrade
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       produto-360
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constantes do plugin
define( 'P360_VERSION', '1.0.0' );
define( 'P360_PLUGIN_FILE', __FILE__ );
define( 'P360_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'P360_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'P360_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'P360_POST_TYPE', 'produto_360' );

// Carrega os arquivos do plugin
require_once P360_PLUGIN_DIR . 'includes/class-p360-post-type.php';
require_once P360_PLUGIN_DIR . 'includes/class-p360-shortcode.php';
require_once P360_PLUGIN_DIR . 'includes/class-p360-assets.php';
require_once P360_PLUGIN_DIR . 'admin/class-p360-admin.php';

/**
 * Classe principal do plugin (Singleton).
 */
class Produto_360 {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Tradução
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Inicializa componentes
		P360_Post_Type::init();
		P360_Shortcode::init();
		P360_Assets::init();

		if ( is_admin() ) {
			P360_Admin::init();
		}

		// Link "Configurações" na listagem de plugins
		add_filter( 'plugin_action_links_' . P360_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

		// Hooks de ativação/desativação
		register_activation_hook( P360_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( P360_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'produto-360', false, dirname( P360_PLUGIN_BASENAME ) . '/languages' );
	}

	public function add_action_links( $links ) {
		$url   = admin_url( 'edit.php?post_type=' . P360_POST_TYPE );
		$custom = array(
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Produtos 360', 'produto-360' ) . '</a>',
		);
		return array_merge( $custom, $links );
	}

	public static function activate() {
		// Garante que o CPT seja registrado para limpar rewrite rules
		P360_Post_Type::register();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}

// Inicializa o plugin
Produto_360::get_instance();
