<?php
/**
 *
 Plugin Name: Replace Quick Mail Sender
 Description: Use replace_quick_mail_sender filter to replace Quick Mail name and sender. Does not work if a mail plugin defined constants for credentials.
 Version: 0.1.5
 Author: Mitchell D. Miller
 Author URI: https://wheredidmybraingo.com/
 Plugin URI: https://wheredidmybraingo.com/send-reliable-email-wordpress-quick-mail/#replace_sender
 Text Domain: quick-mail-sender
 Domain Path: /lang
 License: GPLv3
 License URI: https://www.gnu.org/licenses/lgpl.html
 */
class ReplaceQuickMailSender {
	/**
	 * Quick Mail directory.
	 *
	 * @var string
	 */
	public static $qm_dir;

	/**
	 * Full Name of Quick Mail utilities file.
	 *
	 * @var string
	 */
	public static $qm_util;

	/**
	 * Create objtect. Add actions.
	 */
	public function __construct() {
		if ( ! function_exists( 'register_activation_hook' ) ) {
			exit;
		} // end if not WordPress
		add_action( 'activated_plugin', array( $this, 'activate_plugin' ), 10, 2 );
		add_filter( 'replace_quick_mail_sender', array( $this, 'replace_quick_mail_sender' ) );
		add_action( 'wp_loaded', array( $this, 'install_replacement_values' ), 10, 0 );
		add_action( 'admin_menu', array( $this, 'init_quick_mail_sender_menu' ) );
		add_action( 'plugins_loaded', array( $this, 'init_quick_mail_translation' ) );
		add_filter( 'plugin_row_meta', array( $this, 'qm_plugin_links' ), 10, 2 );
	} // end constructor

	/**
	 * Load paths to Quick Mail Plugin, Utilities.
	 *
	 * @since 0.1.5
	 */
	public static function load_qm_paths() {
		if ( empty( self::$qm_dir ) ) {
			if ( ! class_exists( 'QuickMail' ) ) {
				deactivate_plugins( basename( __FILE__ ) );
				$text      = __( 'Requires Quick Mail plugin.', 'quick-mail-sender' );
				$html      = sprintf( "<div class='notice notice-error' role='alert'>%s</div>", $text );
				$direction = is_rtl() ? 'rtl' : 'ltr';
				$args      = array(
					'response'       => 200,
					'back_link'      => true,
					'text_direction' => $direction,
				);
				wp_die( $text, $html, $args );
			} // end if no quick mail

			$qm            = QuickMail::get_instance();
			self::$qm_dir  = empty( $qm->directory ) ? '/' : $qm->directory;
			self::$qm_util = self::$qm_dir . 'inc/class-quickmailutil.php';
			unset( $qm );
			if ( ! file_exists( self::$qm_util ) ) {
				deactivate_plugins( basename( __FILE__ ) );
				$text      = __( 'Requires Quick Mail 3.5.0 or later', 'quick-mail-sender' );
				$html      = sprintf( "<div class='notice notice-error' role='alert'>%s</div>", $text );
				$direction = is_rtl() ? 'rtl' : 'ltr';
				$args      = array(
					'response'       => 200,
					'back_link'      => true,
					'text_direction' => $direction,
				);
				wp_die( $html, $title, $args );
			} // end if
		} // end if
	} // end load_qm_paths

	/**
	 * Activate plugin.
	 *
	 * @param string  $plugin plugin name
	 * @param boolean $network activate on all network sites?
	 */
	public function activate_plugin( $plugin, $network ) {
		if ( ! strstr( $plugin, basename( __FILE__ ) ) ) {
			return;
		} // end if not Replace Quick Mail Sender.

		self::load_qm_paths();
	} // end activate_plugin

	/**
	 * Install Quick Mail filter. requires Quick Mail Plugin >= 3.5.0.
	 */
	public function install_replacement_values() {
		$uid = get_current_user_id();
		if ( $uid !== intval( get_user_meta( $uid, 'qmf_quick_mail_user', true ) ) ) {
			$qm_options = array(
				'qmf_quick_mail_user'     => $uid,
				'qmf_quick_mail_email'    => '',
				'qmf_quick_mail_name'     => '',
				'qmf_quick_mail_reply_to' => '',
			);
			foreach ( $qm_options as $k => $v ) {
				update_user_meta( $uid, $k, $v );
			} // end foreach
		} // end if not same user who installed plugin
	} // install_replacement_values

