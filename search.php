<?php
//require_once( ABSPATH . '/wp-admin/admin-header.php' );

//@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

wp_enqueue_style( 'global' );
wp_enqueue_style( 'wp-admin' );
wp_enqueue_style( 'ms');
wp_enqueue_style( 'ie' );
wp_enqueue_style( 'colors' );
wp_enqueue_style( 'scw-smoothness' );

?>
<head>
<?php
//do_action('admin_init');
//do_action('admin_head');
do_action('admin_print_styles');
do_action('admin_print_scripts');
global $wpdb;
?>
<style type="text/css">
table { margin: 2px; }
</style>
<script language="javascript"><!--
function scw_setid(target,id) {
	window.parent.scw_settheid(target,id);	
}
--></script>
</head>
<body>
<?php

$pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
if ( empty($pagenum) )
	$pagenum = 1;

$per_page = (int) get_user_option( 'ms_sites_per_page' );
if ( empty( $per_page ) || $per_page < 1 )
	$per_page = 15;

$per_page = apply_filters( 'ms_sites_per_page', $per_page );

$s = isset( $_GET['s'] ) ? stripslashes( trim( $_GET[ 's' ] ) ) : '';
$like_s = esc_sql( like_escape( $s ) );

$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";

if ( isset( $_GET['searchaction'] ) ) {
	if ( 'name' == $_GET['searchaction'] ) {
		$query .= " AND ( {$wpdb->blogs}.domain LIKE '%{$like_s}%' OR {$wpdb->blogs}.path LIKE '%{$like_s}%' ) ";
	} elseif ( 'id' == $_GET['searchaction'] ) {
		$query .= " AND {$wpdb->blogs}.blog_id = '{$like_s}' ";
	} elseif ( 'ip' == $_GET['searchaction'] ) {
		$query = "SELECT *
			FROM {$wpdb->blogs}, {$wpdb->registration_log}
			WHERE site_id = '{$wpdb->siteid}'
			AND {$wpdb->blogs}.blog_id = {$wpdb->registration_log}.blog_id
			AND {$wpdb->registration_log}.IP LIKE ('%{$like_s}%')";
	}
}

$order_by = isset( $_GET['sortby'] ) ? $_GET['sortby'] : 'id';
if ( $order_by == 'registered' ) {
	$query .= ' ORDER BY registered ';
} elseif ( $order_by == 'lastupdated' ) {
	$query .= ' ORDER BY last_updated ';
} elseif ( $order_by == 'blogname' ) {
	$query .= ' ORDER BY domain ';
} else {
	$order_by = 'id';
	$query .= " ORDER BY {$wpdb->blogs}.blog_id ";
}

$order = ( isset( $_GET['order'] ) && 'DESC' == $_GET['order'] ) ? "DESC" : "ASC";
$query .= $order;

$total = $wpdb->get_var( str_replace( 'SELECT *', 'SELECT COUNT(blog_id)', $query ) );

$query .= " LIMIT " . intval( ( $pagenum - 1 ) * $per_page ) . ", " . intval( $per_page );
$blog_list = $wpdb->get_results( $query, ARRAY_A );

$num_pages = ceil($total / $per_page);
$page_links = paginate_links( array(
	'base' => add_query_arg( 'paged', '%#%' ),
	'format' => '',
	'prev_text' => __( '&laquo;' ),
	'next_text' => __( '&raquo;' ),
	'total' => $num_pages,
	'current' => $pagenum
));

if ( empty( $_GET['mode'] ) )
	$mode = 'list';
else
	$mode = esc_attr( $_GET['mode'] );
?>

<div class="wrap">
<?php screen_icon(); ?>
<h2><?php _e('Sites') ?>
<?php
if ( isset( $_GET['s'] ) && $_GET['s'] )
printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $s ) );
?>
</h2>

<form action="admin-ajax.php" method="get" id="ms-search">
<p class="search-box">
<input type="hidden" name="action" value="scw-findsite" />
<input type="hidden" name="target" value="<?php echo $_REQUEST['target']; ?>" />
<input type="text" name="s" value="<?php echo esc_attr( $s ); ?>" />
<input type="submit" class="button" value="<?php esc_attr_e( 'Search Site by' ) ?>" />
<select name="searchaction">
	<option value="name" selected="selected"><?php _e( 'Name' ); ?></option>
	<option value="id"><?php _e( 'ID' ); ?></option>
	<option value="ip"><?php _e( 'IP address' ); ?></option>
