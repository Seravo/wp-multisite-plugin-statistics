<?php
/*
Plugin Name: WP Multisite Plugin Statistics
Plugin URI: https://github.com/Seravo/wp-multisite-plugin-statistics
Description: A multisite plugin to show plugin usage across all your sites.
Version: 1.0
Author: Seravo Oy
Author URI: http://seravo.fi
License: GPL2
Network: true

Copyright 2012	Lewis J. Goettner, III	(email : lew@goettner.net)
Copyright 2015	Onni Hakala / Seravo Oy	(email : onni@seravo.fi)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	 02110-1301	 USA

*/

class MultisitePluginStats {
	const VERSION = '2.0.0';

	public $plugin_url;

	public function __construct() {
		$this->plugin_url = plugin_dir_url( __FILE__ );
	}

	public function init() {
		// declare hooks
		add_action( 'network_admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
		add_action( 'plugins_loaded', array( $this, 'localization' ) );
	}

	public function localization() {
		load_plugin_textdomain( 'multisite_plugin_stats', false, '/multisite-plugin-stats/languages/' );
	}

	public function add_menu() {
		add_submenu_page(
			'plugins.php',
			__( 'Plugin Statistics', 'multisite_plugin_stats' ),
			__( 'Plugin Statistics', 'multisite_plugin_stats' ),
			'manage_network_options',
			'multisite_plugin_stats',
			array( $this, 'stats_page' )
		);
	}

	public function stats_page() {
		global $wpdb;

		// Check Permissions
		if ( ! is_super_admin() ) {
			die( 'Not on my watch!' );
		}

		// Get a list of all the plugins
		$plugin_info = get_plugins();

		$active_plugins = array();

		// Get the network activated plugins
		$network_plugins = get_site_option( 'active_sitewide_plugins' );

		// Initialize the name array
		$site_names = array();

		// Scan the sites for activation
		$blogs = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = {$wpdb->siteid} AND spam = 0 AND deleted = 0" );

		if ( $blogs ) {
			foreach ( $blogs as $blog_id ) {
				switch_to_blog( $blog_id );

				// Get the name and add it to the list
				$site_names[ $blog_id ] = get_option( 'blogname' );

				// Get active plugins
				$site_plugins = (array) get_option( 'active_plugins', array() );

				// Keep a Count
				foreach ( $site_plugins as $plugin ) {
					if ( isset( $active_plugins[ $plugin ] ) ) {
						$active_plugins[ $plugin ][] = $blog_id;
					} else {
						$active_plugins[ $plugin ] = array( $blog_id );
					}
				}

				restore_current_blog();
			}
		}

		?>
		<div class='wrap'>
			<h2><?php _e( 'Plugin Statistics', 'multisite_plugin_stats' ); ?></h2>
			<h3><?php _e( 'Network Activated Plugins', 'multisite_plugin_stats' ); ?> (<?php echo count( $network_plugins ); ?>)</h3>
			<table class="wp-list-table widefat plugin-usage-table">
				<thead>
					<tr>
						<th data-dynatable-no-sort="true" class="manage-column column-cb check-column">
							<input class="column-select-all" id="network-plugins-select-all" type="checkbox">
						</th>
						<th><?php _e( 'Name', 'multisite_plugin_stats' ); ?></th>
						<th><?php _e( 'Path', 'multisite_plugin_stats' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $network_plugins as $plugin => $etc ) { ?>
						<?php
						echo '<tr><th scope="row" class="check-column"><input type="checkbox" name="checked[]" value="' . plugin_basename( $plugin ) . '" id=""></th>';
						echo '<td>' . $plugin_info[ $plugin ]['Name'] . '</td>';
						echo '<td>' . plugin_basename( $plugin ) . '</td></tr>';
						// Remove it from the list
						unset( $plugin_info[ $plugin ] );
					}
					?>
				</tbody>
			</table>

			<h3><?php _e( 'Active Plugins', 'multisite_plugin_stats' ); ?> (<?php echo count( $active_plugins ); ?>)</h3>
			<table class="wp-list-table widefat plugin-usage-table">
				<thead>
					<tr>
						<th data-dynatable-no-sort="true" class="manage-column column-cb check-column">
							<input class="column-select-all" id="plugins-select-all" type="checkbox">
						</th>
						<th><?php _e( 'Name', 'multisite_plugin_stats' ); ?></th>
						<th><?php _e( 'Path', 'multisite_plugin_stats' ); ?></th>
						<th data-dynatable-sorts="integer" data-dynatable-column="integer"><?php _e( 'Usage', 'multisite_plugin_stats' ); ?></th>
						<th><?php _e( 'Sites', 'multisite_plugin_stats' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$counter = 0;
					foreach ( $active_plugins as $plugin => $blog_array ) {
						if ( empty( $plugin_info[ $plugin ] ) ) {
							continue;
						}
						echo '<tr><th scope="row" class="check-column"><input type="checkbox" name="checked[]" value="' . plugin_basename( $plugin ) . '" id=""></th>';

						echo '<td>' . $plugin_info[ $plugin ]['Name'] . '</td>';
						echo '<td>' . plugin_basename( $plugin ) . '</td>';
						echo '<td>' . count( $blog_array ) . '</td>';

						// List the sites
						echo '<td> ';
						$output = '';
						foreach ( $blog_array as $blog_id ) {
							if ( $output === '' ) { // first round
								$output .= '<a href="' . get_site_url( $blog_id ) . '/wp-admin/plugins.php">' . htmlspecialchars( $site_names[ $blog_id ] ) . '</a>';
							} else { $output .= ' , ' . '<a href="' . get_site_url( $blog_id ) . '/wp-admin/plugins.php">' . htmlspecialchars( $site_names[ $blog_id ] ) . '</a>';
							}
						}
						echo "{$output} </td>";
						echo '</tr>';

						// Remove it from the list
						unset( $plugin_info[ $plugin ] );
						$counter++;
					}
					?>
				</tbody>
			</table>

			<h3><?php _e( 'Inactive Plugins', 'multisite_plugin_stats' ); ?> (<?php echo count( $plugin_info ); ?>)</h3>
			<table class="wp-list-table widefat plugin-usage-table">
				<thead>
					<tr>
						<th data-dynatable-no-sort="true" class="manage-column column-cb check-column">
							<input class="column-select-all" id="unused-plugin-select-all" type="checkbox">
						</th>
						<th><?php _e( 'Name', 'multisite_plugin_stats' ); ?></th>
						<th><?php _e( 'Path', 'multisite_plugin_stats' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $plugin_info as $plugin => $info ) {
						echo '<tr><th scope="row" class="check-column"><input type="checkbox" name="checked[]" value="' . plugin_basename( $plugin ) . '" id=""></th>';
						echo '<td>' . $info['Name'] . '</td>';
						echo '<td>' . plugin_basename( $plugin ) . '</td></tr>';
					}
					?>
				</tbody>
			</table>
		</div> <!-- .wrap -->
	<?php
	}

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */
	public function register_admin_scripts( $page ) {
		wp_enqueue_script(
			'dynatable',
			"{$this->plugin_url}js/jquery.dynatable.js",
			array( 'jquery' ),
			'0.3.1',
			true
		);

		wp_enqueue_script(
			'multisite-plugin-stats-admin-script',
			"{$this->plugin_url}js/admin.js",
			array( 'dynatable' ),
			self::VERSION,
			true
		);

		wp_enqueue_style(
			'dynatable',
			"{$this->plugin_url}css/jquery.dynatable.css",
			array(),
			'0.3.1'
		);

		wp_enqueue_style(
			'multisite-plugin-stats-style',
			"{$this->plugin_url}css/admin.css",
			array( 'dynatable' ),
			self::VERSION
		);
	}
}

$multisite_plugin_stats = new MultisitePluginStats();
$multisite_plugin_stats->init();