	/**
	 * load translations.
	 */
	public function init_quick_mail_translation() {
		load_plugin_textdomain( 'quick-mail-sender', false, basename( dirname( __FILE__ ) ) . '/lang' );
	} // end init_quick_mail_translation

	/**
	 * Add helpful links to plugin description. Filters plugin_row_meta.
	 *
	 * @param array  $links Plugin links.
	 * @param string $file Plugin file.
	 * @return array
	 *
	 * @since 0.1.0
	 */
	public function qm_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file === $base ) {
			$links[] = '<a href="https://github.com/mitchelldmiller/replace-quick-mail-sender" target="_blank">' . __( 'Github', 'quick-mail-sender' ) . '</a>';
		} // end if adding links
		return $links;
	} // end qm_plugin_links

	/**
	 * Init admin menu for appropriate users.
	 */
	public function init_quick_mail_sender_menu() {
		$title          = __( 'Quick Mail Sender', 'quick-mail-sender' );
		$min_permission = 'publish_posts';
		$link           = 'edit_quick_mail_sender';
		$page           = add_options_page( $title, $title, $min_permission, $link, array( $this, $link ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'init_quick_mail_style' ) );
	} // end init_quick_mail_sender_menu

	/**
	 * use by admin print styles to add Quick Mail plugin css to admin.
	 */
	public function init_quick_mail_style() {
		self::load_qm_paths();
		$file = self::$qm_dir . 'lib/css/quick-mail.css';
		$path = str_replace( $_SERVER['DOCUMENT_ROOT'], '', $file );
		wp_enqueue_style( 'quick-mail', $path, array(), null, 'all' );
	} // end init_quick_mail_style

	public function edit_quick_mail_sender() {
		$message   = '';
		$uid       = get_current_user_id();
		$saved_uid = get_user_meta( $uid, 'qmf_quick_mail_user', true );
		if ( $uid !== intval( $saved_uid ) ) {
			$text      = esc_html_e( 'You are not authorized to use this plugin.', 'quick-mail-sender' );
			$html      = sprintf( "<div class='notice notice-error' role='alert'>%s</div>", $text );
			$direction = is_rtl() ? 'rtl' : 'ltr';
			$args      = array(
				'response'       => 200,
				'back_link'      => true,
				'text_direction' => $direction,
			);
			wp_die( $text, $html, $args );
		} // end if wrong user.

		$email    = get_user_meta( $uid, 'qmf_quick_mail_email', true );
		$name     = get_user_meta( $uid, 'qmf_quick_mail_name', true );
		$reply_to = get_user_meta( $uid, 'qmf_quick_mail_reply_to', true );
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			self::load_qm_paths();
			require_once self::$qm_util;
			$uname = empty( $_POST['qmf_quick_mail_name'] ) ? '' : trim( $_POST['qmf_quick_mail_name'] );
			if ( ! empty( $uname ) && ! QuickMailUtil::check_char_count( $uname ) ) {
				$message = __( 'Invalid or missing Name', 'quick-mail-sender' );
			} // end if invalid name

			$umail         = empty( $_POST['qmf_quick_mail_email'] ) ? '' : sanitize_email( trim( $_POST['qmf_quick_mail_email'] ) );
			$verify_domain = '';
			if ( is_multisite() ) {
				$verify_domain = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
			} else {
				$verify_domain = get_option( 'verify_quick_mail_addresses', 'N' );
			} // end if multisite
			if ( empty( $message ) && ! empty( $umail ) && ! QuickMailUtil::qm_valid_email_domain( $umail, $verify_domain ) ) {
				$message = __( 'Invalid Email Address', 'quick-mail-sender' );
			} // end if invalid email

			$rmail         = empty( $_POST['qmf_quick_mail_reply_to'] ) ? '' : sanitize_email( trim( $_POST['qmf_quick_mail_reply_to'] ) );
			$verify_domain = '';
			if ( is_multisite() ) {
				$verify_domain = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
			} else {
				$verify_domain = get_option( 'verify_quick_mail_addresses', 'N' );
			} // end if multisite
			if ( empty( $message ) && ! empty( $rmail ) && ! QuickMailUtil::qm_valid_email_domain( $rmail, $verify_domain ) ) {
				$message = __( 'Invalid Email Address', 'quick-mail-sender' );
			} // end if invalid email

			if ( empty( $message ) ) {
				$updated = false;
				if ( $uname !== $name ) {
					update_user_meta( $uid, 'qmf_quick_mail_name', $uname );
					$name    = $uname;
					$updated = true;
				} // end if updated name

				if ( $umail !== $email ) {
					update_user_meta( $uid, 'qmf_quick_mail_email', $umail );
					$email   = $umail;
					$updated = true;
				} // end if updated email

				if ( empty( $rmail ) && ! empty( $reply_to ) ) {
					$reply_to = $rmail;
					update_user_meta( $uid, 'qmf_quick_mail_reply_to', $rmail );
					$updated = true;
				} else {
					if ( $rmail !== $reply_to || ( ! empty( $name ) && ! strstr( $rmail, '<' ) ) ) {
						$reply_to = empty( $rmail ) ? $reply_to : "{$name} <{$rmail}>";
					} else {
						$reply_to = $rmail;
					}
				} // end if cleared reply_to

				if ( ! empty( $rmail ) && $rmail !== $reply_to ) {
					update_user_meta( $uid, 'qmf_quick_mail_reply_to', $rmail );
					$updated = true;
				} // end if updated reply to

				if ( ( empty( $email ) || empty( $name ) ) && $updated ) {
					echo '<div role="alert" class="updated"><p>', _e( 'Filter Disabled', 'quick-mail-sender' ), '</p></div>';
				} elseif ( $updated ) {
					echo '<div role="alert" class="updated"><p>', _e( 'Option Updated', 'quick-mail-sender' ), '</p></div>';
				}
			} else {
				echo "<div class='notice notice-error' role='alert'>{$message}</div>";
			} // end if no error message
		} // end if post
		?>
