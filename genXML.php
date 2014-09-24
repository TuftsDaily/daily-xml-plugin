<?php

//define('TDAILY_XML_DEBUG', true);
define('COLUMNS_CATEGORY_ID', 231); // 21 local, 231 production
define('OPINION_CATEGORY_ID', 24); // 24 local, 24 production

// Get Rid of Any Existing Output by WordPress
ob_end_clean();

the_post();
global $post;

// Create an Associate Array of Values to Fill Tags
// Not Everything in the Array Will Necessarily Be Used in the XML
$tagValues = array();

// Headline
$tagValues['headline'] = $post->post_title;

// Author
$author_data = get_userdata($post->post_author);
$tagValues['author'] = $author_data->display_name;
$tagValues['rank'] = tdaily_get_author_rank($author_data);
$tagValues['bio'] = $author_data->description;

// Jump/Continuation
$tagValues['jumpword'] = strtoupper(get_editorial_metadata('jumpword', 'text'));
$tagValues["conthead"] = get_editorial_metadata('cont-head', 'text');
$tagValues["subtitle"] = get_editorial_metadata('subtitle', 'text');

// Photo Caption/Credit from Featured Image, if Exists
$thumbnail_id    = get_post_thumbnail_id($post->ID);
if ($thumbnail_id) {
	$thumbnail_image = get_post($thumbnail_id);
	$tagValues["photocaption"] = $thumbnail_image->post_content;
	$tagValues["photocredit"] = $thumbnail_image->post_excerpt;
}

// Column Thumbnail
$colThumb_author = $author_data->display_name;
$colThumb_title = '';
foreach(wp_get_post_categories($post->ID) as $catId) {
	$cat = get_category($catId);

	$parentId = $cat->category_parent;
	$parentCat = get_category($parentId);
	$grandparentId = $parentCat = $parentCat->category_parent;
	if ($grandparentId == COLUMNS_CATEGORY_ID) {
		$colThumb_title = $cat->cat_name;
	}

}
$tagValues['col-thumbnail'] = $colThumb_author.' | '.$colThumb_title; 

// Section Name for Thumbnail
$thumbName = '';
$sectionName = '';
foreach(wp_get_post_categories($post->ID) as $catId) {
	$cat = get_category($catId);
	
	// If Child
	if ($cat->category_parent != 0) {
		$thumbName = $cat->cat_name;
		$sectionName = get_ancestor_cat_name($catId);
	} else {
		$sectionName = $cat->slug;
	}
}
$tagValues['thumbnail'] = $thumbName;

// Default Filename Value if Section Name Not Found
if (!$sectionName) { $sectionName = "daily"; }

// Post Body
$body = $post->post_content;
$body = str_replace("\r\n\r\n", "\r\n\t", $body); // Replace Double-Newline
$body = str_replace("&nbsp;", " ", $body); // Strip &nbsp;
$body = strip_tags($body, '<strong><em>'); // And Strip Out HTML
$tagValues['body'] = $body;

// Determine the Proper XML Template
$template = 'standard.xml';
if (in_array(COLUMNS_CATEGORY_ID, wp_get_post_categories($post->ID))) { $template = 'column.xml'; }
if (in_array(OPINION_CATEGORY_ID, wp_get_post_categories($post->ID))) { $template = 'oped.xml'; }
if (get_editorial_metadata('is-box', 'checkbox')) { $template = 'box.xml'; }

// Now Actually Load It
$xmlString = file_get_contents($template, true);

// Find and Replace Text Accordingly
foreach($tagValues as $keyword=>$value) {
	$xmlString = str_replace('{'.$keyword.'}', $value, $xmlString);
}
// Remove Any Unused Template Tags
$xmlString = preg_replace("/\{.*\}/", "", $xmlString);


// Make a Filename Out of the Title
$slug = str_replace(' ', '-', strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $post->post_title)));
$filename = $sectionName.'-'.$slug.'-'.$post->ID.'.xml';
if (!defined('TDAILY_XML_DEBUG')) {
	// Force Download
	header('Content-type: binary/text; charset=utf-8');
	header('Content-Disposition: filename='.$filename);
} else {
	var_dump($filename);
}

echo $xmlString;

exit();

/////////////////////////////
// Helper Functions Below
/////////////////////////////

function tdaily_get_author_rank($author_data) {
	if (strpos(strtoupper($author_data->description), "STAFF WRITER")) {
		$rank = "Staff Writer";
	} else if (strpos(strtoupper($author_data->description), "CONTRIBUTING WRITER")) {
		$rank = "Contributing Writer";
	} else if (strpos(strtoupper($author_data->description), "EDITOR")) {
		$rank = "Daily Editorial Board";
	} else {
		$rank = "INSERT RANK HERE";
	}
	return $rank;
}

function get_editorial_metadata($slug, $type) {
    global $post, $edit_flow;

    $postmeta_key = "_ef_editorial_meta_{$type}_$slug";

    $view = get_metadata( 'post', $post->ID, '', true );
    $show_editorial_metadata = $view["{$postmeta_key}"][0];

    if ($type == "date") { $show_editorial_metadata = date("F j, Y", $show_editorial_metadata); }

    return $show_editorial_metadata;
}

function get_ancestor_cat_name($id) {
	$cat = get_category($id);
	if ($cat->category_parent == 0) {
		return $cat->slug;
	} else {
		return get_ancestor_cat_name($cat->category_parent);
	}
}
