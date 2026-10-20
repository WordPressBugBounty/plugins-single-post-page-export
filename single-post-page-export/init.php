<?php
/*
Plugin Name: Export Single Post Page
Plugin URI: https://ss88.us/plugins/single-post-page-export
Description: Export an XML file using WordPress' eXtended RSS (WXR) on a page/post basis. No need to export your entire content database anymore!
Version: 1.4
Author: SS88 LLC
Author URI: https://ss88.us
Text Domain: single-post-page-export
*/

if (!defined('ABSPATH')) exit;

class SS88_SinglePPExport {

	protected $version = '1.4';
	protected $primary_ids = [];
	protected $export_ids = [];
	protected $allowed_term_ids = [];
	protected $query_matched = false;

	public static function init() {

		$C = __CLASS__;
		new $C;

	}

	function __construct() {

		if(is_admin()) {

			add_filter('post_row_actions', [$this, 'row_actions'], 10, 2);
			add_filter('page_row_actions', [$this, 'row_actions'], 10, 2);
			add_filter('media_row_actions', [$this, 'row_actions'], 10, 2);
			add_action('current_screen', [$this, 'register_bulk_actions']);
			add_action('post_submitbox_misc_actions', [$this, 'classic_editor_links']);
			add_action('attachment_submitbox_misc_actions', [$this, 'classic_editor_links']);
			add_action('enqueue_block_editor_assets', [$this, 'block_editor_assets']);
			add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts_menu']);
			add_action('export_wp', [$this, 'export_wp'], 999);
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);

			add_action('wp_ajax_SS88_SPPE_DISMISS_NOTICE', [$this, 'ajax_dismiss_notice']);
			add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts_notice']);

		}

