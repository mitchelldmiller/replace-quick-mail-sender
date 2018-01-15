<?php
/**
 Plugin Name:  Replace Quick Mail Sender
 Description:  Use replace_quick_mail_sender filter to replace Quick Mail name and sender. Does not work if another mail plugin is overriding credentials.
 Version:      0.1.0
 Author:       Mitchell D. Miller
 Author URI:   https://wheredidmybraingo.com/
 Plugin URI:   https://wheredidmybraingo.com/send-reliable-email-wordpress-quick-mail/#replace_sender
 Text Domain:  quick-mail-sender
 Domain Path:  /lang
 License:      GPLv3
 License URI:  https://www.gnu.org/licenses/lgpl.html
 */

class ReplaceQuickMailSender {
	public static $qm_dir;

	public function __construct() {
		if ( ! function_exists( 'register_activation_hook' ) ) {
			exit;
		} // end if not WordPress

		add_filter( 'replace_quick_mail_sender', array($this, 'replace_quick_mail_sender') );
		add_action( 'activated_plugin', array($this, 'install_quick_mail_filters'), 10, 0);
		add_action( 'admin_menu', array($this, 'init_quick_mail_sender_menu') );
		add_action( 'plugins_loaded', array($this, 'init_quick_mail_translation') );
		add_filter( 'plugin_row_meta', array($this, 'qm_plugin_links'), 10, 2 );
	} // end constructor

	/**
	 * install Quick Mail filter. requires Quick Mail Plugin >= 3.2.0
	 *
	 */
	public function install_quick_mail_filters() {
		if ( !class_exists( 'QuickMail' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			echo sprintf("<div class='notice notice-error' role='alert'>%s</div>",
					__( 'Requires Quick Mail plugin.', 'quick-mail-sender' ) );
			exit;
		} // end if no quick mail

		$qm = QuickMail::get_instance();
		self::$qm_dir = empty( $qm->directory ) ? '/' : $qm->directory;
		$want = self::$qm_dir . 'inc/qm_util.php';
		unset( $qm );
		if ( !file_exists( $want ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			echo sprintf("<div class='notice notice-error' role='alert'>%s</div>",
					__( 'Requires Quick Mail 3.2.0 or later', 'quick-mail-sender' ) );
			exit;
		} // end if

		$uid = get_current_user_id();
		if ( $uid != get_user_meta( $uid, 'qmf_quick_mail_user', true ) ) {
			$qm_options = array('qmf_quick_mail_user' => $uid, 'qmf_quick_mail_email' => '', 'qmf_quick_mail_name' => '', 'qmf_quick_mail_reply_to' => '');
			foreach ($qm_options as $k => $v) {
				update_user_meta( $uid, $k, $v );
			} // end foreach
		} // end if not same user who installed plugin
	} // install_quick_mail_filters

	/**
	 * load translations.
	 */
	public function init_quick_mail_translation() {
		load_plugin_textdomain( 'quick-mail-sender', false, basename( dirname( __FILE__ ) ) . '/lang' );
	} // end init_quick_mail_translation

	/**
	 * add helpful links to plugin description. filters plugin_row_meta.
	 *
	 * @param array $links
	 * @param string $file
	 * @return array
	 *
	 * @since 0.1.0
	 */
	public function qm_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			$links[] = '<a href="https://github.com/mitchelldmiller/replace-quick-mail-sender" target="_blank">' . __( 'Github', 'quick-mail-sender' ) . '</a>';
		} // end if adding links
		return $links;
	} // end qm_plugin_links

	/**
	 * init admin menu for appropriate users.
	 */
	public function init_quick_mail_sender_menu() {
		$title = __( 'Quick Mail Sender', 'quick-mail-sender' );
		$min_permission = 'activate_plugins';
		$link = 'edit_quick_mail_sender';
		$page = add_options_page( $title, $title, $min_permission, $link, array($this, $link) );
		add_action( 'admin_print_styles-' . $page, array($this, 'init_quick_mail_style') );
	} // end init_quick_mail_sender_menu

	/**
	 * use by admin print styles to add Quick Mail plugin css to admin.
	 */
	public function init_quick_mail_style() {
		if ( !class_exists( 'QuickMail' ) ) {
			echo sprintf("<div class='notice notice-error' role='alert'>%s</div>",
					__( 'Requires Quick Mail plugin.', 'quick-mail-sender' ) );
			exit;
		} // end if
		if ( empty( self::$qm_dir ) ) {
			$qm = QuickMail::get_instance();
			self::$qm_dir = empty( $qm->directory ) ? '/' : $qm->directory;
			unset( $qm );
		} // end if lost value
		$file = self::$qm_dir . 'lib/css/quick-mail.css';
		$path = str_replace( $_SERVER['DOCUMENT_ROOT'], '', $file );
		wp_enqueue_style( 'quick-mail', $path, array(), null, 'all' );
	} // end init_quick_mail_style

