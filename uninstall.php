<?php
/**
 * Uninstall Replace Quick Mail Sender. Remove options.
 *
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_metadata( 'user', 1, 'qmf_quick_mail_name', '', true );
delete_metadata( 'user', 1, 'qmf_quick_mail_email', '', true );
delete_metadata( 'user', 1, 'qmf_quick_mail_reply_to', '', true );
