<?php

wp_tiny_mce( true,
			array( "editor_selector" => "creation_wizard" )
			);
// stop everything if this user is trying to do something he or she is not supposed to
if ( ! current_user_can('activate_plugins') ) {
	wp_die(__('You do not have sufficient permissions to activate plugins for this blog.'));
}

if ( isset( $_REQUEST['_wpnonce'] ) ) {
	$nonce=$_REQUEST['_wpnonce'];
	if (! wp_verify_nonce($nonce) ) die(__('Security check') );
	unset ( $_POST['_wpnonce'] );
	unset ( $_POST['_wp_http_referer'] );

	if( isset( $_POST['wizard_policy_text'] ) ) {
		update_site_option('wizard_policy_text', $_POST['wizard_policy_text'] );
		unset( $_POST['wizard_policy_text'] );
	}
	if( isset( $_POST['wizard_checkbox_text'] ) ) {
		update_site_option('wizard_checkbox_text', $_POST['wizard_checkbox_text'] );
		unset( $_POST['wizard_policy_text'] );
	}

	$type_options_array = array();
	$features_options_array = array();
		
	foreach ( $_POST as $key => $val ) {
		if ( preg_match( '|typeoption_([0-9]+)_name|', $key, $matches ) ) {
			$i = $matches[1];
			$option_array = array();
			$option_array['name'] = $_POST['typeoption_'.$i.'_name'];
			$option_array['modelblog'] = $_POST['typeoption_'.$i.'_modelblog'];
			$option_array['description'] = $_POST['typeoption_'.$i.'_description'];
			$option_array['adminonly'] = $_POST['typeoption_'.$i.'_adminonly'];
			
			$type_options_array['type_option_'.$i] = $option_array;
		} else if ( preg_match( '|featureoption_([0-9]+)_name|', $key, $matches ) ) {
			$i = $matches[1];
			$option_array = array();
			
			$option_array['name'] = $_POST['featureoption_'.$i.'_name'];
			$option_array['modelblog'] = $_POST['featureoption_'.$i.'_modelblog'];
			$option_array['description'] = $_POST['featureoption_'.$i.'_description'];
			$option_array['adminonly'] = $_POST['featureoption_'.$i.'_adminonly'];
			
			$features_options_array['features_option_'.$i] = $option_array;
		}
	}
	update_site_option('type_options_array', $type_options_array );
	update_site_option('features_options_array', $features_options_array );
}
?>

