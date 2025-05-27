<?php
/**
 * Plugin Name: CWS Turnstile
 * Plugin URI: https://creativewebstudio.co.uk
 * Description: Integrates Cloudflare Turnstile protection for WordPress search forms
 * Version: 1.0.0
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
	const VERSION = '1.0.0';

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
	 * Enqueue Cloudflare Turnstile script
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 
			'cloudflare-turnstile', 
			'https://challenges.cloudflare.com/turnstile/v0/api.js', 
			array(), 
			self::VERSION, 
			true 
		);
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

        $widget = '<div class="cf-turnstile" data-sitekey="' . esc_attr( TURNSTILE_SITE_KEY ) . '" data-theme="dark" data-size="compact" data-response-field-name="cf-turnstile-response"></div>';

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
}

// Initialize the plugin.
function cws_turnstile() {
	return CWS_Turnstile::get_instance();
}

// Start the plugin.
cws_turnstile();