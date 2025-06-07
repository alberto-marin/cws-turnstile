<?php
/**
 * Plugin Name: CWS Turnstile
 * Plugin URI: https://creativewebstudio.co.uk
 * Description: Integrates Cloudflare Turnstile protection for WordPress search forms
 * Version: 1.1.1
 * Author: Alberto Marin
 * Author URI: https://creativewebstudio.co.uk
 * Text Domain: cws-turnstile
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package CWS_Turnstile
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class CWS_Turnstile {
	/**
	 * Plugin version
	 *
	 * @var string
	 */
	const VERSION = '1.1.0';

	/**
	 * Option name for site key
	 *
	 * @var string
	 */
	const SITE_KEY_OPTION = 'cws_turnstile_site_key';

	/**
	 * Option name for secret key
	 *
	 * @var string
	 */
	const SECRET_KEY_OPTION = 'cws_turnstile_secret_key';

	/**
	 * Option name for theme
	 *
	 * @var string
	 */
	const THEME_OPTION = 'cws_turnstile_theme';

	/**
	 * Option name for size
	 *
	 * @var string
	 */
	const SIZE_OPTION = 'cws_turnstile_size';

	/**
	 * Option name for search form protection
	 *
	 * @var string
	 */
	const ENABLE_SEARCH_OPTION = 'cws_turnstile_enable_search';

	/**
	 * Option name for comment form protection
	 *
	 * @var string
	 */
	const ENABLE_COMMENTS_OPTION = 'cws_turnstile_enable_comments';

	/**
	 * Instance of this class
	 *
	 * @var CWS_Turnstile
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin
	 */
	private function __construct() {
		// Plugin setup actions.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// Frontend functionality.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'get_search_form', array( $this, 'inject_turnstile_into_search_form' ) );
		add_action( 'template_redirect', array( $this, 'verify_search_turnstile' ) );
		
		 // Comment form protection
		$enable_comments = get_option( self::ENABLE_COMMENTS_OPTION, false );
		if ( $enable_comments ) {
			add_filter( 'comment_form_after_fields', array( $this, 'add_turnstile_to_comment_form' ) );
			add_filter( 'preprocess_comment', array( $this, 'verify_comment_turnstile' ) );
		}
		
		// Define constants for backward compatibility.
		if ( ! defined( 'TURNSTILE_SITE_KEY' ) ) {
			define( 'TURNSTILE_SITE_KEY', $this->get_site_key() );
		}
		
		if ( ! defined( 'TURNSTILE_SECRET_KEY' ) ) {
			define( 'TURNSTILE_SECRET_KEY', $this->get_secret_key() );
		}
	}

	/**
	 * Return an instance of this class
	 *
	 * @return CWS_Turnstile A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the Turnstile site key
	 *
	 * @return string The site key.
	 */
	public function get_site_key() {
		return get_option( self::SITE_KEY_OPTION, '' );
	}

	/**
	 * Get the Turnstile secret key
	 *
	 * @return string The secret key.
	 */
	public function get_secret_key() {
		return get_option( self::SECRET_KEY_OPTION, '' );
	}

	/**
	 * Register the admin menu
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'CWS Turnstile Settings', 'cws-turnstile' ),
			__( 'CWS Turnstile', 'cws-turnstile' ),
			'manage_options',
			'cws-turnstile',
			array( $this, 'display_admin_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'cws_turnstile_settings',
			self::SITE_KEY_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'cws_turnstile_settings',
			self::SECRET_KEY_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'cws_turnstile_settings',
			self::THEME_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'dark',
			)
		);

		register_setting(
			'cws_turnstile_settings',
			self::SIZE_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'compact',
			)
		);

		register_setting(
			'cws_turnstile_settings',
			self::ENABLE_SEARCH_OPTION,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'cws_turnstile_settings',
			self::ENABLE_COMMENTS_OPTION,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		add_settings_section(
			'cws_turnstile_main_section',
			__( 'API Keys', 'cws-turnstile' ),
			array( $this, 'render_settings_section' ),
			'cws_turnstile_settings'
		);

		add_settings_field(
			'cws_turnstile_site_key',
			__( 'Site Key', 'cws-turnstile' ),
			array( $this, 'render_site_key_field' ),
			'cws_turnstile_settings',
			'cws_turnstile_main_section'
		);

		add_settings_field(
			'cws_turnstile_secret_key',
			__( 'Secret Key', 'cws-turnstile' ),
			array( $this, 'render_secret_key_field' ),
			'cws_turnstile_settings',
			'cws_turnstile_main_section'
		);

		add_settings_field(
			'cws_turnstile_theme',
			__( 'Widget Theme', 'cws-turnstile' ),
			array( $this, 'render_theme_field' ),
			'cws_turnstile_settings',
			'cws_turnstile_main_section'
		);

		add_settings_field(
			'cws_turnstile_size',
			__( 'Widget Size', 'cws-turnstile' ),
			array( $this, 'render_size_field' ),
			'cws_turnstile_settings',
			'cws_turnstile_main_section'
		);

		add_settings_field(
			'cws_turnstile_enable_search',
			__( 'Enable Search Form Protection', 'cws-turnstile' ),
			array( $this, 'render_enable_search_field' ),
			'cws_turnstile_settings',
			'cws_turnstile_main_section'
		);

		add_settings_field(
			'cws_turnstile_enable_comments',
			__( 'Enable Comment Form Protection', 'cws-turnstile' ),
			array( $this, 'render_enable_comments_field' ),
			'cws_turnstile_settings',
			'cws_turnstile_main_section'
		);
	}

	/**
	 * Render the settings section
	 */
	public function render_settings_section() {
		echo '<p>' . esc_html__( 'Enter your Cloudflare Turnstile API keys. You can get these from your Cloudflare dashboard.', 'cws-turnstile' ) . '</p>';
	}

	/**
	 * Render the site key field
	 */
	public function render_site_key_field() {
		$site_key = $this->get_site_key();
		?>
		<input type="text" name="<?php echo esc_attr( self::SITE_KEY_OPTION ); ?>" value="<?php echo esc_attr( $site_key ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'The site key is used to display the Turnstile widget on your site.', 'cws-turnstile' ); ?></p>
		<?php
	}

	/**
	 * Render the secret key field
	 */
	public function render_secret_key_field() {
		$secret_key = $this->get_secret_key();
		?>
		<input type="password" name="<?php echo esc_attr( self::SECRET_KEY_OPTION ); ?>" value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'The secret key is used to verify Turnstile responses.', 'cws-turnstile' ); ?></p>
		<?php
	}

	/**
	 * Render the theme field
	 */
	public function render_theme_field() {
		$theme = get_option( self::THEME_OPTION, 'dark' );
		?>
		<select name="<?php echo esc_attr( self::THEME_OPTION ); ?>">
			<option value="auto" <?php selected( $theme, 'auto' ); ?>><?php esc_html_e( 'Auto', 'cws-turnstile' ); ?></option>
			<option value="light" <?php selected( $theme, 'light' ); ?>><?php esc_html_e( 'Light', 'cws-turnstile' ); ?></option>
			<option value="dark" <?php selected( $theme, 'dark' ); ?>><?php esc_html_e( 'Dark', 'cws-turnstile' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose the Turnstile widget theme.', 'cws-turnstile' ); ?></p>
		<?php
	}

	/**
	 * Render the size field
	 */
	public function render_size_field() {
		$size = get_option( self::SIZE_OPTION, 'compact' );
		?>
		<select name="<?php echo esc_attr( self::SIZE_OPTION ); ?>">
			<option value="normal" <?php selected( $size, 'normal' ); ?>><?php esc_html_e( 'Normal', 'cws-turnstile' ); ?></option>
			<option value="compact" <?php selected( $size, 'compact' ); ?>><?php esc_html_e( 'Compact', 'cws-turnstile' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose the Turnstile widget size.', 'cws-turnstile' ); ?></p>
		<?php
	}

	/**
	 * Render the enable search field
	 */
	public function render_enable_search_field() {
		$enable_search = get_option( self::ENABLE_SEARCH_OPTION, true );
		?>
		<input type="checkbox" name="<?php echo esc_attr( self::ENABLE_SEARCH_OPTION ); ?>" value="1" <?php checked( $enable_search, true ); ?>>
		<p class="description"><?php esc_html_e( 'Enable Turnstile protection for the search form.', 'cws-turnstile' ); ?></p>
		<?php
	}

	/**
	 * Render the enable comments field
	 */
	public function render_enable_comments_field() {
		$enable_comments = get_option( self::ENABLE_COMMENTS_OPTION, false );
		?>
		<input type="checkbox" name="<?php echo esc_attr( self::ENABLE_COMMENTS_OPTION ); ?>" value="1" <?php checked( $enable_comments, true ); ?>>
		<p class="description"><?php esc_html_e( 'Enable Turnstile protection for the comment form.', 'cws-turnstile' ); ?></p>
		<?php
	}

	/**
	 * Display the admin page
	 */
	public function display_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CWS Turnstile Settings', 'cws-turnstile' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'cws_turnstile_settings' );
				do_settings_sections( 'cws_turnstile_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue Cloudflare Turnstile script conditionally.
	 */
	public function enqueue_scripts() {
		// Check if Turnstile is enabled for the search form.
		$enable_search = get_option( self::ENABLE_SEARCH_OPTION, true );
		if ( $enable_search && has_filter( 'get_search_form', array( $this, 'inject_turnstile_into_search_form' ) ) ) {
			wp_enqueue_script( 
				'cloudflare-turnstile', 
				'https://challenges.cloudflare.com/turnstile/v0/api.js', 
				array(), 
				null, 
				true 
			);
			return;
		}

		// Check if Turnstile is enabled for the comment form.
		$enable_comments = get_option( self::ENABLE_COMMENTS_OPTION, false );
		if ( $enable_comments && is_singular() && comments_open() ) {
			wp_enqueue_script( 
				'cloudflare-turnstile', 
				'https://challenges.cloudflare.com/turnstile/v0/api.js', 
				array(), 
				null, 
				true 
			);
			return;
		}
	}

    /**
     * Inject Turnstile widget into the search form.
     *
     * @param string $form The search form HTML.
     * @return string Modified search form HTML.
     */
    public function inject_turnstile_into_search_form( $form ) {
        if ( ! defined( 'TURNSTILE_SITE_KEY' ) || empty( TURNSTILE_SITE_KEY ) ) {
            return $form;
        }

        $enable_search = get_option( self::ENABLE_SEARCH_OPTION, true );
        if ( ! $enable_search ) {
            return $form;
        }

        $theme = get_option( self::THEME_OPTION, 'dark' );
        $size = get_option( self::SIZE_OPTION, 'compact' );

        $widget = '<div class="cf-turnstile" data-sitekey="' . esc_attr( TURNSTILE_SITE_KEY ) . '" data-theme="' . esc_attr( $theme ) . '" data-size="' . esc_attr( $size ) . '" data-response-field-name="cf-turnstile-response"></div>';

        // Insert before the closing form tag.
        $form = str_replace( '</form>', $widget . '</form>', $form );

        return $form;
    }

	/**
	 * Validates a Cloudflare Turnstile token.
	 *
	 * @param string $token The token to validate.
	 * @return bool True if validation succeeds, false otherwise.
	 */
	public function validate_turnstile( $token ) {
		if ( empty( $token ) ) {
			return false;
		}

		$secret_key = $this->get_secret_key();
		if ( empty( $secret_key ) ) {
			return false;
		}

		$url  = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		$data = array(
			'secret'   => $secret_key,
			'response' => $token,
			'remoteip' => ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		);

		$response = wp_remote_post(
			$url,
			array(
				'body'    => $data,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		return true === isset( $result['success'] ) && true === $result['success'];
	}

	/**
	 * Function to check Turnstile verification before search.
	 *
	 * This function checks if the Turnstile token is present in the search request.
	 * If the token is missing or invalid, it redirects the user to the home page.
	 */
	public function verify_search_turnstile() {
		// Verify nonce for search requests.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'search_nonce' ) ) {
			return;
		}

		// Only run on search requests.
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			// Check if turnstile token is present.
			if ( ! isset( $_GET['cf-turnstile-response'] ) || empty( $_GET['cf-turnstile-response'] ) ) {
				wp_safe_redirect( home_url( '/' ) );
				exit;
			}

			// Validate the token.
			$turnstile_response = isset( $_GET['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_GET['cf-turnstile-response'] ) ) : '';
			$is_valid           = $this->validate_turnstile( $turnstile_response );
			if ( ! $is_valid ) {
				wp_safe_redirect( home_url( '/' ) );
				exit;
			}
		}
	}

	/**
	 * Add Turnstile widget to the comment form.
	 *
	 * @return void
	 */
	public function add_turnstile_to_comment_form() {
		if ( ! defined( 'TURNSTILE_SITE_KEY' ) || empty( TURNSTILE_SITE_KEY ) ) {
			return;
		}

		$theme = get_option( self::THEME_OPTION, 'dark' );
		$size  = get_option( self::SIZE_OPTION, 'compact' );

		echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( TURNSTILE_SITE_KEY ) . '" data-theme="' . esc_attr( $theme ) . '" data-size="' . esc_attr( $size ) . '" data-response-field-name="cf-turnstile-response"></div>';
	}

	/**
	 * Verify Turnstile response for comment submission.
	 *
	 * @param array $commentdata The comment data.
	 * @return array The comment data.
	 */
	public function verify_comment_turnstile( $commentdata ) {
		if ( ! isset( $_POST['cf-turnstile-response'] ) || empty( $_POST['cf-turnstile-response'] ) ) {
			wp_die( esc_html__( 'Turnstile verification failed. Please try again.', 'cws-turnstile' ) );
		}

		$turnstile_response = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) );
		$is_valid           = $this->validate_turnstile( $turnstile_response );

		if ( ! $is_valid ) {
			wp_die( esc_html__( 'Turnstile verification failed. Please try again.', 'cws-turnstile' ) );
		}

		return $commentdata;
	}
}

// Initialize the plugin.
function cws_turnstile() {
	return CWS_Turnstile::get_instance();
}

// Start the plugin.
cws_turnstile();