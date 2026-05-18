<?php
/**
 * Shortcode [produto360]
 *
 * @package Produto_360
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class P360_Shortcode {

	/** @var int Contador para gerar IDs únicos em múltiplas instâncias na mesma página. */
	private static $instance_counter = 0;

	public static function init() {
		add_shortcode( 'produto360', array( __CLASS__, 'render' ) );
	}

	/**
	 * Renderiza o shortcode.
	 *
	 * Aceita os atributos:
	 *  - id          : slug do produto (obrigatório). Ex: "produto-a"
	 *                  Aceita ID numérico como fallback.
	 *  - width       : largura máxima (ex: "520px", "100%")
	 *  - height      : altura fixa opcional (ex: "520px"). Padrão: aspect ratio 1:1
	 *  - autoplay    : "yes" | "no" (sobrescreve config do produto)
	 *  - class       : classe CSS extra
	 *
	 * Demais opções (fps, direção, cor) são definidas por produto na admin.
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => '',
				'width'    => '520px',
				'height'   => '',
				'autoplay' => '',
				'class'    => '',
			),
			$atts,
			'produto360'
		);

		if ( empty( $atts['id'] ) ) {
			return self::error_message( __( 'Atributo "id" é obrigatório.', 'produto-360' ) );
		}

		$post = P360_Post_Type::get_product( $atts['id'] );
		if ( ! $post ) {
			return self::error_message(
				sprintf(
					/* translators: %s identifier */
					__( 'Produto 360° não encontrado: %s', 'produto-360' ),
					esc_html( $atts['id'] )
				)
			);
		}

		$urls = P360_Post_Type::get_image_urls( $post->ID, 'large' );
		if ( count( $urls ) < 2 ) {
			return self::error_message( __( 'Este produto não possui imagens suficientes para visualização 360°.', 'produto-360' ) );
		}

		$settings = P360_Post_Type::get_settings( $post->ID );

		// Override apenas do autoplay (demais configs ficam no produto)
		if ( '' !== $atts['autoplay'] ) {
			$settings['autoplay'] = in_array( strtolower( $atts['autoplay'] ), array( 'yes', 'true', '1' ), true ) ? 1 : 0;
		}

		self::$instance_counter++;
		$instance_id = 'p360-instance-' . $post->ID . '-' . self::$instance_counter;

		// Sanitização de dimensões
		$width  = self::sanitize_dimension( $atts['width'], '520px' );
		$height = self::sanitize_dimension( $atts['height'], '' );

		$extra_class = sanitize_html_class( $atts['class'] );

		// Sinaliza para o Assets enfileirar o script no footer
		P360_Assets::mark_used();

		$config = array(
			'images'    => $urls,
			'autoplay'  => (bool) $settings['autoplay'],
			'fps'       => intval( $settings['fps'] ),
			'direction' => intval( $settings['direction'] ),
			'color'     => $settings['color'],
		);

		// Monta o style inline
		$style  = 'max-width: ' . esc_attr( $width ) . ';';
		$style .= ' --p360-color: ' . esc_attr( $settings['color'] ) . ';';
		if ( ! empty( $height ) ) {
			$style .= ' height: ' . esc_attr( $height ) . '; aspect-ratio: auto;';
		}

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $instance_id ); ?>"
			class="p360-viewer <?php echo esc_attr( $extra_class ); ?>"
			data-p360-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
			style="<?php echo $style; // já escapado acima ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'Visualização 360° de %s', 'produto-360' ), $post->post_title ) ); ?>"
		>
			<div class="p360-loader" aria-hidden="true">
				<div class="p360-spinner"></div>
				<div class="p360-loader-text"><?php esc_html_e( 'Carregando…', 'produto-360' ); ?></div>
			</div>

			<div class="p360-stage">
				<img class="p360-img" alt="<?php echo esc_attr( $post->post_title ); ?>" />
			</div>

			<div class="p360-hint"><?php esc_html_e( 'Arraste para girar • Role para zoom', 'produto-360' ); ?></div>

			<div class="p360-zoom" role="group" aria-label="<?php esc_attr_e( 'Controles de zoom', 'produto-360' ); ?>">
				<button type="button" class="p360-btn" data-z="-" aria-label="<?php esc_attr_e( 'Diminuir zoom', 'produto-360' ); ?>">−</button>
				<button type="button" class="p360-btn" data-z="+" aria-label="<?php esc_attr_e( 'Aumentar zoom', 'produto-360' ); ?>">+</button>
				<button type="button" class="p360-btn" data-z="reset" aria-label="<?php esc_attr_e( 'Resetar zoom', 'produto-360' ); ?>"><?php esc_html_e( 'Reset', 'produto-360' ); ?></button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Valida e sanitiza um valor de dimensão CSS.
	 * Aceita px, %, em, rem, vw, vh.
	 */
	private static function sanitize_dimension( $value, $default ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return $default;
		}
		if ( preg_match( '/^\d+(\.\d+)?(px|%|em|rem|vw|vh)?$/', $value ) ) {
			return $value;
		}
		return $default;
	}

	private static function error_message( $message ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}
		return '<div class="p360-error" style="padding:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;font-size:14px;">'
			. '<strong>[produto360]</strong> ' . esc_html( $message ) . '</div>';
	}
}
