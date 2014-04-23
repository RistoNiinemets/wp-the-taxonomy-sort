<?php
defined( 'ABSPATH' ) OR exit;

/*
	Plugin Name: The Taxonomy Sort
	Plugin URI: http://risto.niinemets.eu
	Description: Allows you to easily change the order of different taxonomies
	Version: 1.0
	Author: Risto Niinemets
	Author URI: http://risto.niinemets.eu
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class TheTaxonomySort {

	var
		// used to store taxonomy order for sorting function object_sort_by_current_taxonomy_order
		$current_taxonomy_order,

		// defined in after_setup_theme. allows user to use filter "the_taxonomy_sort_define_taxonomies"
		$enabled_taxonomies = array(),

		// used to determine if the current taxonomy is sortable for JS & CSS initializations
		$current_taxonomy_sortable = true;


	/**
	 * Simple class constructor
	 */
	function __construct() {
		// admin initialize
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// front-end initialize
		add_action( 'init', array( $this, 'init' ) );

		// set up enabled taxonomies defined in theme
		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );
	}

	/**
	 * Initialize administration
	 *
	 * @return void
	 */
	function admin_init() {

		// load scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// load inline CSS style
		add_action( 'admin_print_styles', array( $this, 'print_styles' ) );

		// ajax to save the sorting
		add_action( 'wp_ajax_get_inline_boxes', array( $this, 'inline_edit_boxes' ) );

		// reorder terms when someone tries to get terms
		add_filter( 'get_terms', array( $this, 'reorder_terms' ) );
	}

	/**
	 * Initialize front-page
	 *
	 * @return void
	 */
	function init() {
		// reorder terms when someone tries to get terms
		add_filter( 'get_terms', array( $this, 'reorder_terms' ) );
	}

	/**
	 * Appply the_taxonomy_sort_define_taxonomies filter to restrict taxonimies as defined in theme.
	 *
	 * @return void
	 */
	function after_setup_theme() {
		$enabled_taxonomies = apply_filters('the_taxonomy_sort_define_taxonomies', '');
		
		if ( ! empty ($enabled_taxonomies) ) {
			$this->enabled_taxonomies = $enabled_taxonomies;
		}
	}

	/**
	 * Load scripts
	 *
	 * @return void
	 */
	function enqueue_scripts() {
		// only scripts if current taxonomy is sortable.
		if ( ! $this->_is_taxonomy_enabled( get_current_screen()->taxonomy )) {
			return false;
		}

		// allow enqueue only on tags/taxonomy page
		if ( get_current_screen()->base != 'edit-tags' ) return;

		// load jquery and plugin's script
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'thetaxonomysort', plugins_url( 'the-taxonomy-sort.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable' ) );
	}

	/**
	 * Print styles
	 *
	 * @return void
	 */
	function print_styles() {
		// only enable
		if ( ! $this->_is_taxonomy_enabled( get_current_screen()->taxonomy )) {
			return false;
		}

		echo '<style>.wp-list-table.tags td { cursor: move; }</style>';
		
	}

	/**
	 * Save sort order
	 *
	 * @return void
	 */
	function inline_edit_boxes() {
		// make sure we received correct post data
		if (empty($_POST['rows']) || empty($_POST['taxonomy_name'])) {
			die(-1);
		}

		// make sure user can manage categories
		if ( ! current_user_can( 'manage_categories' )) {
			die(-1);
		}

		$rows = $_POST['rows'];
		$taxonomy_name = $_POST['taxonomy_name'];

		update_option('thets_' . $taxonomy_name, $rows);

		// kill it for ajax
		exit;
	}

	/**
	 * Order terms
	 *
	 * @param object  $objects All the objects that need sorting
	 * @return object          Sorted objects
	 */
	function reorder_terms( $objects ) {

		// we do not need empty objects
		if( empty( $objects ) ) return $objects;

		$taxonomy_name = $objects[0]->taxonomy;

		// check if taxonomy is sortable
		if ( count($this->enabled_taxonomies) == 0 ) {
			$this->current_taxonomy_sortable = true;
		} elseif ( in_array( $taxonomy_name, $this->enabled_taxonomies) ) {
			$this->current_taxonomy_sortable = true;
		} else {
			$this->current_taxonomy_sortable = false;
			return $objects;
		}

		// get saved order
		$taxonomy_order = get_option('thets_' . $taxonomy_name);

		// no sort order is saved
		if ( $taxonomy_order == FALSE) {
			return $objects;
		}

		// store ordering for usage in object_sort function
		$this->current_taxonomy_order = $taxonomy_order;

		usort( $objects , array($this, '_object_sort_by_current_taxonomy_order') );

		return $objects;
	}

	/**
	 * Sorting function to be used with usort
	 */
	private function _object_sort_by_current_taxonomy_order($a, $b) {
	   $pos1 = array_search ( $a->term_id , $this->current_taxonomy_order);
	   $pos2 = array_search ( $b->term_id , $this->current_taxonomy_order);

	   if ($pos1==$pos2)
	       return 0;
	   else
	      return ($pos1 < $pos2 ? -1 : 1);

	}

	/**
	 * Determine if taxonomy is defined to be sortable
	 *
	 * @param string $taxonomy_name The name of the taxonomy
	 * @return bool
	 */
	private function _is_taxonomy_enabled( $taxonomy_name ) {
		return empty($this->enabled_taxonomies) || in_array($taxonomy_name, $this->enabled_taxonomies);
	}

}

/**
 * Uninstall the plugin
 * 
 * @return void
 */
function thets_uninstall_plugin() {
	// check if user has permissions
	if ( ! current_user_can( 'activate_plugins' ) ) return;
}
register_uninstall_hook( __FILE__, 'thets_uninstall_plugin' );

// Start our plugin
new TheTaxonomySort;
