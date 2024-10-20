<?php
/*
Plugin Name: Export Single Post Page
Plugin URI: https://ss88.us/plugins/single-post-page-export
Description: Export an XML file using WordPress' eXtended RSS (WXR) on a page/post basis. No need to export your entire content database anymore!
Version: 1.3
Author: SS88 LLC
Author URI: https://ss88.us
*/

if (!defined('ABSPATH')) exit;

class SS88_SinglePPExport {

    public static function init() {

        $C = __CLASS__;
        new $C;

    }

    function __construct() {

		if(is_admin()) {

			add_filter('post_row_actions', [$this, 'row_actions'], 10, 2);
			add_filter('page_row_actions', [$this, 'row_actions'], 10, 2);
			add_filter('media_row_actions', [$this, 'row_actions'], 10, 2);
			add_filter('query', [$this, 'query']);

		}

    }
	
	function query($query) {
		
		if (!isset($_GET['sppe'])) return $query;
		
		global $wpdb;
		
		if(strstr($query, 'SELECT ID FROM '. $wpdb->posts .'  WHERE '. $wpdb->posts .'.post_type IN')) {
			
			$split = explode('WHERE', $query);
			$split[1] = $wpdb->prepare(" {$wpdb->posts}.ID = %d", intval($_GET['id']));
			$query = implode('WHERE', $split);

			return $query;
			
		}
		
		if(strstr($query, 'SELECT t.*, tt.* FROM '. $wpdb->terms .' AS t INNER JOIN ' . $wpdb->term_taxonomy)) {

			$split = explode('WHERE', $query);
			$query = $split[0] . ' INNER JOIN '. $wpdb->term_relationships .' AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id' . ' WHERE ' . $split[1];
			$query .= $wpdb->prepare(" AND tr.object_id = %d", intval($_GET['id']));

			return $query;
			
		}
		
		return $query;
		
	}

	function row_actions($actions, $post) {
		
		$URL = add_query_arg([
			'download' => '',
			'sppe' => '',
			'id' => $post->ID,
			'rand' => time(),
		], admin_url('export.php'));

		$actions['export'] = '<a href="'. $URL .'" target="_blank" title="'. __('Export XML file', 'single-post-page-export') .'">'. __('Export', 'single-post-page-export') .'</a>'; 

		return $actions;

	}

    function plugin_action_links($actions) {

        $mylinks = [
            '<a href="https://wordpress.org/support/plugin/single-post-page-export/" target="_blank">'. __('Need help?', 'single-post-page-export') .'</a>',
        ];
        return array_merge( $actions, $mylinks );

    }

	function debug($msg) {

		error_log("\n" . '[' . date('Y-m-d H:i:s') . '] ' .  $msg, 3, plugin_dir_path(__FILE__) . 'debug.log');

	}

}

add_action('plugins_loaded', ['SS88_SinglePPExport', 'init']);