<?php
namespace Codexpert\Plugin;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Feature
 * @author Codexpert <hi@codexpert.io>
 */
class Feature extends Base {
	
	public $slug;
	
	public $name;

	public $featured_plugins;

	public function __construct( $plugin ) {

		$this->plugin 	= $plugin;

		$this->server 	= $this->plugin['server'];
		$this->slug 	= $this->plugin['TextDomain'];
		$this->name 	= $this->plugin['Name'];

		$this->featured_plugins = [
			'restrict-elementor-widgets',
			'image-sizes',
			'image-sizes',
			'search-logger',
			'wc-affiliate',
			'woolementor',
		];

		$this->hooks();
	}

	public function hooks() {
		$this->filter( 'plugins_api_result', 'alter_api_result', 10, 3 );
	}

	/**
	 * Alter API result
	 */
	public function alter_api_result( $res, $action, $args ) {

		if( isset( $_GET['tab'] ) && $_GET['tab'] != 'featured' ) return $res;

		remove_filter( 'plugins_api_result', [ $this, 'alter_api_result' ] );

		foreach ( $this->featured_plugins as $featured_plugin ) {
			$res = $this->add_to_list( $featured_plugin, $res );
		}

		return $res;
	}

	/**
	 * Add a plugin to the fav list
	 */
	public function add_to_list( $plugin_slug, $res ) {
		if ( ! empty( $res->plugins ) && is_array( $res->plugins ) ) {
			foreach ( $res->plugins as $plugin ) {
				if ( is_object( $plugin ) && ! empty( $plugin->slug ) && $plugin->slug == $plugin_slug ) {
					return $res;
				}
			}
		}

		if ( $plugin_info = get_transient( 'cx-plugin-info-' . $plugin_slug ) ) {
			array_unshift( $res->plugins, $plugin_info );
		}
		else {
			$plugin_info = plugins_api( 'plugin_information', array(
				'slug'   => $plugin_slug,
				'is_ssl' => is_ssl(),
				'fields' => array(
					'banners'           => true,
					'reviews'           => true,
					'downloaded'        => true,
					'active_installs'   => true,
					'icons'             => true,
					'short_description' => true,
				)
			) );

			if ( ! is_wp_error( $plugin_info ) ) {
				$res->plugins[] = $plugin_info;
				set_transient( 'cx-plugin-info-' . $plugin_slug, $plugin_info, DAY_IN_SECONDS * 7 );
			}
		}

		return $res;
	}
}