<script type="text/javascript">
	type_options_count = 0; // init @ zero
	
	function add_blog_type_option(target_div, preloaded_name, preloaded_modelblog, preloaded_description, preloaded_adminonly) {
		var option_HTML = document.getElementById('type_option_model').innerHTML;
		option_HTML = option_HTML.replace('typeoption_X_container', 'typeoption_' + type_options_count + '_container');
		option_HTML = option_HTML.replace('typeoption_X_container', 'typeoption_' + type_options_count + '_container');
		option_HTML = option_HTML.replace('typeoption_X_name', 'typeoption_' + type_options_count + '_name');
		option_HTML = option_HTML.replace('typeoption_X_modelblog', 'typeoption_' + type_options_count + '_modelblog');
		option_HTML = option_HTML.replace('typeoption_X_description', 'typeoption_' + type_options_count + '_description');
		option_HTML = option_HTML.replace('typeoption_X_adminonly', 'typeoption_' + type_options_count + '_adminonly');
		option_HTML = option_HTML.replace('preloaded_name', preloaded_name);
		option_HTML = option_HTML.replace('preloaded_modelblog', preloaded_modelblog);
		option_HTML = option_HTML.replace('preloaded_description', preloaded_description);
		option_HTML = option_HTML.replace('preloaded_adminonly', preloaded_adminonly);
		
		//document.getElementById(target_div).appendChild += option_HTML;
		var newdiv = document.createElement('div');
		newdiv.innerHTML = option_HTML;
		document.getElementById(target_div).appendChild(newdiv);
		type_options_count++;
	}
	
	function remove_blog_type_option(option_container) {
		document.getElementById(option_container).innerHTML = '';
	}
	
	// feature options functions:
	feature_options_count = 0;
	
	function add_feature_option(target_div, preloaded_name, preloaded_modelblog, preloaded_description, preloaded_adminonly) {
		
		var option_HTML = document.getElementById('feature_option_model').innerHTML;
		option_HTML = option_HTML.replace('featureoption_X_container', 'featureoption_' + feature_options_count + '_container');
		option_HTML = option_HTML.replace('featureoption_X_container', 'featureoption_' + feature_options_count + '_container');
		option_HTML = option_HTML.replace('featureoption_X_name', 'featureoption_' + feature_options_count + '_name');
		option_HTML = option_HTML.replace('featureoption_X_modelblog', 'featureoption_' + feature_options_count + '_modelblog');
		option_HTML = option_HTML.replace('featureoption_X_description', 'featureoption_' + feature_options_count + '_description');
		option_HTML = option_HTML.replace('featureoption_X_adminonly', 'featureoption_' + feature_options_count + '_adminonly');
		option_HTML = option_HTML.replace('preloaded_name', preloaded_name);
		option_HTML = option_HTML.replace('preloaded_modelblog', preloaded_modelblog);
		option_HTML = option_HTML.replace('preloaded_description', preloaded_description);
		option_HTML = option_HTML.replace('preloaded_adminonly', preloaded_adminonly);
		
		var newdiv = document.createElement('div');
		newdiv.innerHTML = option_HTML;
		document.getElementById(target_div).appendChild(newdiv);
		feature_options_count++;
	}
	
	function remove_feature_option(option_container) {
		document.getElementById(option_container).innerHTML = '';
	}
</script>

<?php
$existing_options = array();

if(get_site_option('type_options_array') && !is_null(get_site_option('type_options_array')) ) {
	$type_options_array = get_site_option('type_options_array');
	
	foreach($type_options_array as $option) {
		$adminonly = ($option['adminonly'] == 'yes' ? 'checked' : '');
		$existing_options[] = "add_blog_type_option('existing_type_options', '" . $option['name'] . "', '" . $option['modelblog'] . "', '" . $option['description'] . "', '" . $adminonly . "');";
	}
	
}

if(get_site_option('features_options_array') && !is_null(get_site_option('features_options_array')) ) {
	$features_options_array = get_site_option('features_options_array');
	
	foreach($features_options_array as $option) {
		$adminonly = ($option['adminonly'] == 'yes' ? 'checked' : '');	
		$existing_options[] =  "add_feature_option('existing_feature_options', '" . $option['name'] . "', '" . $option['modelblog'] . "', '" . $option['description'] . "', '" . $adminonly . "');";
	}
}

echo "<body onload=\"";

foreach($existing_options as $option) {
	echo $option;
}

echo "\">";
?>
<div id="type_option_model" style="display:none">
	<div id="typeoption_X_container">
		<div style="border:1px dotted black;padding:5px;background-color:#FFF">
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="20%" align="center" style="border-bottom:1px solid blue;border-right:1px dashed blue">
						name of type
					</td>
					<td width="10%" align="center" style="border-bottom:1px solid blue;border-right:1px dashed blue">
						template site ID
					</td>
					<td width="50%" align="center" style="border-bottom:1px solid blue;border-right:1px dashed blue">
						description
					</td>
					<td width="15%" align="center" style="border-bottom:1px solid blue">
						super-admin only?
					</td>
					<td width="5%" align="center" style="border-bottom:1px solid blue;text-align:right">
						<input type="button" value="X" onClick="remove_blog_type_option('typeoption_X_container')" style="color:red;">
					</td>
				</tr>
				<tr>
					<td style="border-right:1px dashed blue">
						<input width="100%" type="text" name="typeoption_X_name" value="preloaded_name" />
					</td>
					<td style="border-right:1px dashed blue">
						<input size="8" type="text" name="typeoption_X_modelblog" value="preloaded_modelblog" />
					</td>
					<td style="border-right:1px dashed blue">
						<textarea rows="3" type="text" name="typeoption_X_description" style="width:100%">preloaded_description</textarea>
					</td>
					<td style="text-align:center">
				   		<input type="checkbox" name="typeoption_X_adminonly" value="yes" preloaded_adminonly />
					</td>
					<td>
					
					</td>
				</tr>
			</table>
		</div>
	</div><!-- end one type option div -->