		add_action('admin_notices', [$this, 'show_unicornpanel_notice']);

	}

	function can_export_post($post) {

		$post = get_post($post);

		if(!$post || 'auto-draft' === $post->post_status) return false;

		$type = get_post_type_object($post->post_type);

		return $type && $type->can_export && current_user_can('export') && current_user_can('edit_post', $post->ID);

	}

	function export_url($ids, $with_media = false) {

		$args = [
			'download' => '1',
			'sppe' => '1',
			'ids' => implode(',', array_map('absint', (array) $ids)),
			'_wpnonce' => wp_create_nonce('ss88_sppe_export'),
		];

		if($with_media) $args['media'] = '1';

		return add_query_arg($args, admin_url('export.php'));

	}

	function row_actions($actions, $post) {

		if(!$this->can_export_post($post)) return $actions;

		$actions['export'] = $this->export_menu($post);

		return $actions;

	}

	function export_menu($post) {

		$url = esc_url($this->export_url($post->ID));

		// Media has nothing extra to include, so it stays a plain link
		if('attachment' === $post->post_type) {

			return '<a href="'. $url .'" title="'. esc_attr__('Export XML file', 'single-post-page-export') .'">'. esc_html__('Export', 'single-post-page-export') .'</a>';

		}

		$type = get_post_type_object($post->post_type);

		$html = '<span class="sppe-export">';
		// Without JS the toggle still works as a plain export link
		$html .= '<a href="'. $url .'" class="sppe-export-toggle" aria-haspopup="true" aria-expanded="false">'. esc_html__('Export', 'single-post-page-export') .'</a>';
		$html .= '<span class="sppe-export-menu" hidden>';
		/* translators: %s: post type singular name, e.g. Post or Page */
		$html .= '<a href="'. $url .'">'. esc_html(sprintf(__('Export %s', 'single-post-page-export'), $type->labels->singular_name)) .'</a>';
		$html .= '<a href="'. esc_url($this->export_url($post->ID, true)) .'" title="'. esc_attr__('Also includes the featured image and media used in this content', 'single-post-page-export') .'">'. esc_html__('Export with media', 'single-post-page-export') .'</a>';
		$html .= '</span></span>';

		return $html;

	}

	function admin_enqueue_scripts_menu() {

		$screen = get_current_screen();

		if(!$screen || !current_user_can('export')) return;

		// Post lists, and the classic editor's Publish box
		$is_list = 'edit' === $screen->base;
		$is_classic = 'post' === $screen->base && !(method_exists($screen, 'is_block_editor') && $screen->is_block_editor());

		if(!$is_list && !$is_classic) return;

		$type = get_post_type_object($screen->post_type);

		if(!$type || !$type->can_export || 'attachment' === $screen->post_type) return;

		wp_enqueue_style('SS88_SPPE-menu', plugin_dir_url( __FILE__ ) . 'assets/css/menu.css', ['dashicons'], $this->version);
		wp_enqueue_script('SS88_SPPE-menu', plugin_dir_url( __FILE__ ) . 'assets/js/menu.js', [], $this->version, true);

	}

	function register_bulk_actions($screen) {

		if(!current_user_can('export')) return;

		if('edit' === $screen->base) {

			$type = get_post_type_object($screen->post_type);
			if(!$type || !$type->can_export) return;

		}
		else if('upload' !== $screen->base) return;

		add_filter('bulk_actions-' . $screen->id, [$this, 'bulk_actions']);
		add_filter('handle_bulk_actions-' . $screen->id, [$this, 'handle_bulk_actions'], 10, 3);

	}

	function bulk_actions($actions) {

		$actions['ss88_sppe_export'] = __('Export', 'single-post-page-export');

		$screen = get_current_screen();

		if($screen && 'upload' !== $screen->base) {

			$actions['ss88_sppe_export_media'] = __('Export with media', 'single-post-page-export');

		}

		return $actions;

	}

	function handle_bulk_actions($redirect, $action, $ids) {

		if(!in_array($action, ['ss88_sppe_export', 'ss88_sppe_export_media'], true)) return $redirect;

		$ids = array_filter(array_map('absint', (array) $ids));

		if(empty($ids)) return $redirect;

		return $this->export_url($ids, 'ss88_sppe_export_media' === $action);

	}

	function classic_editor_links($post) {

		if(!$this->can_export_post($post)) return;

		echo '<div class="misc-pub-section misc-pub-sppe-export">';
		echo '<span class="dashicons dashicons-download" style="color:#8c8f94;margin-right:2px;" aria-hidden="true"></span> ';
		echo $this->export_menu($post); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in export_menu()
		echo '</div>';

	}

	function block_editor_assets() {

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;

		if(!$screen || 'post' !== $screen->base) return;

		$post = get_post();

		if(!$post) return;

		$type = get_post_type_object($post->post_type);

		// New posts are still auto-drafts here, the editor script asks the user to save first
		if(!$type || !$type->can_export || !current_user_can('export') || !current_user_can('edit_post', $post->ID)) return;

		wp_enqueue_script('SS88_SPPE-editor', plugin_dir_url( __FILE__ ) . 'assets/js/editor.js', ['wp-plugins', 'wp-element', 'wp-i18n', 'wp-data', 'wp-edit-post'], $this->version, true);
		wp_localize_script('SS88_SPPE-editor', 'SS88_SPPE_EditorData', [
			'exportUrl' => $this->export_url($post->ID),
			'exportMediaUrl' => $this->export_url($post->ID, true),
			/* translators: %s: post type singular name, e.g. Post or Page */
			'exportLabel' => sprintf(__('Export %s', 'single-post-page-export'), $type->labels->singular_name),
		]);

		if(function_exists('wp_set_script_translations')) {

			wp_set_script_translations('SS88_SPPE-editor', 'single-post-page-export');

		}

	}

	function export_wp($args) {

		if(!isset($_GET['sppe'])) return;

		check_admin_referer('ss88_sppe_export');

		$ids = isset($_GET['ids']) ? wp_parse_id_list(sanitize_text_field(wp_unslash($_GET['ids']))) : [];
		$ids = array_values(array_filter($ids, [$this, 'can_export_post']));

		if(empty($ids)) {

			wp_die(esc_html__('There is nothing to export. The selected content may no longer exist, or you may not have permission to export it.', 'single-post-page-export'), '', ['back_link' => true]);

		}

		$this->primary_ids = $ids;

		if(!empty($_GET['media'])) {

			$ids = array_merge($ids, $this->get_media_ids($ids));

		}

		$this->export_ids = array_values(array_unique(array_map('intval', $ids)));
		$this->allowed_term_ids = $this->get_term_ids($this->export_ids);

		add_filter('query', [$this, 'query']);
		add_filter('get_terms', [$this, 'filter_terms'], 10, 3);
		add_filter('export_wp_filename', [$this, 'export_filename'], 10, 3);
		add_action('rss2_head', [$this, 'verify_query'], 0);

	}

	function query($query) {

		global $wpdb;

		// Core's post ID snapshot: SELECT ID FROM {posts} {join} WHERE {posts}.post_type ... AND {posts}.post_status != 'auto-draft'
		// Matching the auto-draft clause too stops us hijacking a similar query run by another plugin first
		if(!preg_match('/^\s*SELECT\s+ID\s+FROM\s+`?' . preg_quote($wpdb->posts, '/') . '`?\s.*\bWHERE\b.*\bpost_type\b.*\bpost_status\s*!=\s*\'auto-draft\'/is', $query)) return $query;

		remove_filter('query', [$this, 'query']);

		$this->query_matched = true;

		$placeholders = implode(',', array_fill(0, count($this->export_ids), '%d'));

		return $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID IN ($placeholders)", $this->export_ids); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	}

	function verify_query() {

		if($this->query_matched) return;

		// Never fall back to exporting the whole site
		if(!headers_sent()) {

			header_remove('Content-Disposition');
			header_remove('Content-Description');

		}

		wp_die(esc_html__('The export was stopped because this plugin could not limit it to the selected content. Please update the Export Single Post Page plugin or contact support.', 'single-post-page-export'));

	}

	function filter_terms($terms, $taxonomies, $args) {

		// Core requests every term with 'get' => 'all' right after taking the post ID snapshot
		$is_term_list = isset($args['get']) && 'all' === $args['get'];

		if($is_term_list) $this->verify_query();

		if(!is_array($terms)) return $terms;

		$allowed = $this->allowed_term_ids;

		$terms = array_filter($terms, function($term) use ($allowed) {

			return !($term instanceof WP_Term) || isset($allowed[$term->term_id]);

		});

		// Only the term lists get sorted parent-first. Per-post lookups (wxr_post_taxonomy) must keep every term
		if(!$is_term_list) return array_values($terms);

		// Core's parent-first sort never ends if a parent is missing (e.g. an orphaned term), so drop those
		do {

			$ids = [];
			foreach($terms as $term) if($term instanceof WP_Term) $ids[$term->term_id] = true;

			$count = count($terms);
			$terms = array_filter($terms, function($term) use ($ids) {

				return !($term instanceof WP_Term) || !$term->parent || isset($ids[$term->parent]);

			});

		} while(count($terms) !== $count);

		return array_values($terms);

	}

	function get_term_ids($ids) {

		$allowed = [];

		$terms = wp_get_object_terms($ids, get_taxonomies());

		if(is_wp_error($terms)) return $allowed;

		foreach($terms as $term) {

			$allowed[$term->term_id] = true;

			// Core loops forever if a child term is exported without its parent
			if(is_taxonomy_hierarchical($term->taxonomy)) {

				foreach(get_ancestors($term->term_id, $term->taxonomy, 'taxonomy') as $ancestor) {

					$allowed[$ancestor] = true;

				}

			}

		}

		return $allowed;

	}

	function get_media_ids($ids) {

		$media = [];

		foreach($ids as $id) {

			$post = get_post($id);

			if(!$post || 'attachment' === $post->post_type) continue;

			$thumbnail = get_post_thumbnail_id($post);
			if($thumbnail) $media[] = $thumbnail;

			$media = array_merge($media, get_posts([
				'post_type' => 'attachment',
				'post_parent' => $post->ID,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields' => 'ids',
			]));

			$media = array_merge($media, $this->get_content_media_ids($post->post_content));

		}

		$media = array_unique(array_map('intval', $media));
		$media = array_filter($media, function($id) {

			return 'attachment' === get_post_type($id);

		});

		return array_values(apply_filters('ss88_sppe_media_ids', $media, $ids));

	}

	function get_content_media_ids($content) {

		$ids = [];

		if('' === trim($content)) return $ids;

		if(preg_match_all('/\bwp-image-(\d+)\b/', $content, $matches)) {

			$ids = array_merge($ids, $matches[1]);

		}

		if(preg_match_all('/\[(?:gallery|playlist)\b[^\]]*\bids\s*=\s*["\']?([\d,\s]+)/i', $content, $matches)) {

			foreach($matches[1] as $list) $ids = array_merge($ids, wp_parse_id_list($list));

		}

		if(function_exists('parse_blocks') && has_blocks($content)) {

			$ids = array_merge($ids, $this->get_block_media_ids(parse_blocks($content)));

		}

		return $ids;

	}

	function get_block_media_ids($blocks) {

		$ids = [];
		$media_blocks = ['core/image', 'core/gallery', 'core/video', 'core/audio', 'core/file', 'core/cover', 'core/media-text'];

		foreach($blocks as $block) {

			if(in_array($block['blockName'], $media_blocks, true)) {

				foreach(['id', 'mediaId'] as $key) {

					if(!empty($block['attrs'][$key])) $ids[] = absint($block['attrs'][$key]);

				}

				if(!empty($block['attrs']['ids']) && is_array($block['attrs']['ids'])) {

					$ids = array_merge($ids, array_map('absint', $block['attrs']['ids']));

				}

			}

			if(!empty($block['innerBlocks'])) {

				$ids = array_merge($ids, $this->get_block_media_ids($block['innerBlocks']));

			}

		}

		return $ids;

	}

	function export_filename($filename, $sitename, $date) {

		if(1 === count($this->primary_ids)) {

			$post = get_post($this->primary_ids[0]);
			$slug = $post->post_name ? urldecode($post->post_name) : $post->post_type . '-' . $post->ID;

			return $sitename . 'WordPress.' . sanitize_file_name($slug) . '.' . $date . '.xml';

		}

		return $sitename . 'WordPress.' . count($this->primary_ids) . '-items.' . $date . '.xml';

	}

	function plugin_action_links($actions) {

		$mylinks = [
			'<a href="https://wordpress.org/support/plugin/single-post-page-export/" target="_blank" rel="noopener noreferrer">'. esc_html__('Need help?', 'single-post-page-export') .'</a>',
		];
		return array_merge( $actions, $mylinks );

	}

	function ajax_dismiss_notice() {

		check_ajax_referer('ss88_sppe_dismiss', 'nonce');

		if(!current_user_can('manage_options')) {
			wp_send_json_error('', 403);
		}

		$Type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';

		$AllowedTypes = ['unicornpanel'];

		if(in_array($Type, $AllowedTypes, true)) {

			// Shared with other SS88 plugins so dismissing in one hides it in all
			update_option('SS88_2FAVE_notice_dismissed_' . $Type, true);

		}

		wp_send_json_success();

	}

	function show_unicornpanel_notice() {

		if(!current_user_can('manage_options')) return;

		if(get_option('SS88_2FAVE_notice_dismissed_unicornpanel')) return;

		echo '<div class="notice notice-info is-dismissible SS88_SPPE ss88-unicornpanel-notice" data-type="unicornpanel"><p>';
		echo '<span style="display:flex;gap:15px;align-items:center;">';
		echo '<img src="' . esc_url(plugin_dir_url( __FILE__ ) . 'assets/img/unicornpanel-logo.svg') . '" alt="Unicorn Panel" width="60" height="60" style="display:block;flex:0 0 auto;" />';
		echo '<span>';
		echo '<strong>' . esc_html__('We just released Unicorn Panel, our new hosting control panel built for multi-server fleets', 'single-post-page-export') . '</strong><br>';
		echo esc_html__('Provision, manage, and monitor every website, database, mailbox, and DNS zone across your entire fleet from one place. Free for 2 servers. Reselling included.', 'single-post-page-export');
		echo ' <a href="' . esc_url('https://unicornpanel.com?ref=WordPress&utm_campaign=WPdashboard_ESPP') . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn more', 'single-post-page-export') . ' &rarr;</a>';
		echo '<br><small style="opacity:0.7;">' . sprintf(
			/* translators: %s: linked plugin name */
			esc_html__('Brought to you by the %s plugin', 'single-post-page-export'),
			'<a href="' . esc_url('https://wordpress.org/plugins/single-post-page-export/') . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Export Single Post Page', 'single-post-page-export') . '</a>'
		) . '</small>';
		echo '</span></span></p></div>';

	}

	function admin_enqueue_scripts_notice() {

		if(!current_user_can('manage_options') || get_option('SS88_2FAVE_notice_dismissed_unicornpanel')) return;

		wp_enqueue_script('SS88_SPPE-notice', plugin_dir_url( __FILE__ ) . 'assets/js/notice.js', false, $this->version, true);
		wp_localize_script('SS88_SPPE-notice', 'SS88_SPPE_NoticeData', array('ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('ss88_sppe_dismiss')));

	}

}

add_action('plugins_loaded', ['SS88_SinglePPExport', 'init']);