</select>
</p>
</form>

<?php if ( $page_links ) { ?>
<div class="tablenav-pages">
<?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
number_format_i18n( min( $pagenum * $per_page, $total ) ),
number_format_i18n( $total ),
$page_links
); echo $page_links_text; ?>
</div>
<?php } ?>

<div class="view-switch">
	<a href="<?php echo esc_url( add_query_arg( 'mode', 'list', $_SERVER['REQUEST_URI'] ) ) ?>"><img <?php if ( 'list' == $mode ) echo 'class="current"'; ?> id="view-switch-list" src="<?php echo esc_url( includes_url( 'images/blank.gif' ) ); ?>" width="20" height="20" title="<?php _e( 'List View' ) ?>" alt="<?php _e( 'List View' ) ?>" /></a>
	<a href="<?php echo esc_url( add_query_arg( 'mode', 'excerpt', $_SERVER['REQUEST_URI'] ) ) ?>"><img <?php if ( 'excerpt' == $mode ) echo 'class="current"'; ?> id="view-switch-excerpt" src="<?php echo esc_url( includes_url( 'images/blank.gif' ) ); ?>" width="20" height="20" title="<?php _e( 'Excerpt View' ) ?>" alt="<?php _e( 'Excerpt View' ) ?>" /></a>
</div>

</div>

<div class="clear"></div>

<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$blogname_columns = ( is_subdomain_install() ) ? __( 'Domain' ) : __( 'Path' );
$sites_columns = array(
	'id'           => __( 'ID' ),
	'blogname'     => $blogname_columns,
	'lastupdated'  => __( 'Last Updated'),
	'registered'   => _x( 'Registered', 'site' ),
	'users'        => __( 'Users' )
);

if ( has_filter( 'wpmublogsaction' ) )
	$sites_columns['plugins'] = __( 'Actions' );

$sites_columns = apply_filters( 'wpmu_blogs_columns', $sites_columns );
?>