</div>

<div id="feature_option_model" style="display:none">
	<div id="featureoption_X_container">
		<div style="border:1px dotted black;padding:5px;background-color:#FFF">
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="20%" align="center" style="border-bottom:1px solid blue;border-right:1px dashed blue">
						feature
					</td>
					<td width="10%" align="center" style="border-bottom:1px solid blue;border-right:1px dashed blue">
						feature blog ID
					</td>
					<td width="50%" align="center" style="border-bottom:1px solid blue;border-right:1px dashed blue">
						feature description
					</td>
					<td width="15%" align="center" style="border-bottom:1px solid blue">
						super-admin only?
					</td>
					<td width="5%" align="center" style="border-bottom:1px solid blue;text-align:right">
						<input type="button" value="X" onClick="remove_feature_option('featureoption_X_container')" style="color:red;">
					</td>
				</tr>
				<tr>
					<td style="border-right:1px dashed blue">
						<input width="100%" type="text" name="featureoption_X_name" value="preloaded_name" />
					</td>
					<td style="border-right:1px dashed blue">
						<input size="8" type="text" name="featureoption_X_modelblog" value="preloaded_modelblog" />
					</td>
					<td style="border-right:1px dashed blue">
						<textarea rows="3" type="text" name="featureoption_X_description" style="width:100%">preloaded_description</textarea>
					</td>
					<td style="text-align:center">
						<input type="checkbox" name="featureoption_X_adminonly" value="yes" preloaded_adminonly />
					</td>
				</tr>
			</table>
			  
		</div>
	
	</div><!-- end one type option div -->

</div>

<div class="wrap">

<h2>Site Creation Wizard Settings -- Blog Types</h2>

<form name="input" method="post" action="#">
	<?php 
	wp_nonce_field( );
	//settings_fields( 'blogswizard-option-group' ); ?> 
	
	<table class="form-table">
		<tr valign="top">
			<td colspan="2"><h3><?php _e('Policiy Information'); ?></h3></td>
		</tr>
		<tr valign="top">
			<td><?php _e('Policy Text'); ?>: </td><td><textarea class="creation_wizard" name="wizard_policy_text" cols="60" rows="5"><?php echo get_site_option('wizard_policy_text'); ?></textarea></td>
		</tr>
		<tr valign="top">
			<td><?php _e('Checkbox Text'); ?>: </td><td><textarea class="creation_wizard" name="wizard_checkbox_text" cols="60" rows="5"><?php echo get_site_option('wizard_checkbox_text'); ?></textarea></td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Policy Options') ?>" />
	</p>

<br />
<hr />

	<table class="form-table">
		<tr valign="top">
			<td>
				<h3>Type of Site</h3>
				<div id="existing_type_options">
				</div>
				<div id="type_options">
				</div><!-- end type options div -->
				<br />
				<input type="button" value="Add New Site Type" onClick="add_blog_type_option('type_options', '', '', '', '')">
				<input type="hidden" id="types_submitted" name="types_submitted" value="yes" />
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Blog Type Options') ?>" />
	</p>

<br />
<hr />

<!-- Features options form -->
	<table class="form-table">
		<tr valign="top">
			<td>
				<h3>Features</h3>
				<div id="existing_feature_options">
				</div>
				<div id="feature_options">
				</div><!-- end features options div -->
				<br />
				<input type="button" value="Add features option" onClick="add_feature_option('feature_options', '', '', '', '')">
				<input type="hidden" id="feature_submitted" name="feature_submitted" value="yes" />
			</td>
		</tr>	
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Features Options') ?>" />
	</p>

</form>
</div>