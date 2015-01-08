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
