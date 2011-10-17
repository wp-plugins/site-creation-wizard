<?php

// activate Tiny MCE WYSIWYG editor for textarea boxes.
wp_tiny_mce( true,
			array( "editor_selector" => "creation_wizard" )
			);
			
// stop everything if this user is trying to do something he or she is not supposed to
if ( ! current_user_can('activate_plugins') ) {
	wp_die(__('You do not have sufficient permissions to activate plugins for this blog.'));
}


//Update SCW Options sent by POST from form
//First check nonce for security's sake.
if ( isset( $_REQUEST['_wpnonce'] ) ) {
	$nonce=$_REQUEST['_wpnonce'];
	if (! wp_verify_nonce($nonce) ) die(__('Security check') );
	unset ( $_POST['_wpnonce'] );
	unset ( $_POST['_wp_http_referer'] );

	if( isset( $_POST['wizard_policy_text'] ) ) {
		$policy = str_replace( array( '\"', "\'" ), array( '', '' ), $_POST['wizard_policy_text'] );
		update_site_option('wizard_policy_text', esc_textarea($policy) );
		unset( $_POST['wizard_policy_text'] );
	}
	if( isset( $_POST['wizard_checkbox_text'] ) ) {
		$checkbox = str_replace( array( '\"', "\'" ), array( '', '' ), $_POST['wizard_checkbox_text'] );
		update_site_option('wizard_checkbox_text', esc_textarea($checkbox) );
		unset( $_POST['wizard_policy_text'] );
	}
	
	$type_options_array = array();
	$features_options_array = array();
		
	foreach ( $_POST as $key => $val ) {
		if ( preg_match( '|typeoption_([0-9]+)_name|', $key, $matches ) ) {
			$i = $matches[1];
			$option_array = array();
			$option_array['name'] = htmlentities($_POST['typeoption_'.$i.'_name']);
			$option_array['modelblog'] = intval($_POST['typeoption_'.$i.'_modelblog']);
			$option_array['description'] = htmlentities(stripslashes($_POST['typeoption_'.$i.'_description']));
			$option_array['adminonly'] = (isset($_POST['typeoption_'.$i.'_adminonly']) && $_POST['typeoption_'.$i.'_adminonly'] == 'yes' ) ? 'yes' : '';
			
			$type_options_array['type_option_'.$i] = $option_array;
		} else if ( preg_match( '|featureoption_([0-9]+)_name|', $key, $matches ) ) {
			$i = $matches[1];
			$option_array = array();
			
			$option_array['name'] = htmlentities($_POST['featureoption_'.$i.'_name']);
			$option_array['modelblog'] = intval($_POST['featureoption_'.$i.'_modelblog']);
			$option_array['description'] = htmlentities(stripslashes($_POST['featureoption_'.$i.'_description']));
			$option_array['adminonly'] = (isset($_POST['featureoption_'.$i.'_adminonly']) && $_POST['featureoption_'.$i.'_adminonly'] == 'yes') ? 'yes' : '';
			
			$features_options_array['features_option_'.$i] = $option_array;
		}
	}
	update_site_option('type_options_array', $type_options_array );
	update_site_option('features_options_array', $features_options_array );
}

// Render out SCW setting interface.

// Get SCW Options.
$type_options_array = (get_site_option('type_options_array') && !is_null(get_site_option('type_options_array')) ) ? 
	get_site_option('type_options_array') : array();

$features_options_array = (get_site_option('features_options_array') && !is_null(get_site_option('features_options_array')) )?
	get_site_option('features_options_array') : array();

// Implement UI w/ jQuery
?>

