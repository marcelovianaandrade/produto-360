<?php
/**
 * Interface administrativa.
 *
 * @package Produto_360
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class P360_Admin {

	public static function init() {
		// Meta boxes
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . P360_POST_TYPE, array( __CLASS__, 'save_post' ), 10, 2 );

		// Assets da admin
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );

		// Colunas customizadas na listagem
		add_filter( 'manage_' . P360_POST_TYPE . '_posts_columns', array( __CLASS__, 'set_columns' ) );
		add_action( 'manage_' . P360_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'p360-images',
			__( 'Imagens 360° (36 frames)', 'produto-360' ),
			array( __CLASS__, 'render_images_meta_box' ),
			P360_POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'p360-preview',
			__( 'Pré-visualização', 'produto-360' ),
			array( __CLASS__, 'render_preview_meta_box' ),
			P360_POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'p360-settings',
			__( 'Configurações do visualizador', 'produto-360' ),
			array( __CLASS__, 'render_settings_meta_box' ),
			P360_POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'p360-shortcode',
			__( 'Shortcode', 'produto-360' ),
			array( __CLASS__, 'render_shortcode_meta_box' ),
			P360_POST_TYPE,
			'side',
			'default'
		);
	}

	public static function enqueue_admin_assets( $hook ) {
		global $post;

		// Só carrega na tela de edição do nosso CPT
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( ! $post || P360_POST_TYPE !== $post->post_type ) {
			return;
		}

		// Media library
		wp_enqueue_media();

		// jQuery UI Sortable (para drag-and-drop)
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// CSS admin
		wp_enqueue_style(
			'p360-admin',
			P360_PLUGIN_URL . 'admin/css/p360-admin.css',
			array(),
			P360_VERSION
		);

		// JS admin
		wp_enqueue_script(
			'p360-admin',
			P360_PLUGIN_URL . 'admin/js/p360-admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ),
			P360_VERSION,
			true
		);

		// Front viewer (para preview)
		wp_enqueue_style(
			'p360-viewer',
			P360_PLUGIN_URL . 'public/css/p360-viewer.css',
			array(),
			P360_VERSION
		);
		wp_enqueue_script(
			'p360-viewer',
			P360_PLUGIN_URL . 'public/js/p360-viewer.js',
			array(),
			P360_VERSION,
			true
		);

		wp_localize_script(
			'p360-admin',
			'P360Admin',
			array(
				'i18n' => array(
					'selectImages'   => __( 'Selecionar imagens', 'produto-360' ),
					'useThese'       => __( 'Usar estas imagens', 'produto-360' ),
					'confirmRemove'  => __( 'Remover esta imagem da sequência?', 'produto-360' ),
					'confirmClear'   => __( 'Remover TODAS as imagens?', 'produto-360' ),
					'imagesCount'    => __( 'imagens na sequência', 'produto-360' ),
					'recommendedQty' => __( 'O ideal são 36 imagens para rotação completa.', 'produto-360' ),
				),
			)
		);
	}

	public static function render_images_meta_box( $post ) {
		wp_nonce_field( 'p360_save_post', 'p360_nonce' );

		$ids = P360_Post_Type::get_images( $post->ID );
		?>
		<div class="p360-admin-images">
			<p class="description">
				<?php esc_html_e( 'Faça upload de até 36 imagens. Arraste para reordenar. Você também pode ordenar automaticamente pelo nome do arquivo.', 'produto-360' ); ?>
			</p>

			<div class="p360-toolbar">
				<button type="button" class="button button-primary" id="p360-select-images">
					<span class="dashicons dashicons-images-alt2" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Adicionar imagens', 'produto-360' ); ?>
				</button>
				<button type="button" class="button" id="p360-sort-name">
					<span class="dashicons dashicons-sort"></span>
					<?php esc_html_e( 'Ordenar pelo nome', 'produto-360' ); ?>
				</button>
				<button type="button" class="button" id="p360-clear">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Limpar tudo', 'produto-360' ); ?>
				</button>
				<span class="p360-counter">
					<strong id="p360-count"><?php echo count( $ids ); ?></strong>
					<?php esc_html_e( 'imagens', 'produto-360' ); ?>
				</span>
			</div>

			<ul class="p360-list" id="p360-list">
				<?php foreach ( $ids as $idx => $img_id ) : ?>
					<?php
					$thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' );
					if ( ! $thumb ) {
						continue;
					}
					?>
					<li class="p360-item" data-id="<?php echo esc_attr( $img_id ); ?>">
						<span class="p360-frame">#<?php echo esc_html( $idx + 1 ); ?></span>
						<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
						<button type="button" class="p360-remove" aria-label="<?php esc_attr_e( 'Remover', 'produto-360' ); ?>">×</button>
						<input type="hidden" name="p360_images[]" value="<?php echo esc_attr( $img_id ); ?>" />
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="p360-empty" <?php echo count( $ids ) > 0 ? 'style="display:none;"' : ''; ?>>
				<p><?php esc_html_e( 'Nenhuma imagem adicionada ainda.', 'produto-360' ); ?></p>
			</div>
		</div>
		<?php
	}

	public static function render_preview_meta_box( $post ) {
		$urls     = P360_Post_Type::get_image_urls( $post->ID, 'large' );
		$settings = P360_Post_Type::get_settings( $post->ID );

		if ( count( $urls ) < 2 ) {
			echo '<p class="description">' . esc_html__( 'Adicione pelo menos 2 imagens para visualizar a pré-visualização.', 'produto-360' ) . '</p>';
			return;
		}

		$config = array(
			'images'    => $urls,
			'autoplay'  => (bool) $settings['autoplay'],
			'fps'       => intval( $settings['fps'] ),
			'direction' => intval( $settings['direction'] ),
			'color'     => $settings['color'],
		);
		?>
		<div
			class="p360-viewer"
			id="p360-admin-preview"
			data-p360-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
			style="max-width:520px;margin:0 auto;--p360-color:<?php echo esc_attr( $settings['color'] ); ?>;"
		>
			<div class="p360-loader" aria-hidden="true">
				<div class="p360-spinner"></div>
				<div class="p360-loader-text"><?php esc_html_e( 'Carregando…', 'produto-360' ); ?></div>
			</div>
			<div class="p360-stage">
				<img class="p360-img" alt="" />
			</div>
			<div class="p360-hint"><?php esc_html_e( 'Arraste para girar', 'produto-360' ); ?></div>
			<div class="p360-zoom">
				<button type="button" class="p360-btn" data-z="-">−</button>
				<button type="button" class="p360-btn" data-z="+">+</button>
				<button type="button" class="p360-btn" data-z="reset">Reset</button>
			</div>
		</div>
		<?php
	}

	public static function render_settings_meta_box( $post ) {
		$settings = P360_Post_Type::get_settings( $post->ID );
		?>
		<p>
			<label>
				<input type="checkbox" name="p360_autoplay" value="1" <?php checked( $settings['autoplay'], 1 ); ?> />
				<?php esc_html_e( 'Autoplay (rotação automática)', 'produto-360' ); ?>
			</label>
		</p>

		<p>
			<label for="p360_fps"><strong><?php esc_html_e( 'Velocidade (FPS):', 'produto-360' ); ?></strong></label><br>
			<input type="number" id="p360_fps" name="p360_fps" value="<?php echo esc_attr( $settings['fps'] ); ?>" min="1" max="60" step="1" style="width:80px;" />
			<span class="description"><?php esc_html_e( '1–60 quadros por segundo', 'produto-360' ); ?></span>
		</p>

		<p>
			<label for="p360_direction"><strong><?php esc_html_e( 'Direção:', 'produto-360' ); ?></strong></label><br>
			<select id="p360_direction" name="p360_direction">
				<option value="1" <?php selected( $settings['direction'], 1 ); ?>><?php esc_html_e( 'Horária', 'produto-360' ); ?></option>
				<option value="-1" <?php selected( $settings['direction'], -1 ); ?>><?php esc_html_e( 'Anti-horária', 'produto-360' ); ?></option>
			</select>
		</p>

		<p>
			<label for="p360_color"><strong><?php esc_html_e( 'Cor dos controles:', 'produto-360' ); ?></strong></label><br>
			<input type="text" id="p360_color" name="p360_color" value="<?php echo esc_attr( $settings['color'] ); ?>" class="p360-color-picker" />
		</p>
		<?php
	}

	public static function render_shortcode_meta_box( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p class="description">' . esc_html__( 'Salve o produto para gerar o shortcode.', 'produto-360' ) . '</p>';
			return;
		}

		$slug      = $post->post_name ? $post->post_name : $post->ID;
		$shortcode = '[produto360 id="' . $slug . '"]';
		?>
		<p><?php esc_html_e( 'Copie e cole em qualquer página ou post:', 'produto-360' ); ?></p>
		<input
			type="text"
			readonly
			value="<?php echo esc_attr( $shortcode ); ?>"
			onclick="this.select();"
			style="width:100%;font-family:monospace;padding:8px;background:#f6f7f7;border:1px solid #ccd0d4;"
		/>
		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( 'Atributos opcionais:', 'produto-360' ); ?><br>
			<code>width="520px"</code><br>
			<code>autoplay="yes"</code> | <code>autoplay="no"</code><br>
			<code>fps="12"</code><br>
			<code>color="#295F7A"</code>
		</p>
		<?php
	}

	public static function save_post( $post_id, $post ) {
		// Verificações de segurança
		if ( ! isset( $_POST['p360_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( wp_unslash( $_POST['p360_nonce'] ), 'p360_save_post' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Imagens
		$images = isset( $_POST['p360_images'] ) ? (array) wp_unslash( $_POST['p360_images'] ) : array();
		P360_Post_Type::save_images( $post_id, $images );

		// Configurações
		$settings = array(
			'autoplay'  => isset( $_POST['p360_autoplay'] ) ? 1 : 0,
			'fps'       => isset( $_POST['p360_fps'] ) ? intval( $_POST['p360_fps'] ) : 12,
			'direction' => isset( $_POST['p360_direction'] ) ? intval( $_POST['p360_direction'] ) : 1,
			'color'     => isset( $_POST['p360_color'] ) ? sanitize_text_field( wp_unslash( $_POST['p360_color'] ) ) : '#295F7A',
		);
		P360_Post_Type::save_settings( $post_id, $settings );
	}

	public static function set_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['p360_thumb']     = __( 'Capa', 'produto-360' );
				$new['p360_count']     = __( 'Imagens', 'produto-360' );
				$new['p360_shortcode'] = __( 'Shortcode', 'produto-360' );
			}
		}
		return $new;
	}

	public static function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'p360_thumb':
				$ids = P360_Post_Type::get_images( $post_id );
				if ( ! empty( $ids[0] ) ) {
					$url = wp_get_attachment_image_url( $ids[0], 'thumbnail' );
					if ( $url ) {
						echo '<img src="' . esc_url( $url ) . '" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:4px;" />';
					}
				} else {
					echo '—';
				}
				break;

			case 'p360_count':
				$count = count( P360_Post_Type::get_images( $post_id ) );
				$class = $count >= 36 ? 'p360-ok' : ( $count > 0 ? 'p360-partial' : 'p360-empty' );
				echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $count ) . ' / 36</span>';
				break;

			case 'p360_shortcode':
				$post = get_post( $post_id );
				if ( ! $post ) {
					return;
				}
				$slug      = $post->post_name ? $post->post_name : $post->ID;
				$shortcode = '[produto360 id="' . $slug . '"]';
				echo '<code style="font-size:11px;cursor:pointer;" onclick="navigator.clipboard.writeText(this.innerText);" title="' . esc_attr__( 'Clique para copiar', 'produto-360' ) . '">'
					. esc_html( $shortcode ) . '</code>';
				break;
		}
	}
}