	public function edit_quick_mail_sender() {
		$message = '';
		$uid = get_current_user_id();
		$showform = ( $uid == get_user_meta( $uid, 'qmf_quick_mail_user', true ) );
		$email = $showform ? get_user_meta( $uid, 'qmf_quick_mail_email', true ) : '';
		$name = $showform ? get_user_meta( $uid, 'qmf_quick_mail_name', true ) : '';
		$reply_to = $showform ? get_user_meta( $uid, 'qmf_quick_mail_reply_to', true ) : '';

		if ( $showform && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			if ( empty( self::$qm_dir ) ) {
				$qm = QuickMail::get_instance();
				self::$qm_dir = empty( $qm->directory ) ? '/' : $qm->directory;
				unset( $qm );
			} // end if lost value

			require_once self::$qm_dir . 'inc/qm_util.php';
			$uname = empty($_POST['qmf_quick_mail_name']) ? '' : trim( $_POST['qmf_quick_mail_name'] );
			if ( !empty( $uname ) && !QuickMailUtil::check_char_count( $uname ) ) {
				$message = __( 'Invalid or missing Name', 'quick-mail-sender' );
			} // end if invalid name

			$umail = empty($_POST['qmf_quick_mail_email']) ? '' : sanitize_email( trim( $_POST['qmf_quick_mail_email'] ) );
			$verify_domain = '';
			if ( is_multisite() ) {
				$verify_domain = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
			} else {
				$verify_domain = get_option( 'verify_quick_mail_addresses', 'N' );
			} // end if multisite
			if ( empty( $message ) && !empty( $umail ) && !QuickMailUtil::qm_valid_email_domain( $umail, $verify_domain ) ) {
				$message = __( 'Invalid Email Address', 'quick-mail-sender' );
			} // end if invalid email

			$rmail = empty($_POST['qmf_quick_mail_reply_to']) ? '' : sanitize_email( trim( $_POST['qmf_quick_mail_reply_to'] ) );
			$verify_domain = '';
			if ( is_multisite() ) {
				$verify_domain = get_blog_option( get_current_blog_id(), 'verify_quick_mail_addresses', 'N' );
			} else {
				$verify_domain = get_option( 'verify_quick_mail_addresses', 'N' );
			} // end if multisite
			if ( empty( $message ) && !empty( $umail ) && !QuickMailUtil::qm_valid_email_domain( $rmail, $verify_domain ) ) {
				$message = __( 'Invalid Email Address', 'quick-mail-sender' );
			} // end if invalid email

			if ( empty( $message) ) {
				$updated = false;
				if ( $uname != $name ) {
					update_user_meta( $uid, 'qmf_quick_mail_name', $uname );
					$name = $uname;
					$updated = true;
				} // end if updated name

				if ( $umail != $email ) {
					update_user_meta( $uid, 'qmf_quick_mail_email', $umail );
					$email = $umail;
					$updated = true;
				} // end if updated email

				if ( $rmail != $reply_to ) {
					update_user_meta( $uid, 'qmf_quick_mail_reply_to', $rmail );
					$reply_to = $rmail;
					$updated = true;
				} // end if updated reply to

				if ( ( empty( $email ) || empty( $name ) ) && $updated) {
					echo '<div role="alert" class="updated"><p>', _e( 'Filter Disabled', 'quick-mail-sender' ), '</p></div>';
				} elseif ($updated) {
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
		<?php if ( $showform ) : ?>
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<fieldset>
		<label id="te_label" for="qmf_quick_mail_name" class="recipients"><?php _e( 'Name', 'quick-mail-sender' ); ?></label>
		<p><input type="text" aria-labelledby="te_label" size="64" maxlength="80" value="<?php echo $name; ?>" name="qmf_quick_mail_name" id="qmf_quick_mail_name" tabindex="1"></p>
		<label id="tn_label" for="qmf_quick_mail_email" class="recipients"><?php _e( 'Email', 'quick-mail-sender' ); ?></label>
		<p><input type="email" aria-labelledby="tn_label" size="64" maxlength="255" value="<?php echo $email; ?>" name="qmf_quick_mail_email" id="qmf_quick_mail_email" tabindex="2"></p>
		<label id="tr_label" for="qmf_quick_mail_reply_to" class="recipients"><?php _e( 'Reply to', 'quick-mail-sender' ); ?></label>
		<p><input type="email" aria-labelledby="tr_label" size="64" maxlength="255" value="<?php echo $reply_to; ?>" name="qmf_quick_mail_reply_to" id="qmf_quick_mail_reply_to" tabindex="10"></p>
<p class="submit"><input type="submit" id="qm-submit" name="qm-submit"
title="<?php _e( 'Update', 'quick-mail-sender' ); ?>" tabindex="99"
value="<?php _e( 'Update', 'quick-mail-sender' ); ?>"></p>
		</fieldset>
		</form>
		<?php else : ?>
		<p><?php _e( 'You are not authorized to use this plugin.', 'quick-mail-sender' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
	} // end edit_quick_mail_sender

	/**
	 * replace quick mail sender.
	 * @param array $args $args['email'] = email, $args['name'] = name
	 * @return array modified name, email, reply_to
	 */
	public function replace_quick_mail_sender( $args ) {
		$uid = get_current_user_id();
		if ($uid == get_user_meta( $uid, 'qmf_quick_mail_user', true ) ) {
			$email = get_user_meta( $uid, 'qmf_quick_mail_email', true );
			$name = get_user_meta($uid, 'qmf_quick_mail_name', true );
			$rmail = get_user_meta( $uid, 'qmf_quick_mail_reply_to', true );
			if ( !empty( $email ) && !empty( $name ) ) {
				return array('email' => $email, 'name' => $name, 'reply_to' => $rmail);
			} // end if got name and email
		} // end if same user is checking

		return $args;
	} // end replace_quick_mail_sender
} // end class
new ReplaceQuickMailSender;
