<?php
/*
Plugin Name: Tufts Daily Export to InDesign
Plugin URI: https://github.com/TuftsDaily/daily-xml-plugin
Description: Export a post as XML for import to InDesign
Author: Andrew Stephens
Author URI: http://andrewmediaprod.com/
Version: 3.0
*/

// Create Meta Box for Post Edit Screens
add_action('admin_init', 'tdaily_export_html_box', 1);
function tdaily_export_html_box() {
	add_meta_box( 'tdaily_export_html', 'InDesign Options', 'tdaily_export_html_box_inner', 'post', 'side','high' );
}

// Box Content
function tdaily_export_html_box_inner($post, $metabox) {
?>

<div class="inside" style="margin:0;padding:6px 0">
	<a class="button" href="<?php bloginfo('url'); ?>/?p=<?php echo $post->ID; ?>&amp;preview_id=<?php echo $post->ID; ?>&amp;InDesign=download" style="margin-right: 10px;">Download XML</a>
</div>

<?php
}

// Add XML Download Action to Post List Screen
$xml_callback = function($actions) {
	global $post;
	$actions[] = '<a href="'.get_bloginfo('url').'/?p='.$post->ID.'&amp;preview_id='.$post->ID.'&amp;InDesign=download" title="Download XML for InDesign" target="_blank">XML</a>';
	return $actions;
};
add_filter('post_row_actions', $xml_callback);

// Call the XML Generator when Loaded
add_action('single_template', 'tdaily_export_html');
function tdaily_export_html($single_template) {
	if (isSet($_GET['InDesign']) && $_GET['InDesign'] == 'download') {
		return dirname( __FILE__ ) .'/xml-gen.php';
	} else {
		return $single_template;
	}
}



// Additional User Post Meta Fields
add_action( 'show_user_profile', 'td_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'td_show_extra_profile_fields' );
function td_show_extra_profile_fields( $user ) { ?>

	<h3>Print Information</h3>

	<table class="form-table">

		<tr>
			<th><label for="daily-rank">Rank</label></th>

			<td>
				<input type="text" name="daily-rank" id="daily-rank" value="<?php echo esc_attr( get_the_author_meta( 'daily-rank', $user->ID ) ); ?>" class="regular-text" /><br />
				<span class="description">Something like "Executive News Editor", "Arts Editor", or "Assistant Features Editor".</span>
			</td>
		</tr>

	</table>
<?php }


add_action( 'personal_options_update', 'td_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'td_save_extra_profile_fields' );
function td_save_extra_profile_fields( $user_id ) {

	if (!current_user_can('edit_user', $user_id)) {
		return false;
	}

	update_user_meta( $user_id, 'daily-rank', $_POST['daily-rank'] );


}


// If User Does Not Have a Bio, Create One
function td_autogen_bio($rank, $user_id) {

	// TODO

}