<?php

add_action( 'post_submitbox_misc_actions', 'thfo_search_subscriber' );

function thfo_search_subscriber() {
	global $post;

	if ( $post->post_type === 'listing' ) {
		global $wpdb;
		/**
		 * get city location
		 **/

		$terms = wp_get_object_terms( $post->ID, 'location' );
		if ( ! empty( $terms ) ) {
			$city = $terms[0]->name;
		}

		/**
		 * get price from property
		 */
		$prices = get_post_meta( $post->ID, '_price' );
		if ( ! empty( $prices ) ) {
			$price = (int) $prices[0];
		}

		/**
		 * get bathroom number for property
		 * Premium Feature
		 * https://www.thivinfo.com/downloads/wpcasa-mail-alert-pro/
		 */

		$bath = get_post_meta( $post->ID, '_details_2' );

		if ( ! empty( $bath ) ) {
			$nb_bath = (int) $bath[0];
		} else {
			$nb_bath = '';
		}

		/**
		 * Get type of offer for property
		 * Premium Feature
		 * https://www.thivinfo.com/downloads/wpcasa-mail-alert-pro/
		 */

		$type = get_post_meta( $post->ID, '_price_offer' );


		/**
		 * get subcriber list for this city
		 */

		if ( ! empty( $city ) ) {
			$subscribers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpcasama_mailalert WHERE city = '$city' " );

			/**
			 * @since 1.4.0
			 * Fires after selecting subscribers
			 */
			$subscribers = apply_filters( 'thfo-get-subscriber-list', $subscribers );

			/**
			 * Search is running!
			 */

			/**
			 * Fires before searching subscribers
			 * @since 1.4.0
			 */
			do_action( 'thfo_before_search' );


			foreach ( $subscribers as $subscriber ) {

				if ( $price <= $subscriber->max_price && $price >= $subscriber->min_price ) {

					if ($nb_bath >= $subscriber->bath) {

						if ($type[0] == $subscriber->type) {


							$mail = $subscriber->email;

							thfo_send_mail( $mail );
						}
					}
				}
			}
		}
	}


}

function thfo_send_mail($mail){
	global $post;
	$recipient = $mail;
	$sender_mail = get_option('thfo_newsletter_sender_mail');
	if ( empty($sender_mail)){
		$sender_mail = get_option('admin_email');
	}

	$sender = get_option('thfo_newsletter_sender');
	$content = "";
	$object = get_option('thfo_newsletter_object');
	$img= get_option('empathy-setting-logo');
	$content .= '<img src="' . $img . '" alt="logo" /><br />';
	$content .= get_option('thfo_newsletter_content');
	$content .= '<br /><a href="'.get_permalink().'"></a><br />';
	$content .= $post->guid ."<br />";
	$content .= '<p>' . __('To unsubscribe to this mail please follow this link: ', 'wpcasa-mail-alert');
	$url = get_option('thfo_unsubscribe_page');
	$content .= esc_url(home_url($url.'?remove='.$recipient)) . '<p>';
	$content .= get_option('thfo_newsletter_footer');

	$headers[] = 'Content-Type: text/html; charset=UTF-8';

	$headers[] = 'From:'.$sender.'<'.$sender_mail.'>';

	/**
	 * @since 1.4.0
	 * Fires before sending mail
	 */

	do_action( 'thfo_before_sending_mail' );

	$result = wp_mail($recipient, $object, $content, $headers);

	/**
	 * @since 1.4.0
	 * Fires immediatly after sending mail
	 */

	do_action('thfo_after_sending_mail');

	remove_filter( 'wp_mail_content_type', 'set_html_content_type' );

}