<table class="widefat scw-table">
	<thead>
		<tr>
		<th class="manage-column column-cb check-column" id="cb" scope="col">
		</th>
		<?php
		$col_url = '';
		foreach($sites_columns as $column_id => $column_display_name) {
			$column_link = "<a href='";
			$order2 = '';
			if ( $order_by == $column_id )
				$order2 = ( $order == 'DESC' ) ? 'ASC' : 'DESC';

			$column_link .= esc_url( add_query_arg( array( 'action' => 'scw-findsite', 'order' => $order2, 'paged' => $pagenum, 'sortby' => $column_id ), remove_query_arg( array('action', 'updated'), $_SERVER['REQUEST_URI'] ) ) );
			$column_link .= "'>{$column_display_name}</a>";
			$col_url .= '<th scope="col">' . ( ( $column_id == 'users' || $column_id == 'plugins' ) ? $column_display_name : $column_link ) . '</th>';
		}
		echo $col_url ?>
		</tr>
	</thead>
	<tfoot>
		<tr>
		<th class="manage-column column-cb check-column" id="cb1" scope="col">
		</th>
			<?php echo $col_url ?>
		</tr>
	</tfoot>
	<tbody id="the-site-list" class="list:site">
    <?php
			$status_list = array( 'archived' => array( 'site-archived', __( 'Archived' ) ), 'spam' => array( 'site-spammed', _x( 'Spam', 'site' ) ), 'deleted' => array( 'site-deleted', __( 'Deleted' ) ), 'mature' => array( 'site-mature', __( 'Mature' ) ) );
			if ( $blog_list ) {
				$class = '';
				foreach ( $blog_list as $blog ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate';
					reset( $status_list );

					$blog_states = array();
					foreach ( $status_list as $status => $col ) {
						if ( get_blog_status( $blog['blog_id'], $status ) == 1 ) {
							$class = $col[0];
							$blog_states[] = $col[1];
						}
					}
					$blog_state = '';
					if ( ! empty( $blog_states ) ) {
						$state_count = count( $blog_states );
						$i = 0;
						$blog_state .= ' - ';
						foreach ( $blog_states as $state ) {
							++$i;
							( $i == $state_count ) ? $sep = '' : $sep = ', ';
							$blog_state .= "<span class='post-state'>$state$sep</span>";
						}
					}
					echo "<tr class='$class'>";

					$blogname = ( is_subdomain_install() ) ? str_replace( '.'.$current_site->domain, '', $blog['domain'] ) : $blog['path'];
					foreach ( $sites_columns as $column_name=>$column_display_name ) {
						switch ( $column_name ) {
							case 'id': ?>
								<th scope="row" class="check-column">
									<div title="Select this Site" class="scw-check ui-state-default ui-corner-all" style="margin-left:2px;"><a href="#" class="ui-icon ui-icon-check" onClick="scw_setid('<?php echo $_REQUEST['target'];?>',<?php echo $blog['blog_id']; ?>); return false;"></a></div>
								</th>
								<th valign="top" scope="row">
									<?php echo $blog['blog_id'] ?>
								</th>
							<?php
							break;

							case 'blogname': ?>
								<td class="column-title">
									<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=scw-findsite&amp;id=' . $blog['blog_id'] ) ); ?>" class="edit"><?php echo $blogname . $blog_state; ?></a>
									<?php
									if ( 'list' != $mode )
										echo '<p>' . sprintf( _x( '%1$s &#8211; <em>%2$s</em>', '%1$s: site name. %2$s: site tagline.' ), get_blog_option( $blog['blog_id'], 'blogname' ), get_blog_option( $blog['blog_id'], 'blogdescription ' ) ) . '</p>';

									?>
								</td>
							<?php
							break;

							case 'lastupdated': ?>
								<td valign="top">
									<?php
									if ( 'list' == $mode )
										$date = 'Y/m/d';
									else
										$date = 'Y/m/d \<\b\r \/\> g:i:s a';
									echo ( $blog['last_updated'] == '0000-00-00 00:00:00' ) ? __( 'Never' ) : mysql2date( __( $date ), $blog['last_updated'] ); ?>
								</td>
							<?php
							break;
						case 'registered': ?>
								<td valign="top">
								<?php
								if ( $blog['registered'] == '0000-00-00 00:00:00' )
									echo '&#x2014;';
								else
									echo mysql2date( __( $date ), $blog['registered'] );
								?>
								</td>
						<?php
						break;
							case 'users': ?>
								<td valign="top">
									<?php
									$blogusers = get_users_of_blog( $blog['blog_id'] );
									if ( is_array( $blogusers ) ) {
										$blogusers_warning = '';
										if ( count( $blogusers ) > 5 ) {
											$blogusers = array_slice( $blogusers, 0, 5 );
											$blogusers_warning = __( 'Only showing first 5 users.' ) . ' <a href="' . esc_url( get_admin_url( $blog['blog_id'], 'users.php' ) ) . '">' . __( 'More' ) . '</a>';
										}
										foreach ( $blogusers as $key => $val ) {
											echo '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $val->user_id ) ) . '">' . esc_html( $val->user_login ) . '</a> ';
											if ( 'list' != $mode )
												echo '(' . $val->user_email . ')';
											echo '<br />';
										}
										if ( $blogusers_warning != '' )
											echo '<strong>' . $blogusers_warning . '</strong><br />';
									}
									?>
								</td>
							<?php
							break;

							case 'plugins': ?>
								<?php if ( has_filter( 'wpmublogsaction' ) ) { ?>
								<td valign="top">
									<?php do_action( 'wpmublogsaction', $blog['blog_id'] ); ?>
								</td>
								<?php } ?>
							<?php break;

							default: ?>
								<?php if ( has_filter( 'manage_blogs_custom_column' ) ) { ?>
								<td valign="top">
									<?php do_action( 'manage_blogs_custom_column', $column_name, $blog['blog_id'] ); ?>
								</td>
								<?php } ?>
							<?php break;
						}
					}
					?>
					</tr>
					<?php
				}
			} else { ?>
				<tr>
					<td colspan="<?php echo (int) count( $sites_columns ); ?>"><?php _e( 'No sites found.' ) ?></td>
				</tr>
			<?php
			} // end if ($blogs)
			?>

	</tbody>
</table>