<?php

class XMLGen {

	private $COLUMNS_CATEGORY_ID;
	private $OPINION_CATEGORY_ID;
	private $DEBUG = false;

	private $catArray;
	private $tags;
	private $filenameData;
	private $templateFile;

	public function __construct() {

		// Get Rid of Any Existing Output by WordPress
		ob_end_clean();

		// Set Debug Var for Local Env Only
		if ($_SERVER['SERVER_NAME'] != "tuftsdaily.com") {
			$this->DEBUG = true;
		}

		// Save as Class Variable for Shorthand Later
		the_post();

		// Set Category ID Values
		$this->COLUMNS_CATEGORY_ID = get_cat_ID("Columns");
		$this->OPINION_CATEGORY_ID = get_cat_ID("Opinion");
		$this->build_cat_array();

		// Get Tag Data
		$this->tags = array();
		$this->set_title();
		$this->set_author();
		$this->set_pagination();
		$this->set_thumbnail();
		$this->set_photos();
		$this->set_body();

		// Prepare for Output
		$this->set_template();

	}

	public function output() {

		// Now Actually Load It
		$xmlString = file_get_contents($this->templateFile, true);

		// Find and Replace Text Accordingly
		foreach($this->tags as $keyword=>$value) {
			$xmlString = str_replace('{'.$keyword.'}', $value, $xmlString);
		}
		// Remove Any Unused Template Tags
		$xmlString = preg_replace("/\{.*\}/", "", $xmlString);


		// Make a Filename Out of the Title
		$filename = implode('-', $this->filenameData).'.xml';
		if (!$this->DEBUG) {
			// Force Download
			header('Content-type: binary/text; charset=utf-8');
			header('Content-Disposition: filename='.$filename);
		} else {
			var_dump($filename);
		}

		echo $xmlString;

		exit();

	}

	private function set_title() {
		global $post;

		$this->tags['headline'] = $post->post_title;

	}

	private function set_author() {
		global $post;

		// Off-the-Hill Articles Store Author in Editorial Metadata
		if ($this->get_editorial_metadata('off-the-hill', 'checkbox', false)) { 
			$this->tags['author'] = $this->get_editorial_metadata('off-the-hill-author', 'text');
			$this->tags['rank'] = $this->get_editorial_metadata('off-the-hill-university', 'text');
		
		// Otherwise, Stored in Post Data
		} else {

			// Get Using Co-Authors Plus Plugin Functions
			if (function_exists('coauthors')) {

				$shared_rank = null;
				$shared_bio = '';

				// Co-Authors Plus Plugin Provides Author List Formatted
				$this->tags['author'] = coauthors(null, null, null, null, false);

				foreach (get_coauthors() as $author_data) {

					// Ranks Must Match, or Be Edited Manually
					$rank = $this->get_author_rank($author_data->ID);

					var_dump($shared_rank);
					var_dump($rank);
					
					// If Null, This is First Author so Set Rank from That
					if ($shared_rank == null) {
						$shared_rank = $rank;

					// If Current Author's Rank Doesn't Match Previous, Set to Placeholder
					} else if ($shared_rank != $rank) {
						$shared_rank = "AUTHORS HAVE DIFFERENT RANKS";
					}

					// Merge Bios Together
					// With a Space In-Between if Necessary
					if ($shared_bio != '') { $shared_bio .= ' '; }
					$shared_bio .= $author_data->description;	
				}

				// Set Tags Accordingly
				$this->tags['rank'] = $shared_rank;
				$this->tags['bio'] = $shared_bio;

			// Otherwise, Use Built-In WP Functions
			} else {
				$author_data = get_userdata($post->post_author);
				$this->tags['author'] = $author_data->display_name;
				$this->tags['rank'] = $this->get_author_rank($author_data->description);
				$this->tags['bio'] = $author_data->description;
			}

			// Handle Empty Bio Case
			if ($this->tags['bio'] == '') {
				$this->tags['bio'] = "This author does not have a bio set on WordPress. REPLACE THIS BEFORE GOING TO PRINT";
			}
			
		}

	}

	/**
	 * Given an author's bio, returns that author's rank.
	 * 
	 * Based on a standardized bio format of:
	 * John Smith is a/an ____ at the Tufts Daily.
	 * Returns placeholder text if the author does not have a bio
	 *   or the bio does not follow this format.
	 * 
	 * @return string Author's rank text.
	 */
	private function get_author_rank($user_id) {

		$rank = get_user_meta($user_id, 'daily-rank', true);
		if (!$rank) { $rank = "RANK NOT SET ON WEB"; }

		return $rank;

	}

	private function set_pagination() {

		// All Three Pagination Controls are Custom Data Stored in Metadata
		$jumpword = strtoupper($this->get_editorial_metadata('jumpword', 'text'));
		$conthead = $this->get_editorial_metadata('cont-head', 'text');
		$subtitle = $this->get_editorial_metadata('subtitle', 'text');

		// Fill In Defaults For Required Fields
		if (!$jumpword) { $jumpword = 'JUMPWORD'; }
		if (!$conthead) { $conthead = 'CONT HEADLINE GOES HERE'; }

		$this->tags['jumpword'] = $jumpword;
		$this->tags['conthead'] = $conthead;
		$this->tags['subtitle'] = $subtitle;

	}

