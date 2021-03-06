<?php
/*
Plugin Name: Google Chart shortcode
Plugin URI: http://wordpress.org/extend/plugins/google-chart-shortcode/
Description: This plugin allow to insert Google charts in your posts or pages, just using a shortcode.
Version: 0.1.0
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: 
Domain Path: 

Generated by WordPress ShortCode Plugin Builder (http://dogmap.jp/shortcoder/)

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2010

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
if (!class_exists('ShortCodeModel')) :
class ShortCodeModel {
	var $file_path;
	var $plugins_dir, $plugin_dir, $plugin_file, $plugin_url;
	var $textdomain_name;
	var $admin_option, $admin_action, $admin_hook;
	var $charset;

	// initialize
	function init( $file ) {
		global $wp_version;

		$this->charset = get_option('blog_charset');

		$this->set_plugin_dir($file);
		$this->load_textdomain('');

		$this->admin_option    = $this->plugin_file;
		$this->admin_action    =
			  trailingslashit(get_bloginfo('wpurl')) . 'wp-admin/'
			. (version_compare($wp_version, "2.7", ">=") ? 'options-general.php' : 'admin.php')
			. '?page=' . $this->admin_option;
		$this->admin_hook      = array();
	}

	function set_plugin_dir( $file ) {
		$this->file_path = $file;
		$this->plugins_dir = trailingslashit(defined('PLUGINDIR') ? PLUGINDIR : 'wp-content/plugins');
		$filename = explode("/", $this->file_path);
		if(count($filename) <= 1) $filename = explode("\\", $this->file_path);
		$this->plugin_dir  = $filename[count($filename) - 2];
		$this->plugin_file = $filename[count($filename) - 1];
		$this->plugin_url  = $this->plugin_url($this->plugin_dir);
		unset($filename);
	}

	function load_textdomain( $sub_dir = '' ) {
		global $wp_version;

		$this->textdomain_name = $this->plugin_dir;
		$abs_plugin_dir = $this->plugin_dir($this->plugin_dir);
		$sub_dir = (!empty($sub_dir)
			? preg_replace('/^\//', '', $sub_dir)
			: (file_exists($abs_plugin_dir.'languages') ? 'languages' : (file_exists($abs_plugin_dir.'language') ? 'language' : (file_exists($abs_plugin_dir.'lang') ? 'lang' : '')))
			);
		$textdomain_dir = trailingslashit(trailingslashit($this->plugin_dir) . $sub_dir);

		if (version_compare($wp_version, "2.6", ">=") && defined('WP_PLUGIN_DIR'))
			load_plugin_textdomain($this->textdomain_name, false, $textdomain_dir);
		else
			load_plugin_textdomain($this->textdomain_name, $this->plugins_dir . $textdomain_dir);
	}

	// have short code?
	function have_shortcode( $short_codes = "" ) {
		if (is_admin()) return FALSE;

		global $wp_query;

		$pattern = '';
		foreach ( (array) $short_codes as $val) {
			$pattern .= (!empty($pattern) ? '|' : '') . strtolower($val);
		}
		$pattern = '/\[(' . $pattern . ')[^\]]*\]/im';
		$found = array();
		foreach($wp_query->posts as $key => $post) {
			$post_content = isset($post->post_content) ? $post->post_content : '';
			if (!empty($post_content) && preg_match_all($pattern, $post_content, $matches, PREG_SET_ORDER)) {
				foreach ((array) $matches as $match) {
					$found[$match[1]] = true;
				}
				unset($match);
			}
			unset($matches);
		}

		return (count($found) > 0 ? $found : FALSE);
	}

	// Get Text
	function get_text( $text ) {
		return __($text, $this->textdomain_name);
	}

	// Add Admin Option Page
	function add_option_page( $page_title, $function, $capability = 9, $menu_title = '', $file = '' ) {
		if ($menu_title == '') $menu_title = $page_title;
		if ($file == '') $file = $this->plugin_file;
		$this->admin_hook['option'] = add_options_page($page_title, $menu_title, $capability, $file, $function);
	}

	function add_management_page( $page_title, $function, $capability = 9, $menu_title = '', $file = '' ) {
		if ($menu_title == '') $menu_title = $page_title;
		if ($file == '') $file = $this->plugin_file;
		$this->admin_hook['management'] = add_management_page($page_title, $menu_title, $capability, $file, $function);
	}

	function add_theme_page( $page_title, $function, $capability = 9, $menu_title = '', $file = '' ) {
		if ($menu_title == '') $menu_title = $page_title;
		if ($file == '') $file = $this->plugin_file;
		$this->admin_hook['theme'] = add_theme_page($page_title, $menu_title, $capability, $file, $function);
	}

	function add_submenu_page( $parent, $page_title, $function, $capability = 9, $menu_title = '', $file = '' ) {
		if ($menu_title == '') $menu_title = $page_title;
		if ($file == '') $file = $this->plugin_file;
		$this->admin_hook[$parent] = add_submenu_page($parent, $page_title, $menu_title, $capability, $file, $function);
	}

	function add_media_page( $page_title, $function, $capability = 9, $menu_title = '', $file = '' ) {
		$this->add_submenu_page(($this->wp27 ? 'upload.php' : 'edit.php'), $page_title, $function, $capability, $menu_title, $file);
	}

	function add_edit_page( $page_title, $function, $capability = 9, $menu_title = '', $file = '' ) {
		$this->add_submenu_page('edit.php', $page_title, $function, $capability, $menu_title, $file);
	}

	function add_plugin_setting_links( $links, $file ) {
		$this_plugin = plugin_basename($this->file_path);
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}
		return $links;
	}

	// This Plugin active?
	function is_active( $file = '' ) {
		$is_active = false;
		if ($file == '')
			$file = $this->plugin_file;
		foreach ((array) get_option('active_plugins') as $val) {
			if (preg_match('/'.preg_quote($file).'/i', $val)) {
				$is_active = true;
				break;
			}
		}
		return $is_active;
	}

	// Get WP_CONTENT_DIR
	function content_dir( $path = '' ) {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: trailingslashit(ABSPATH) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// Get WP_CONTENT_URL
	function content_url( $path = '' ) {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_URL')
			? WP_CONTENT_URL
			: trailingslashit(get_option('siteurl')) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// Get WP_PLUGIN_DIR
	function plugin_dir( $path = '' ) {
		return trailingslashit($this->content_dir( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// Get WP_PLUGIN_URL
	function plugin_url( $path = '' ) {
		return trailingslashit($this->content_url( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

}
endif;

//** Your custom code starts here **************************************//
class Chart_Controller extends ShortCodeModel {
	var $plugin_ver  = '0.1.0';
	var $plugin_name = 'Google Chart shortcode';

