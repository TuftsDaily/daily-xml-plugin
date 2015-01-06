<?php
/*
Plugin Name: Tufts Daily Export to InDesign
Plugin URI: https://github.com/TuftsDaily/daily-xml-plugin
Description: Export a post as XML for import to InDesign
Author: Andrew Stephens
Author URI: http://andrewmediaprod.com/
Version: 3.0
*/

/* Adds a box to the main column on the Post and Page edit screens */
add_action('admin_init', 'tdaily_export_html_box', 1);
function tdaily_export_html_box() {
	add_meta_box( 'tdaily_export_html', 'InDesign Options', 'tdaily_export_html_box_inner', 'post', 'side','high' );
}

/* Prints the box content */
function tdaily_export_html_box_inner($post, $metabox) {
?>

<div class="inside" style="margin:0;padding:6px 0">

	<a class="button" href="<?php bloginfo('url'); ?>/?p=<?php echo $post->ID; ?>&amp;preview_id=<?php echo $post->ID; ?>&amp;InDesign=download" style="margin-right: 10px;">Download XML</a>

</div>

<?php
}

add_action('single_template', 'tdaily_export_html');
function tdaily_export_html($single_template) {
	if (isSet($_GET['InDesign']) && $_GET['InDesign'] == 'download') {
		return dirname( __FILE__ ) .'/xml-gen.php';
	} else {
		return $single_template;
	}
}
