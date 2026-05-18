<?php
/**
 * Gerenciador de assets do plugin.
 *
 * @package Produto_360
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class P360_Assets {

	private static $used_in_page = false;

	public static function init() {
		// Registra (mas não enfileira) os assets de front
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_front_assets' ) );

		// No footer, decide se enfileira ou não (otimização)
		add_action( 'wp_footer', array( __CLASS__, 'maybe_enqueue_front_assets' ), 5 );
	}

	/**
	 * Marca que o shortcode foi usado nesta requisição.
	 */
	public static function mark_used() {
		self::$used_in_page = true;
	}

	public static function register_front_assets() {
		wp_register_style(
			'p360-viewer',
			P360_PLUGIN_URL . 'public/css/p360-viewer.css',
			array(),
			P360_VERSION
		);

		wp_register_script(
			'p360-viewer',
			P360_PLUGIN_URL . 'public/js/p360-viewer.js',
			array(),
			P360_VERSION,
			true
		);
	}

	public static function maybe_enqueue_front_assets() {
		if ( ! self::$used_in_page ) {
			return;
		}
		wp_enqueue_style( 'p360-viewer' );
		wp_enqueue_script( 'p360-viewer' );
	}
}
