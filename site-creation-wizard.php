<?php
/*
Plugin Name: Site Creation Wizard
Version: 2.4
Description: Allow users to create a site using predefined templates. Compatible with BuddyPress and More Privacy Options.
Author: Jon Gaulding, Ioannis Yessios, Yale Instructional Technology Group
Author URI: http://itg.yale.edu
Author URI: http://www.yessios.com
Plugin URI: http://plugins.commons.yale.edu/site-creation-wizard/
Site Wide Only: true
Network: true
*/

/*  Copyright 2011  Ioannis C. Yessios (email : ioannis.yessios@yale.edu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Thanks to Boone Gorges for patching this plugin to make it compatible with WP 3.1
*/

class CreationWizard {
	var $has_network_admin = false;

	function CreationWizard() {
		// Determine whether this is WP 3.1+, which requires Network Admin hooks
		if ( is_multisite() && function_exists( 'is_network_admin' ) ) {
			$this->has_network_admin = true;
		}
	
		if ( ( !$this->has_network_admin && is_admin() ) || ( $this->has_network_admin && is_network_admin() ) ){
			add_action( 'admin_init', array( $this, 'register' ) );
		}
		wp_register_style('scw-smoothness', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/resources/css/smoothness/jquery-ui-1.8.7.custom.css');

		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'scw_signup' ) {
			$this->scw_signup();
		}

		if ( isset($_REQUEST['page']) && $_REQUEST['page'] == 'site_creation_wizard' ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'scw-smoothness' );
		}
		if ( $_SERVER['PHP_SELF'] == '/wp-signup.php' ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'scw-smoothness' );
		}
		add_action( 'wpmu_new_blog', array( $this, 'wpmu_new_blog' ) );
		add_action( 'signup_blogform', array( $this, 'signup_form' ) );
		add_action( $this->has_network_admin ? 'network_admin_menu' : 'admin_menu', array( $this, 'plugin_menu' ) );
		add_action( 'admin_menu', array( $this, 'dashboard_link') );
		add_action( 'network_admin_menu', array( $this, 'dashboard_link') );
		add_action( 'signup_header', array( $this, 'signup_header' ) );
		add_action( 'wp_ajax_scw-findsite', array( $this, 'findsite') );
		add_action( 'preprocess_signup_form', array( $this, 'preprocess' ) );
	}
	function register() {
		register_setting( 'blogswizard-option-group', 'type_options_array' );
		register_setting( 'blogswizard-option-group', 'features_options_array' );
		register_setting( 'blogswizard-option-group', 'wizard_policy_text' );
		register_setting( 'blogswizard-option-group', 'wizard_checkbox_text' );
	}
	function signup_header () {
		?>
        <style type="text/css">
			<?php
			if ( class_exists("ds_more_privacy_options") ): 
			?>
			#privacy {
				display:none;
			}
			#new_blog_owner {
				font-size:24px;
				margin:5px 0;
				width:100%;
			}
			.mu_register ul {
				max-height:150px;
				overflow:scroll;
			}
			<?php
			endif;
			?>
		</style>
        <script language="javascript">
			jQuery(document).ready( function ($) {
				$('#wizard-dialog').dialog ( { autoOpen: false, 
											modal: true,
											buttons: {
												Ok: function() {
													$(this).dialog('close');
												}
											}

										} );
				$('#setupform').submit(function() {
					//alert('click: ' + $('#wizard_checkbox:checkbox').val());
					//alert('click: ' + $('#wizard_checkbox').val());
					if ( $('#wizard_checkbox').val() ) {
						if ( $('#wizard_checkbox:checkbox').val() == null && $('#wizard_checkbox').val() == 1) {
							return true;
						} else if ( $('#wizard_checkbox:checked').val() == null ) {
							$('#wizard-dialog').dialog( 'open' );
							return false;
						}
					}
					return true;
				});
				if ( $('#signupblog').val() ) {
					$('#signupblog').attr('value','user').parent().css('display','none');	
				}
			});
		</script>
        <?php
	}
	function wpmu_new_blog ( $new_blog_id ) {
		global $wpdb;
		
		if ( !isset( $_POST['creationWizard'] ) || $_POST['creationWizard'] != 1 ) 
			return;

		$adminemail = false;
		global $current_user;
		$userid = $current_user->ID;
		if ( is_super_admin() &&  isset($_POST['new_blog_owner']) && trim( $_POST['new_blog_owner'] ) != '' ) {
			$userid = get_user_id_from_string( $_POST['new_blog_owner'] );
			if ( $userid !== null ) {
				if ( add_user_to_blog( $new_blog_id, $userid, 'administrator' ) == true ) {
					remove_user_from_blog( $current_user->ID , $new_blog_id );
				}
				$tmpuser = get_userdata($userid);
				$adminemail = $tmpuser->user_email;
			} else 
				$userid = $current_user->ID;
		}

		// list of options that shouldn't be touched
		$untouchable_options = array(
				'_transient_random_seed',
				'siteurl',
				'blogname',
				'blogdescription',
				'admin_email',
				'home',
				'recently_edited',
				'secret',
				'blog_public',
				'wp_' . $type_option_model_blog_id . '_user_roles',
				'wp_' . $feature_option_model_blog_id . '_user_roles',
				'fileupload_url',
				'recently_activated',
				'upload_path'
				);

		$type_option_model_blog_id = ( isset( $_POST['type_option_model_blog'] ) ) ? $_POST['type_option_model_blog'] : 0;
		$feature_option_model_blog_ids = ( isset( $_POST['feature_option_model_blog'] ) ) ? $_POST['feature_option_model_blog'] : array();

		if ( $type_option_model_blog_id != 0 ) {
			$type_option_model_blog_prefix = $wpdb->get_blog_prefix( $type_option_model_blog_id );
			
			
			/****************************************************************
			*
			* Copy everything except for options from the type blog
			*
			*****************************************************************/
			switch_to_blog( $new_blog_id );
			$wpdb->query( "DELETE FROM $wpdb->commentmeta" );
			$wpdb->query( "DELETE FROM $wpdb->comments" );
			$wpdb->query( "DELETE FROM $wpdb->links" );
			$wpdb->query( "DELETE FROM $wpdb->postmeta" );
			$wpdb->query( "DELETE FROM $wpdb->posts" );
			$wpdb->query( "DELETE FROM $wpdb->term_relationships" );
			$wpdb->query( "DELETE FROM $wpdb->term_taxonomy" );
			$wpdb->query( "DELETE FROM $wpdb->terms" );
			
			$wpdb->query( "INSERT INTO $wpdb->commentmeta SELECT * FROM {$type_option_model_blog_prefix}commentmeta" );
			$wpdb->query( "INSERT INTO $wpdb->comments SELECT * FROM {$type_option_model_blog_prefix}comments" );
			$wpdb->query( "INSERT INTO $wpdb->links SELECT * FROM {$type_option_model_blog_prefix}links" );
			$wpdb->query( "INSERT INTO $wpdb->postmeta SELECT * FROM {$type_option_model_blog_prefix}postmeta" );
			$wpdb->query( "INSERT INTO $wpdb->posts SELECT * FROM {$type_option_model_blog_prefix}posts" );
			$date = date('Y-m-d H:i:s');
			$gmdate = gmdate('Y-m-d H:i:s');
			$wpdb->query( "UPDATE $wpdb->posts SET post_author = $userid, post_date = '$date', post_modified = '$date', post_date_gmt = '$gmdate', post_modified_gmt = '$gmdate'" );
			//echo "<pre>INSERT INTO $wpdb->posts SELECT * FROM {$type_option_model_blog_prefix}posts</pre>";
			$wpdb->query( "INSERT INTO $wpdb->term_relationships SELECT * FROM {$type_option_model_blog_prefix}term_relationships" );
			$wpdb->query( "INSERT INTO $wpdb->term_taxonomy SELECT * FROM {$type_option_model_blog_prefix}term_taxonomy" );
			$wpdb->query( "INSERT INTO $wpdb->terms SELECT * FROM {$type_option_model_blog_prefix}terms" );			
			restore_current_blog();
			
			// get type model blog options
			$type_option_model_blog_options = $wpdb->get_results( "SELECT * FROM {$type_option_model_blog_prefix}options WHERE option_name NOT LIKE '\_%' AND option_name NOT LIKE '%user_roles'" );
			
			// duplicate all settings besides untouchable settings from type model blog to new blog
			foreach ($type_option_model_blog_options as $option) {
				if( !in_array($option->option_name, $untouchable_options) ) {
					$new_option_value = maybe_unserialize($option->option_value);
					update_blog_option($new_blog_id, $option->option_name, $new_option_value);
				}
			}
		}
		
		$new_blog_prefix = $wpdb->get_blog_prefix( $new_blog_id );

		foreach ( $feature_option_model_blog_ids as $feature_option_model_blog_id ) {
			// get features model blog options
			$feature_option_model_blog_prefix = $wpdb->get_blog_prefix( $feature_option_model_blog_id );
			$feature_option_model_blog_options = $wpdb->get_results( "SELECT * FROM {$feature_option_model_blog_prefix}options WHERE option_name NOT LIKE '\_%' AND option_name NOT LIKE '%user_roles'" );
					
			// get new list of new blog options
			$new_blog_options = $wpdb->get_results( "SELECT * FROM {$new_blog_prefix}options WHERE option_name NOT LIKE '\_%' AND option_name NOT LIKE '%user_roles'" );
			
			// copy option not already set in new blog but set in feature option model blog into new blog
			foreach ($feature_option_model_blog_options as $option) {
				$already_set = 'no';
				foreach($new_blog_options as $new_blog_option) {
					if($option->option_name == $new_blog_option->option_name) {
						$already_set = 'yes';
						break;
					}
				}
				
				if( $already_set == 'no' && $option->option_name != 'wp_' . $feature_option_model_blog_id . '_user_roles' ) {
					$new_option_value = maybe_unserialize($option->option_value);
					update_blog_option($new_blog_id, $option->option_name, $new_option_value);
				}
			}
			// merge activated plugins from new blog and feature options blog and set that new array 
			$new_blog_active_plugins = get_blog_option($new_blog_id, 'active_plugins');
			$feature_option_model_blog_active_plugins = get_blog_option($feature_option_model_blog_id, 'active_plugins');
			
			foreach( $feature_option_model_blog_active_plugins as $plugin) {
				if( !in_array($plugin, $new_blog_active_plugins) ) {
					$new_blog_active_plugins[] = $plugin;
				}
			}
			update_blog_option($new_blog_id, 'active_plugins', $new_blog_active_plugins);
		}
		if ( is_super_admin() && $adminemail != false ) {
			update_blog_option($new_blog_id, 'admin_email', $adminemail);
			update_blog_option($new_blog_id, 'new_admin_email', $adminemail);
		}
	}
	
	function preprocess () {
		if ( isset( $_REQUEST['signup_for'] ) )
			$_REQUEST['signup_for'] = 'user';	
	}
	
	function signup_form() {
		global $wpdb;
		$type_options_array = get_site_option( 'type_options_array',array() );

		if ( class_exists("ds_more_privacy_options") ) :
		?>
        	<div id="new_privacy">
                <p class="privacy-intro">
                    <label for="blog_public_on">Privacy:</label>
                    I want my new site to...
                    <br style="clear: both;">
                    <input type="radio" value="1" name="blog_public" id="blog_public_on" <?php 
						if ( isset($_POST['blog_public']) && $_POST['blog_public'] == 1 ) 
							echo 'checked="checked" ';
					?>/>
                    be viewable by everyone and appear in search engines like Google, Technorati, and in public listings around this network.
                    <br style="clear: both;">
                    <input type="radio" value="0" name="blog_public" id="blog_public_off" <?php 
						if ( isset($_POST['blog_public']) && $_POST['blog_public'] == 0 ) 
							echo 'checked="checked" ';
					?>/>
                    to be viewable by everyone and appear in listings on this network, but not listed in search engines.
                    <br style="clear: both;">
                    <input type="radio" value="-1" name="blog_public" id="blog_public_on" <?php 
						if ( ( isset($_POST['blog_public']) && $_POST['blog_public'] == -1 ) || !isset($_POST['blog_public']) )
							echo 'checked="checked" ';
					?>/>
                    to be closed to the world, but visible to users belonging to this network.
                    <br style="clear: both;">
                    <input type="radio" value="-2" name="blog_public" id="blog_public_on" <?php 
						if ( isset($_POST['blog_public']) && $_POST['blog_public'] == -2 ) 
							echo 'checked="checked" ';
					?>/>
                    to be visible only to users registered to it.
                    <br style="clear: both;">
                    <input type="radio" value="-3" name="blog_public" id="blog_public_on" <?php 
						if ( isset($_POST['blog_public']) && $_POST['blog_public'] == -3 ) 
							echo 'checked="checked" ';
					?>/>
                    to be visible only to its administrator. 
                </p>            
            </div>
		<?php 
			//ds_more_privacy_options::add_privacy_options( false );
		endif;
		
		if ( is_super_admin() ) :
		?>
        <label>Site Owner (login or email address):</label>
        <input type="text" id="new_blog_owner" name="new_blog_owner"  />
        <hr />
		<?php
		endif;
		?>
		<label>Select the type of blog you would like to create:</label>
        <input type="hidden" name="creationWizard" value="1" />        
        <?php
		
		foreach( $type_options_array as $key => $type_option ) {
			
			if ( $type_option['adminonly'] != 'yes' || current_user_can( 'manage_network_options' ) ) {
				echo '<label for="' . $type_option['name'] . '">'; 
				$checked = ( $_POST['type_option_model_blog'] == $type_option['modelblog'] ) ? ' checked="checked" ':'';
				echo '<input type="radio" id="' . $type_option['name'] . '" name="type_option_model_blog" value="'. $type_option['modelblog'] .'"'. $checked .' />' .
				'<strong>' . $type_option['name'] . '</strong>';
				if ( current_user_can( 'manage_network_options' ) && $type_option['adminonly'] == 'yes' )
					echo ' <i>(ADMIN ONLY)</i>';
				echo '</label>';
				?>
			
				<div style="padding-left:35px">
				<?php
				// echo the description for this blog type:
				echo $type_option['description'];
				?>
				</div>
				<?php
			}
		}
		?><label for="Default">
		<input type="radio" id="<?php echo $type_option['name']; ?>" name="type_option_model_blog" value="0" <?php 
			if ( !isset( $_POST['type_option_model_blog'] ) || $_POST['type_option_model_blog'] == 0 ) echo 'checked="checked"';
		?>/><strong>Default</strong></label>
				<div style="padding-left:35px">Unformatted Site, best for advanced users who already have experience with Wordpress.</div>
		<?php
		
		$features_options_array = get_site_option('features_options_array', array() );
		
		if ( count($feature_options_array) == 1 && $feature_options_array[0]['feature_option_model_blog'] == 0 ) {
			$feature_options_array = array();
		}
		if ( count($features_options_array) > 0 ) {
			echo '<label>Select the feature set you want for your blog:</label>';
			
			foreach($features_options_array as $key => $feature_option) {
				if( $feature_option['adminonly'] != 'yes' || current_user_can( 'manage_network_options' ) ) {
					echo '<label for="' . $feature_option['name'] . '">';
	
					$checked = ( isset( $_POST['feature_option_model_blog'] ) && in_array( $feature_option['modelblog'], $_POST['feature_option_model_blog'] ) ) ? ' checked="checked" ':'';
					
					echo '<input type="checkbox" id="' . $feature_option['name'] . '" name="feature_option_model_blog[]" value="'. $feature_option['modelblog'] .'"' . $checked . ' />' .
					'<strong>' . $feature_option['name'] . '</strong>';
					if ( current_user_can( 'manage_network_options' ) && $feature_option['adminonly'] == 'yes' )
						echo ' <i>(ADMIN ONLY)</i>';
					echo '</label>';
					?>
					<div style="padding-left:35px">
					<?php
					// echo the description for this feature type:
					echo $feature_option['description'];
					?>
					</div>
					<?php
				}
			}
		}
		echo get_site_option( 'wizard_policy_text' );
		
		$checkbox = get_site_option( 'wizard_checkbox_text' );
		if ( trim( $checkbox ) != '' && !current_user_can( 'manage_network_options' ) ) {
			$checked = ( isset( $_POST['wizard_checkbox'] ) && $_POST['wizard_checkbox'] == 1 ) ? ' checked="checked" ':'';
			?>
            <div id="wizard-dialog" title="Confirm Policy">
				<p>Please confirm your the checkbox labeled:<br />
                	<?php echo $checkbox; ?></p>
			</div>
            <table><tr><td><input type="checkbox" id="wizard_checkbox" name="wizard_checkbox" value="1" <?php echo $checked; ?>/></td>
            	<td><?php 
			echo $checkbox. '</td></tr></table>';
		} else { 
			 ?>
            <input id="wizard_checkbox" type="hidden" name="wizard_checkbox" value="1" />
        <?php
		}

	}

	// create settings page and add this to settings menu
	function settings_page() {
		if ( !is_super_admin() ) die( 'You are not allowed to do this!' );
		$optionspath = dirname(__FILE__) . "/" . "options.php";
		include($optionspath);
	}
	
	function dashboard_link() {
		$term = 'Site';
		$active_signup = get_site_option( 'registration' );
		if ( !$active_signup )
			$active_signup = 'all';
		$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"
		if ( $active_signup == 'all' || $active_signup == 'blog' )
			add_dashboard_page( 'Create New '.$term, 'Create New '.$term, 'read', "scw_signup", array($this, 'scw_signup') );
	}
	function plugin_menu() {
		$term = 'Site';
		//add_options_page ('Site Creation Wizard', 'Site Creation Wizard', 'administrator', 'blogswizard-settings-handle', array( $this, 'settings_page' ) );
		
		// The Dashboard menu must be added differently for WPMU, WP 3.0.x, and WP 3.1+
		if ( $this->get_major_version() < 3 ) {
			$parent_page = 'wpmu-admin.php';
		} else {
			$parent_page = $this->has_network_admin ? 'settings.php' : 'ms-admin.php';
		}
		
		add_submenu_page( $parent_page ,$term.' Creation Wizard', $term.' Creation Wizard', 'update_core', 'site_creation_wizard', array( $this, 'settings_page' ) );	
	}
	
	function scw_signup() {
		//echo "<h2>I am here</h2>";
		
		$site = get_current_site();
		//echo "<pre>"; print_r($site); echo "</pre>";
		$url = 'http://' . $site->domain . '/wp-signup.php';
		//echo "<p>$url</p>";
		//header('Location: ' . $url);
		
		if ( !function_exists( 'wp_redirect' ) )
			include_once( ABSPATH . '/wp-includes/pluggable.php' );
		wp_redirect( $url );
		//wpmu_admin_do_redirect($url);	
	}
	function model_path( $ind = 'X', $type = 'default', $options = array() ) {
		$options = ( count($options) != 0 ) ? $options : array( 'name' => '', 'modelblog' => '', 'description' => '', 'adminonly' => 'no' );
		
		if ( ltrim( $options['modelblog'] ) == '' && $ind != 'X' ) return;
		
		$scw_prefix = $type.'option_'.$ind.'_';
		?>

        <li id="<?php $scw_prefix; ?>container" class="ui-widget-content ui-corner-all">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr valign="top">
                   <td width="17px" valign="middle"><span title="Drag to Reorder" class="ui-icon ui-icon-arrowthick-2-n-s"></span></td>
                   <td width="180px" style="border-right:1px dashed blue">
                        <input width="100%" type="text" name="<?php echo $scw_prefix; ?>name" value="<?php echo $options['name']; ?>" />
                    </td>
                    <td  width="80px">
                        <input size="6" type="text" id="<?php echo $scw_prefix; ?>modelblog" value="<?php echo $options['modelblog']; ?>" name="<?php echo $scw_prefix; ?>modelblog" value="<?php echo $options['modelblog']; ?>" />
                    </td>
                    <td width="17px" style="border-right:1px dashed blue">
                        <div class="scw-searchbox ui-state-hover ui-corner-all"><a class="ui-icon ui-icon-search thickbox" href="<?php
							 echo get_bloginfo('url') . '/wp-admin/admin-ajax.php?action=scw-findsite&target=' . $scw_prefix; 
							?>modelblog&TB_iframe=1&height=400&width=400" title="Search for Site ID"></a><div>                        
                    </td>
                    <td style="border-right:1px dashed blue">
                        <textarea rows="3" type="text" name="<?php echo $scw_prefix; ?>description" style="width:100%"><?php echo $options['description']; ?></textarea>
                    </td>
                    <td width="120px" style="text-align:center">
                        <input type="checkbox" title="Limit availability to Super Admins Only" name="<?php echo $scw_prefix; ?>adminonly" value="yes" <?php echo ( $options['adminonly']  == 'yes' ) ? 'checked="checked"': ''; ?> />
                    </td>
                    <td width="20px" align="center" style="text-align:right">
                        <div class="scw_remove_out ui-state-hover ui-corner-all"><a title="Remove Row" class="scw_remove ui-icon ui-icon-closethick"></a></div>
                    </td>
                </tr>
            </table>
        </li>
    <?php	
	}

	function findsite() {
		if ( !is_super_admin() ) die ('You are not allowed to do this!');
		include( dirname(__FILE__) . "/" . "search.php" );		
		exit;
	}
	/**************************************************************
	* @return the major version (2 or 3)
	***************************************************************/
	function get_major_version()
	{
		global  $wp_version;
		return array_shift(explode('.',$wp_version));
	}
}
new CreationWizard;

