<?php

/**
 * Class Forminator_Addon_Settings_Abstract
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * @since 1.1
 */
abstract class Forminator_Addon_Settings_Abstract {


	/**
	 * Get HTML Email Lists select-options
	 *
	 * @param object $mail_lists All email lists.
	 * @param string $selected_value Saved value.
	 * @return string
	 */
	public static function email_lists_options( $mail_lists, $selected_value = '' ) {
		$html = '<option value="">' . __( 'None', 'forminator' ) . '</option>';

		foreach ( $mail_lists as $mail_list ) {
			$html .= '<option value="' . esc_attr( $mail_list->id ) . '" ' . selected(
				$selected_value,
				$mail_list->id,
				false
			) . '>' . esc_html( $mail_list->name ) . '</option>';
		}

		return $html;
	}

	/**
	 * Get HTML for refresh button
	 *
	 * @return string
	 */
	public static function refresh_button() {
		$html = '<button class="sui-button-icon sui-tooltip forminator-refresh-email-lists" data-tooltip="'
				. esc_html__( 'Refresh list', 'forminator' ) . '" type="button">'
				. '<span class="sui-loading-text" aria-hidden="true">'
				. '<i class="sui-icon-refresh"></i>'
				. '</span>'
				. '<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>'
				. '<span class="sui-screen-reader-text">' . esc_html__( 'Refresh', 'forminator' ) . '</span>'
				. '</button>';

		return $html;
	}
}
