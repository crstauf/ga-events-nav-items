<?php
/**
Plugin Name: Google Analytics Event Tracking Navigation Items
Description: Add checkbox to nav menu items to track with Google Analytics Event Tracking (__gaTracker)
     Author: Caleb Stauffer Style, LLC
 Author URI: http://develop.calebstauffer.com
    Version: 0.0.1
**/

new cssllc_ga_events_nav_items;

class cssllc_ga_events_nav_items {

	function __construct() {
		add_filter('manage_nav-menus_columns',	array(__CLASS__,'filter_manage_nav_menus_columns'),11);
		add_filter('wp_edit_nav_menu_walker',	array(__CLASS__,'filter_wp_edit_nav_menu_walker'));
		add_action('wp_update_nav_menu',		array(__CLASS__,'action_wp_update_nav_menu'));
		add_filter('nav_menu_link_attributes',	array(__CLASS__,'filter_nav_menu_link_attributes'),10,4);
	}

	function filter_manage_nav_menus_columns($cols) {
		return array_merge($cols,array('link-event-track' => 'GA Event Tracking'));
	}

	function filter_wp_edit_nav_menu_walker($walker) { return 'CSSLLC_GA_Events_Walker_Nav_Menu_Edit'; }

	function action_wp_update_nav_menu($nav_menu_selected_id) {

		if ( ! empty( $_POST['menu-item-db-id'] ) ) {
			foreach( (array) $_POST['menu-item-db-id'] as $_key => $k ) {
				if ( ! isset( $_POST['menu-item-title'][ $_key ] ) || '' == $_POST['menu-item-title'][ $_key ] )
					continue;
				if (isset($_POST['menu-item-event-track'][$_key]) && '' !== $_POST['menu-item-event-track'][$_key])
					update_post_meta( $_key, '_menu_item_event_track', true );
				else
					delete_post_meta( $_key, '_menu_item_event_track' );
			}
		}

	}

	static function filter_nav_menu_link_attributes($atts,$item,$args,$depth) {
		$track = get_post_meta(esc_attr($item->ID),'_menu_item_event_track',true);
		if (false === $track) return $atts;
		return array_merge($atts,array('onclick' => "__gaTracker('send','event','Nav','click','" . $args->menu->name . "/" . $item->post_title . "',1)"));
	}

}

if (!class_exists('Walker_Nav_Menu_Edit')) {
	if (file_exists(ABSPATH . 'wp-admin/includes/class-walker-nav-menu-edit.php'))
		require_once( ABSPATH . 'wp-admin/includes/class-walker-nav-menu-edit.php' );
	else
		require_once( ABSPATH . 'wp-admin/includes/nav-menu.php' );
}

class CSSLLC_GA_Events_Walker_Nav_Menu_Edit extends Walker_Nav_Menu_Edit {

	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		parent::start_el($output,$item,$depth,$args,$id);

		$item_id = esc_attr( $item->ID );
		if (false !== strpos($output,'edit-menu-item-event-track-' . $item_id)) return;

		$search = '<p class="field-css-classes description description-thin">';
		$track = get_post_meta($item_id,'_menu_item_event_track',true);

		$replace = '<p class="field-link-event-track description">
			<label for="edit-menu-item-event-track-' . $item_id . '">
				<input type="checkbox" id="edit-menu-item-event-track-' . $item_id . '" value="1" name="menu-item-event-track[' . $item_id . ']"' . checked($track,true,false) . '/>
				' . __( 'Track clicks via GA event tracking' ) . '
			</label>
		</p>';

		$output = str_replace($search,$replace . str_replace('description-thin','description-thin added-onclick-field',$search),$output);
	}

}