<script type="text/javascript">
	jQuery(document).ready( function($) {
		var type_options_count =<?php echo count( $type_options_array ); ?>;
		var feature_options_count = <?php echo count($features_options_array);?>;
		scw_add_remover();
		$('.ui-icon-arrowthick-2-n-s').css('cursor','move');
		$('#scw_feature_options, #scw_type_options').sortable();
		$('#scw_add_type_button').click( function() {
			var lihtml = $('#scw_default_li').html();
			lihtml = lihtml.replace(/_X_/g, '_' + (type_options_count++) + '_');
			lihtml = lihtml.replace(/default/g, 'type');			
			$('#scw_type_options').append(lihtml);
			scw_add_remover();
		});
		$('#scw_add_feature_button').click( function() {
			var lihtml = $('#scw_default_li').html();
			lihtml = lihtml.replace(/_X_/g, '_' + (feature_options_count++) + '_');	
			lihtml = lihtml.replace(/default/g, 'feature');
			$('#scw_feature_options').append(lihtml);
			scw_add_remover();
		});
		scw_resize_tb();
		$(window).resize( function() { scw_resize_tb() } );
		function scw_resize_tb () {
			var h = Math.floor( $(window).height() * .8 );
			var w = Math.min( Math.floor( $(window).width() * .8 ), 800 );
			$('a.thickbox').each( function() {
				var basehref = $(this).attr('href').replace(/TB_iframe.*$/,'');
				$(this).attr('href', basehref + 'TB_iframe=1&height=' + h + '&width=' + w);
			})
		}
		function scw_add_remover() {
			$('.scw_remove').click( function() {
				$(this).parent().parent().parent().parent().parent().parent().remove();
			} ).css('cursor','pointer');
		}
	});
	function scw_settheid(target, id) {
		jQuery('#'+target).attr('value',id);
		tb_remove();
	}
</script>
<?php

echo '<div id="scw_default_li" style="display:none">';
$this->model_path();
echo "</div>";


//make form
?>
<div class="wrap">

<h2>Site Creation Wizard Settings -- Blog Types</h2>

<form name="input" method="post" action="">
	<?php 
	wp_nonce_field( );
	//settings_fields( 'blogswizard-option-group' ); ?> 
	
	<table class="form-table">
		<tr valign="top">
			<td colspan="2"><h3><?php _e('Policiy Information'); ?></h3></td>
		</tr>
		<tr valign="top">
			<td><?php _e('Policy Text'); ?>: </td><td><textarea class="creation_wizard" name="wizard_policy_text" cols="60" rows="5"><?php echo html_entity_decode(get_site_option('wizard_policy_text')); ?></textarea></td>
		</tr>
		<tr valign="top">
			<td><?php _e('Checkbox Text'); ?>: </td><td><textarea class="creation_wizard" name="wizard_checkbox_text" cols="60" rows="5"><?php echo html_entity_decode(get_site_option('wizard_checkbox_text')); ?></textarea></td>
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
                <ul>
                <li class="ui-widget-header">
                    <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td width="17px">&nbsp;</td>
                            <td width="180px" align="left" style="border-right:1px solid #AAA">
                                name<?php echo $type; ?>
                            </td>
                            <td width="80px" align="left">
                                template site
                            </td>
                            <td width="17px" align="center" style="border-right:1px solid #AAA">&nbsp;
                                
                            </td>
                            <td align="left" style="border-right:1px solid #AAA">
                                description
                            </td>
                            <td width="120px" align="center">
                                super-admin only?
                            </td>
                            <td width="20px" align="center" style="text-align:right">
                            </td>
                        </tr>
                    </table>
                </li>
                </ul>
                <ul id="scw_type_options">
                <?php				
				$i = 0;
				if ( count( $type_options_array ) > 0 ) {
					foreach($type_options_array as $option) {
						$this->model_path( $i++, 'type', $option);
					}
				} else {
						$this->model_path( $i, 'type', array() );					
				}
				?>
                </ul>
				<input id="scw_add_type_button" type="button" name="scw_add_site_type" value="Add Site Type">
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
                <ul>
                <li class="ui-widget-header">
                    <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td width="17px">&nbsp;</td>
                            <td width="180px" align="left" style="border-right:1px solid #AAA">
                                name<?php echo $type; ?>
                            </td>
                            <td width="80px" align="left">
                                template site
                            </td>
                            <td width="17px" align="center" style="border-right:1px solid #AAA">&nbsp;
                                
                            </td>
                            <td align="left" style="border-right:1px solid #AAA">
                                description
                            </td>
                            <td width="120px" align="center">
                                super-admin only?
                            </td>
                            <td width="20px" align="center" style="text-align:right">
                            </td>
                        </tr>
                    </table>
                </li>
                </ul>
				<ul id="scw_feature_options">
                <?php
				$i = 0;
				if ( count( $features_options_array ) > 0 ) {
					foreach($features_options_array as $option) {
						$this->model_path( $i++, 'feature', $option);
					}
				} else {
					$this->model_path( $i++, 'feature', $option);
				}
				?>
				</ul><!-- end features options div -->
				<input id="scw_add_feature_button" type="button" name="scw_add_site_type" value="Add Feature">
				<input type="hidden" id="feature_submitted" name="feature_submitted" value="yes" />
			</td>
		</tr>	
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Features Options') ?>" />
	</p>

</form>
</div>