	// Constructor
	function Chart_Controller() {
		$this->__construct();
	}
	function __construct() {
		add_shortcode('chart', array(&$this, 'Shortcode_Handler'));
		$this->init(__FILE__);
	}

	function Shortcode_Handler($atts, $content = '') {
//		extract( shortcode_atts( array(
//			'cht' => '',
//			'chs' => '',
//			'chd' => '',
//			'chtt' => '',
//			'chdl' => '',
//			'chco' => '',
//			'chf' => '',
//			'chxt' => '',
//			'chg' => '',
//			'chm' => '',
//			'chls' => '',
//			'chbh' => '',
//			'chl' => '',
//			), $atts) );

		$plugin_ver  = $this->plugin_ver;
		$plugin_name = $this->plugin_name;
		$plugin_url  = $this->plugin_url;
		$file_path   = $this->file_path;
		$charset     = $this->charset;

//** Your code *********************************************************//

		$gc_url = 'http://chart.apis.google.com/chart';
		$query = array();
		foreach ( $atts as $key => $val ) {
			switch(strtolower($key)){
			case 'cht' :
			case 'chs' :
			case 'chd' :
			case 'chtt':
			case 'chdl':
			case 'chco':
			case 'chf' :
			case 'chxt':
			case 'chg' :
			case 'chm' :
			case 'chls':
			case 'chbh':
			case 'chl' :
				if (!empty($val)) {
//					$query[] = strtolower($key).'='.urlencode($val);
					$query[] = strtolower($key).'='.$val;
				}
				break;
			}
		}

		$return_text = (
			count($query) > 0
			? "<img src=\"{$gc_url}?".implode('&amp;',$query)."\" alt=\"{$content}\" />"
			: $content
			);

//** End of Your code **************************************************//

		return $return_text;
	}
}
//** Your custom code ends here ****************************************//


// This registers the shortcode.
new Chart_Controller();
?>