	private function set_thumbnail() {

		// First, Allow Custom Overrides
		$custom = $this->get_editorial_metadata('thumbnail', 'text');
		if ($custom != '') {
			$this->tags['thumbnail'] = $custom;
		}

		// If It's a Column, Save the Column Title
		if ($this->has_category($this->COLUMNS_CATEGORY_ID)) {
			$col_title = $this->get_cat_name_at_lvl(2);
			// Handle Miscategorization
			if (!$col_title) { $col_title = "COLUMN NAME HERE"; }
			$this->tags['col-title'] = $col_title;
		}

		// Otherwise, Get Level 2 Category
		$lvl2cat = $this->get_cat_name_at_lvl(1);
		$this->tags['thumbnail'] = wptexturize($lvl2cat);

		// Special Case for Opinion Articles: Should Have Thumbnail "Op-Ed"
		if ($this->has_category($this->OPINION_CATEGORY_ID) && $this->tags['thumbnail'] == '') {
			$this->tags['thumbnail'] = "Op-Ed";
		}

	}

	private function set_photos() {
		global $post;

		$thumbnail_id    = get_post_thumbnail_id($post->ID);
		if ($thumbnail_id) {
			$thumbnail_image = get_post($thumbnail_id);
			//photocaption
			$photocaption = $thumbnail_image->post_content;
			$photocaption = wptexturize($photocaption);
			$this->tags["photocaption"] = $photocaption;
			//photocredit
			$photocredit = $thumbnail_image->post_excerpt;
			$photocredit = wptexturize($photocredit);
			$this->tags["photocredit"] = $photocredit;
		}

	}

	private function set_body() {
		global $post;

		$body = $post->post_content;
		$body = "\t" . $body; // Add a leading indent for first paragraph
		$body = str_replace("\r\n\r\n", "\r\n\t", $body); // Replace Double-Newline
		$body = str_replace("&nbsp;", " ", $body); // Strip &nbsp;
		$body = strip_tags($body, '<strong><em>'); // And Strip Out HTML
		$body = wptexturize($body); // Fix quotations and other encoding
		$this->tags['body'] = $body;

	}

	private function set_template() {
		global $post;

		$this->filenameData = array();

		// Filename Has First Two Levels of Categories, if Exist
		$this->filenameData[] = $this->get_cat_name_at_lvl(0);
		$sectionSubcat = $this->get_cat_name_at_lvl(1);
		if ($sectionSubcat) {
			$this->filenameData[] = $sectionSubcat;
		}

		// Use Standard XML Template by Default
		// Then Override in Specific Situations
		$this->templateFile = 'standard.xml';
		if ($this->has_category($this->COLUMNS_CATEGORY_ID)) { 
			$this->templateFile = 'column.xml'; 
		} else if ($this->has_category($this->OPINION_CATEGORY_ID)) { 
			$this->templateFile = 'oped.xml'; 
		} else if ($this->get_editorial_metadata('is-box', 'checkbox', false)) { 
			$this->templateFile = 'box.xml'; 
		} else if ($this->get_editorial_metadata('off-the-hill', 'checkbox', false)) { 
			$this->templateFile = 'off-the-hill.xml';
			$this->filenameData = array('opinion', 'off the hill');
		}

		// Put Title and ID in Filename Too
		$this->filenameData[] = $post->post_title;
		$this->filenameData[] = $post->ID;

		// Filter Data in Filename to Prevent Download Erors
		$stripNonAlphaNum = function($el) {
			return str_replace(' ', '-', strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $el)));
		};
		$this->filenameData = array_map($stripNonAlphaNum, $this->filenameData);

		$stripEmptyStr = function($el) {
			return $el != '';
		};
		$this->filenameData = array_filter($this->filenameData, $stripEmptyStr);

	}

	private function get_editorial_metadata($slug, $type, $texturize=true) {
		global $post, $edit_flow;

		$postmeta_key = "_ef_editorial_meta_{$type}_$slug";

		$view = get_metadata( 'post', $post->ID, '', true );
		$show_editorial_metadata = $view["{$postmeta_key}"][0];

		if ($type == "date") { $show_editorial_metadata = date("F j, Y", $show_editorial_metadata); }

		if ($texturize) {
			return wptexturize($show_editorial_metadata);
		} else {
			return $show_editorial_metadata;
		}
	}

	private function has_category($checkCatId) {
		global $post;

		$cats = wp_get_post_categories($post->ID);
		return in_array($checkCatId, $cats);

	}

	private function build_cat_array() {
		global $post;

		foreach(wp_get_post_categories($post->ID) as $catId) {

			$cat = get_category($catId);
			$lvl = $this->get_cat_lvl($catId);
			if (!isSet($this->catArray[$lvl])) {
				$this->catArray[$lvl] = array();
			}
			$this->catArray[$lvl][] = $cat;

		}

	}

	/**
	 * Determines a category's depth position in a hierarchy.
	 *
	 * @param  integer Category ID being queried.
	 * @param  integer Level counter, used internally for recursion.
	 * @return integer Depth level of queried hierarchy. 
	 */
	private function get_cat_lvl($catId, $count=0) {
		global $post;

		$c = get_category($catId);
		if ($c->category_parent == 0) {
			return $count;
		} else {
			return $this->get_cat_lvl($c->category_parent, $count+1);
		}

	}

	/**
	 * Get category name at a given level.
	 *
	 * Given an hierarchical array of categories, get the category name at the
	 * specified level. If there are multiple categories at the level, default
	 * to the first entry.
	 * 
	 * @param  integer Level number of desired category, zero-indexed.
	 * @param  integer Which category to return, if specificity is needed.
	 * @return string Name of category that matches given criteria.
	 */
	private function get_cat_name_at_lvl($lvl, $which=0) {
		return $this->catArray[$lvl][$which]->name;
	}
	
}

$tdaily_xml = new XMLGen();
$tdaily_xml->output();