<?php
/**
 * Custom Post Type "produto_360".
 *
 * @package Produto_360
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class P360_Post_Type {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		$labels = array(
			'name'                  => __( 'Produtos 360°', 'produto-360' ),
			'singular_name'         => __( 'Produto 360°', 'produto-360' ),
			'menu_name'             => __( 'Produtos 360°', 'produto-360' ),
			'name_admin_bar'        => __( 'Produto 360°', 'produto-360' ),
			'add_new'               => __( 'Adicionar Novo', 'produto-360' ),
			'add_new_item'          => __( 'Adicionar Novo Produto', 'produto-360' ),
			'new_item'              => __( 'Novo Produto', 'produto-360' ),
			'edit_item'             => __( 'Editar Produto', 'produto-360' ),
			'view_item'             => __( 'Ver Produto', 'produto-360' ),
			'all_items'             => __( 'Todos os Produtos', 'produto-360' ),
			'search_items'          => __( 'Buscar Produtos', 'produto-360' ),
			'not_found'             => __( 'Nenhum produto encontrado.', 'produto-360' ),
			'not_found_in_trash'    => __( 'Nenhum produto na lixeira.', 'produto-360' ),
			'featured_image'        => __( 'Imagem de capa', 'produto-360' ),
			'set_featured_image'    => __( 'Definir imagem de capa', 'produto-360' ),
			'remove_featured_image' => __( 'Remover imagem de capa', 'produto-360' ),
			'use_featured_image'    => __( 'Usar como imagem de capa', 'produto-360' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => false,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-image-rotate',
			'supports'           => array( 'title' ),
			'has_archive'        => false,
			'rewrite'            => false,
			'query_var'          => false,
		);

		register_post_type( P360_POST_TYPE, $args );
	}

	/**
	 * Recupera o post 360 a partir de slug ou ID.
	 *
	 * @param string|int $identifier Slug ou ID do produto.
	 * @return WP_Post|null
	 */
	public static function get_product( $identifier ) {
		if ( empty( $identifier ) ) {
			return null;
		}

		// Tenta por ID numérico
		if ( is_numeric( $identifier ) ) {
			$post = get_post( intval( $identifier ) );
			if ( $post && P360_POST_TYPE === $post->post_type ) {
				return $post;
			}
		}

		// Tenta por slug
		$post = get_page_by_path( sanitize_title( $identifier ), OBJECT, P360_POST_TYPE );
		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * Retorna o array de IDs de imagens (em ordem) de um produto.
	 *
	 * @param int $post_id ID do post.
	 * @return int[] Array de attachment IDs.
	 */
	public static function get_images( $post_id ) {
		$ids = get_post_meta( $post_id, '_p360_images', true );
		if ( ! is_array( $ids ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}

	/**
	 * Retorna as URLs (em ordem) das imagens de um produto.
	 *
	 * @param int    $post_id ID do post.
	 * @param string $size    Tamanho da imagem (padrão: 'large').
	 * @return string[]
	 */
	public static function get_image_urls( $post_id, $size = 'large' ) {
		$ids  = self::get_images( $post_id );
		$urls = array();
		foreach ( $ids as $id ) {
			$url = wp_get_attachment_image_url( $id, $size );
			if ( $url ) {
				$urls[] = $url;
			}
		}
		return $urls;
	}

	/**
	 * Salva os IDs de imagens (em ordem).
	 *
	 * @param int   $post_id
	 * @param int[] $ids
	 */
	public static function save_images( $post_id, $ids ) {
		$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
		update_post_meta( $post_id, '_p360_images', $ids );
	}

	/**
	 * Salva as opções de um produto.
	 */
	public static function save_settings( $post_id, $settings ) {
		$defaults = array(
			'autoplay'  => 1,
			'fps'       => 12,
			'direction' => 1,
			'color'     => '#295F7A',
		);
		$settings = wp_parse_args( $settings, $defaults );

		$clean = array(
			'autoplay'  => $settings['autoplay'] ? 1 : 0,
			'fps'       => max( 1, min( 60, intval( $settings['fps'] ) ) ),
			'direction' => intval( $settings['direction'] ) >= 0 ? 1 : -1,
			'color'     => sanitize_hex_color( $settings['color'] ) ? $settings['color'] : '#295F7A',
		);

		update_post_meta( $post_id, '_p360_settings', $clean );
	}

	/**
	 * Recupera as opções de um produto (com defaults).
	 */
	public static function get_settings( $post_id ) {
		$saved = get_post_meta( $post_id, '_p360_settings', true );
		$defaults = array(
			'autoplay'  => 1,
			'fps'       => 12,
			'direction' => 1,
			'color'     => '#295F7A',
		);
		if ( ! is_array( $saved ) ) {
			return $defaults;
		}
		return wp_parse_args( $saved, $defaults );
	}
}