<h1 id="quick-mail-title" class="quick-mail-title"><?php _e( 'Replace Quick Mail Sender', 'quick-mail-sender' ); ?></h1>
<div id="poststuff">
	<div id="post-body-content">
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<fieldset>
		<label id="te_label" for="qmf_quick_mail_name" class="recipients"><?php _e( 'Name', 'quick-mail-sender' ); ?></label>
		<p><input type="text" aria-labelledby="te_label" size="64" maxlength="80" value="<?php echo $name; ?>" name="qmf_quick_mail_name" id="qmf_quick_mail_name" tabindex="1"></p>
		<label id="tn_label" for="qmf_quick_mail_email" class="recipients"><?php _e( 'Email', 'quick-mail-sender' ); ?></label>
		<p><input type="email" aria-labelledby="tn_label" size="64" maxlength="255" value="<?php echo $email; ?>" name="qmf_quick_mail_email" id="qmf_quick_mail_email" tabindex="2"></p>
		<label id="tr_label" for="qmf_quick_mail_reply_to" class="recipients"><?php _e( 'Reply to', 'quick-mail-sender' ); ?></label>
		<p><input type="text" aria-labelledby="tr_label" size="64" maxlength="255" value="<?php echo $reply_to; ?>" name="qmf_quick_mail_reply_to" id="qmf_quick_mail_reply_to" tabindex="10"></p>
<p class="submit"><input type="submit" id="qm-submit" name="qm-submit"
title="<?php esc_html_e( 'Update', 'quick-mail-sender' ); ?>" tabindex="99"
value="<?php esc_html_e( 'Update', 'quick-mail-sender' ); ?>"></p>
		</fieldset>
		</form>
	</div>
</div>
		<?php
	} // end edit_quick_mail_sender

	/**
	 * Replace quick mail sender.
	 *
	 * @param array $args 'email', 'name', 'reply_to', 'defined'.
	 * @return array modified name, email, reply_to, defined
	 * @todo defined will be used to test mail service for constants.
	 */
	public function replace_quick_mail_sender( $args ) {
		$all_args = array(
			'email'    => '',
			'name'     => '',
			'reply_to' => '',
		);
		foreach ( $args as $k => $v ) {
			$all_args[ $k ] = $v;
		} // end foreach

		$uid = get_current_user_id();
		if ( $uid === intval( get_user_meta( $uid, 'qmf_quick_mail_user', true ) ) ) {
			$email = get_user_meta( $uid, 'qmf_quick_mail_email', true );
			$name  = get_user_meta( $uid, 'qmf_quick_mail_name', true );
			$rmail = get_user_meta( $uid, 'qmf_quick_mail_reply_to', true );
			if ( ! empty( $email ) && ! empty( $name ) ) {
				$all_args['email']    = $email;
				$all_args['name']     = $name;
				$all_args['reply_to'] = $rmail;
			} // end if got name and email
		} // end if same user is checking

		return $all_args;
	} // end replace_quick_mail_sender
} // end class
new ReplaceQuickMailSender();
