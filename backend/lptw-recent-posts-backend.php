<?php

/* backend ajax */
add_action('admin_footer', 'my_action_javascript'); // Write our JS below here
function my_action_javascript() {
?>
<script type="text/javascript" >
	jQuery(document).ready(function($) {

		$('#post_type').change(function() {
			var selected_post_type = $(this).val();
			var data = {
				'action': 'get_terms_list',
				'selected_post_type': selected_post_type
			};
			$.post(ajaxurl, data, function(response) {
				$('.lptw-list-categories').html(response);
			});
		});
	});
</script>
<?php
}

add_action('wp_ajax_get_terms_list', 'get_terms_list_callback');
function get_terms_list_callback() {
	global $wpdb;

	$selected_post_type = $_POST['selected_post_type'];

	if ($_POST['widget_name']) {
		$widget_option = get_option('widget_'.$_POST['widget_name']);
		$post_category = $widget_option[$_POST['instance_id']]['post_category'];
	}

	$taxonomies = get_object_taxonomies($selected_post_type);
	if (!empty($taxonomies)) {
		$categories_content = '';
		foreach ($taxonomies as $taxonomy) {
			$args = array(
				'taxonomy' => $taxonomy,
				'orderby' => 'name',
				'show_count' => 0,
				'pad_counts' => 0,
				'hierarchical' => 1,
				'hide_empty' => 0
			);
			$categories = get_categories($args);
			foreach ($categories as $category) {
				if (!empty($post_category)) {
					if (is_array($post_category) && in_array($category->term_id, $post_category)) { $checked = 'checked="checked"'; }
					else { $checked = ''; }
				} else { $checked = ''; }
				$categories_content .= '<li id="category-' . $category->term_id . '"><label class="selectit"><input type="checkbox" id="in-category-' . $category->term_id . '" name="post_category[]" value="' . $category->term_id . '" '.$checked.'> ' . $category->name . '</label></li>' . "\n";
			}
		}
	} else { $categories_content = 'No taxonomies for selected post type'; }

	echo $categories_content;
	wp_die();
}

function lptw_concat_attrs($attrs){
	$str = "";
	foreach($attrs as $key => $value)
		$str .= ' '.$key.'="'.$value.'"';
	return $str;
}
function lptw_select($name, $options){
	$strret = '<select name="'.$name.'"';
	if(!isset($options["attributes"]))
		$options["attributes"] = array();
	if(!isset($options["attributes"]["id"]))
		$options["attributes"]["id"] = $name;

	$strret .= lptw_concat_attrs($options["attributes"]);
	if(isset($options["multiple"]) && $options["multiple"])
		$strret .= " multiple";
	$strret .= ">";

	if(isset($options["options"]) && is_array($options["options"])){
		if(array_values($options["options"]) === $options["options"]){
			foreach($options["options"] as $value){
				$strret .= '<option value="'.$value.'">'.$value.'</option>';
			}
		} else {
			foreach($options["options"] as $key => $value){
				$strret .= '<option value="'.$key.'"';
				if(isset($options["selected"]) && ((is_array($options["selected"]) && in_array($key, $options["selected"])) || (!is_array($options["selected"]) && $options["selected"] == $key)))
					$strret .= ' selected="selected"';
				$strret .= '>';
				if(is_array($value)){
					if(isset($value["text"]) && $value["text"]){
						$strret .= $value["text"];
					} else {
						$strret .= $key;
					}
				} else {
					$strret .= $value;
				}
				$strret .= '</option>';
			}
		}
	}

	$strret .= "</select>";
	return $strret;
}
function lptw_check($name, $options){
	$ret = array();
	if(!isset($options["options"]))
		return $ret;

	if(!is_array($options["options"]))
		$options["options"] = array($options["options"]);

	foreach($options["options"] as $option => $data){
		$str = '<input name="'.$name.'"';
		if(isset($options["radio"]) && $options["radio"])
			$str .= ' type="radio"';
		else
			$str .= ' type="checkbox"';
		$str .= ' value="'.$option.'"';
		if(!isset($data["attributes"]))
			$data["attributes"] = array();
		if(!isset($data["attributes"]["id"]))
			$data["attributes"]["id"] = $option;

		$str .= lptw_concat_attrs($data["attributes"]);
		if(isset($options["selected"]) && ((is_array($options["selected"]) && in_array($option, $options["selected"])) || (!is_array($options["selected"]) && $options["selected"] == $option)))
			$str .= ' checked="checked"';
		$str .= '/>';

		$ret[$option] = $str;
	}
	return $ret;
}
function lptw_color($name, $value){

}
function lptw_text($name, $options){
	$str = '<input name="'.$name.'"';
	if(isset($options["type"]))
		$str .= ' type="'.$options["type"].'"';
	if(isset($options["value"]) && $options["value"] !== NULL && trim($options["value"]) != "")
		$str .= ' value="'.$options["value"].'"';

	if(!isset($options["attributes"]))
		$options["attributes"] = array();
	if(!isset($options["attributes"]["id"]))
		$options["attributes"]["id"] = $name;

	$str .= lptw_concat_attrs($options["attributes"]);
	$str .= '/>';
	return $str;
}

function formatSelectAuthor($authors){
	$ret = array();
	foreach ($authors as $author) {
		$author_name;
		if ( $author->first_name && $author->last_name ) {
			$author_name = ' ('.$author->first_name.' '.$author->last_name.')';
		} else {
			$author_name = '';
		}
		$ret[$author->ID] = $author->user_nicename.$author_name;
	}
	return $ret;
}

function formatSelectTags($tags){
	$ret = array();
	foreach ($tags as $tag) {
		$ret[$tag->term_id] = $tag->name;
	}
	return $ret;
}

function lptw_recent_posts_manage_shortcodes() {
	$defaults = array(
		"sb_layout" => "basic",
		"no_thumbnails" => NULL,

		"sb_columns" => "2",
		"sb_space_hor" => "10",
		"sb_space_ver" => "10",
		"sb_fluid_images" => "0",
		"sb_width" => "300",
		"sb_height" => "400",
		"sb_featured_height" => "400",
		"sb_min_height" => "0",

		"post_type" => NULL,

		"link_target" => NULL,

		"authors" => NULL,
		"tags" => NULL,
		"tags_exclude" => NULL,

		"post_category" => NULL,
		"same_category" => NULL,
		"orderby" => "date",
		"order" => "DESC",

		"posts_per_page" => $default_posts_per_page,
		"post_offset" => 0,
		"reverse_post_order" => NULL,
		"exclude_current_post" => NULL,

		"thumbnail_size" => "medium",

		"color_scheme" => "dark",
		"dropcap-background-color" => "#4CAF50",
		"dropcap-text-color" => "#ffffff",
		"override_colors" => NULL,

		"excerpt_show" => "0",
		"excerpt_length" => 35,
		"ignore_more_tag" => NULL,
		"read_more_show" => NULL,
		"read_more_inline" => NULL,
		"read_more_content" => "Read more &rarr;",

		"show_date_before_title" => "0",
		"show_date" => "0",
		"show_time" => "0",
		"show_time_before" => "0",
		"show_subtitle" => "0",

		"sb_date_format" => "d.m.Y",
		"sb_time_format" => "H:i"
	);
	
	if(isset($_POST['action']) && $_POST['action'] == "savedefault"){
		// Save options
		$data = $_POST;
		unset($data["action"]);
		foreach($defaults as $key => $value){
			if(!isset($data[$key]))
				$data[$key] = NULL;
		}
		update_option("lptw_recent_posts", $data);
	}
	$default_posts_per_page = intval(get_option('posts_per_page', '10'));

	$defaults = array_merge($defaults, get_option("lptw_recent_posts", $defaults));
	$formFields = array(
		"sb_layout" => lptw_check(
			"sb_layout",
			array(
				"radio" => true,
				"selected" => $defaults["sb_layout"],
				"options" => array(
					"basic" => array(
						"attributes" => array(
							"id" => "layout-basic",
							"class" => "layout-radio"
						)
					),
					"thumbnail" => array(
						"attributes" => array(
							"id" => "layout-thumbnail",
							"class" => "layout-radio"
						)
					),
					"dropcap" => array(
						"attributes" => array(
							"id" => "layout-dropcap",
							"class" => "layout-radio"
						)
					),
					"grid-medium" => array(
						"attributes" => array(
							"id" => "layout-grid-medium",
							"class" => "layout-radio"
						)
					)
				)
			)
		),
		"no_thumbnails" => lptw_check(
			"no_thumbnails",
			array(
				"radio" => false,
				"selected" => $defaults["no_thumbnails"],
				"options" => array(
					"hide" => array(
						"attributes" => array(
							"id" => "no_thumbnails"
						)
					),
				)
			)
		),
		"sb_columns" => lptw_text(
			"sb_columns",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-show",
					"min" => "1",
					"step" => "1",
					"max" => "12"
				),
				"value" => $defaults["sb_columns"]
			)
		),
		"sb_space_hor" => lptw_text(
			"sb_space_hor",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-show",
					"min" => "1",
					"step" => "1"
				),
				"value" => $defaults["sb_space_hor"]
			)
		),
		"sb_space_ver" => lptw_text(
			"sb_space_ver",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-show",
					"min" => "1",
					"step" => "1"
				),
				"value" => $defaults["sb_space_ver"]
			)
		),
		"sb_fluid_images" => lptw_check(
			"sb_fluid_images",
			array(
				"radio" => false,
				"selected" => $defaults["sb_fluid_images"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "sb_fluid_images",
							"class" => "layout-basic-show layout-grid-show layout-thumbnail-hide layout-dropcap-hide"
						)
					),
				)
			)
		),
		"sb_width" => lptw_text(
			"sb_width",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-show",
					"min" => "1",
					"step" => "1",
					"disabled" => "disabled"
				),
				"value" => $defaults["sb_width"]
			)
		),
		"sb_height" => lptw_text(
			"sb_height",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-show",
					"min" => "1",
					"step" => "1",
					"disabled" => "disabled"
				),
				"value" => $defaults["sb_height"]
			)
		),
		"sb_featured_height" => lptw_text(
			"sb_featured_height",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-show",
					"min" => "1",
					"step" => "1",
					"disabled" => "disabled"
				),
				"value" => $defaults["sb_featured_height"]
			)
		),
		"sb_min_height" => lptw_text(
			"sb_min_height",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-show",
					"min" => "0",
					"step" => "1",
					"disabled" => "disabled"
				),
				"value" => $defaults["sb_min_height"]
			)
		),
		"post_type" => lptw_select(
			"post_type",
			array(
				"selected" => $defaults["post_type"],
				"options" => get_post_types('', 'names')
			)
		),
		"link_target" => lptw_select(
			"link_target",
			array(
				"selected" => $defaults["link_target"],
				"options" => array(
					"self" => __("This window", "lptw_recent_posts"),
					"new" => __("New window", "lptw_recent_posts"),
				)
			)
		),
		"authors" => lptw_select(
			"authors",
			array(
				"selected" => $defaults["authors"],
				"multiple" => true,
				"options" => formatSelectAuthor(
					get_users(
						array(
							'who'          => 'authors'
						)
					)
				),
				"attributes" => array(
					"class" => "chosen-select",
					"data-placeholder" => __("Select one or more authors", "lptw_recent_posts")
				)
			)
		),
		"tags" => lptw_select(
			"tags",
			array(
				"selected" => $defaults["tags"],
				"multiple" => true,
				"options" => formatSelectAuthor(
					get_tags()
				),
				"attributes" => array(
					"class" => "chosen-select",
					"data-placeholder" => __("Select one or more post tags", "lptw_recent_posts")
				)
			)
		),
		"tags_exclude" => lptw_check(
			"tags_exclude",
			array(
				"radio" => false,
				"selected" => $defaults["tags_exclude"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "tags_exclude",
							"class" => "layout-basic-show layout-grid-show layout-thumbnail-hide layout-dropcap-hide"
						)
					),
				)
			)
		),
		"same_category" => lptw_check(
			"same_category",
			array(
				"radio" => false,
				"selected" => $defaults["same_category"],
				"options" => array(
					"-1" => array(
						"attributes" => array(
							"id" => "same_category",
							"class" => "layout-basic-show layout-grid-show layout-thumbnail-hide layout-dropcap-hide"
						)
					),
				)
			)
		),
		"orderby" => lptw_select(
			"orderby",
			array(
				"selected" => $defaults["orderby"],
				"options" => array(
					"none"			=> __("None", "lptw_recent_posts"),
					"title"			=> __("Title", "lptw_recent_posts"),
					"name"			=> __("Name (post slug)", "lptw_recent_posts"),
					"date"			=> __("Date created", "lptw_recent_posts"),
					"modified"		=> __("Date modified", "lptw_recent_posts"),
					"rand"			=> __("Random", "lptw_recent_posts"),
					"comment_count"	=> __("Number of comments", "lptw_recent_posts"),
				),
				"attributes" => array(
					"class" => "layout-basic-show layout-dropcap-show layout-grid-show layout-thumbnail-show",
				)
			)
		),
		"order" => lptw_select(
			"order",
			array(
				"selected" => $defaults["order"],
				"options" => array(
					"ASC"			=> __("Ascending order from lowest to highest values", "lptw_recent_posts"),
					"DESC"			=> __("Descending order from highest to lowest values", "lptw_recent_posts"),
				),
				"attributes" => array(
					"class" => "layout-basic-show layout-dropcap-show layout-grid-show layout-thumbnail-show",
				)
			)
		),
		"posts_per_page" => lptw_text(
			"posts_per_page",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text",
					"min" => "1",
					"step" => "1"
				),
				"value" => $defaults["posts_per_page"]
			)
		),
		"post_offset" => lptw_text(
			"post_offset",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text",
					"min" => "0",
					"step" => "1",
				),
				"value" => $defaults["post_offset"]
			)
		),
		"reverse_post_order" => lptw_check(
			"reverse_post_order",
			array(
				"radio" => false,
				"selected" => $defaults["reverse_post_order"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "reverse_post_order",
						)
					),
				)
			)
		),
		"exclude_current_post" => lptw_check(
			"exclude_current_post",
			array(
				"radio" => false,
				"selected" => $defaults["exclude_current_post"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "exclude_current_post",
						)
					),
				)
			)
		),
		"thumbnail_size" => lptw_select(
			"thumbnail_size",
			array(
				"selected" => $defaults["thumbnail_size"],
				"options" => array(
					"thumbnail"	=> __("Thumbnail", "lptw_recent_posts"),
					"medium"	=> __("Medium", "lptw_recent_posts"),
					"large"		=> __("Large", "lptw_recent_posts"),
					"full"	=> __("Full", "lptw_recent_posts"),
				),
				"attributes" => array(
					"class" => "layout-basic-show layout-dropcap-hide layout-grid-hide layout-thumbnail-hide",
				)
			)
		),
		"color_scheme" => lptw_select(
			"color_scheme",
			array(
				"selected" => $defaults["color_scheme"],
				"options" => array(
					"no-overlay"	=> __("Without overlay", "lptw_recent_posts"),
					"light"			=> __("Light", "lptw_recent_posts"),
					"dark"			=> __("Dark", "lptw_recent_posts"),
				),
				"attributes" => array(
					"class" => "layout-basic-show layout-dropcap-hide layout-grid-hide layout-thumbnail-hide",
				)
			)
		),
		"dropcap-background-color" => lptw_text(
			"dropcap-background-color",
			array(
				"attributes" => array(
					"class" => "color-field",
					"data-default-color" => $defaults["dropcap-background-color"]
				),
				"value" => $defaults["dropcap-background-color"]
			)
		),
		"dropcap-text-color" => lptw_text(
			"dropcap-text-color",
			array(
				"attributes" => array(
					"class" => "color-field",
					"data-default-color" => $defaults["dropcap-text-color"]
				),
				"value" => $defaults["dropcap-text-color"]
			)
		),
		"override_colors" => lptw_check(
			"override_colors",
			array(
				"radio" => false,
				"selected" => $defaults["override_colors"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "override_colors",
						)
					),
				)
			)
		),
		"excerpt_show" => lptw_check(
			"excerpt_show",
			array(
				"radio" => false,
				"selected" => $defaults["excerpt_show"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "excerpt_show",
						)
					),
				)
			)
		),
		"excerpt_length" => lptw_text(
			"excerpt_length",
			array(
				"type" => "number",
				"attributes" => array(
					"class" => "small-text",
				),
				"value" => $defaults["excerpt_length"]
			)
		),
		"ignore_more_tag" => lptw_check(
			"ignore_more_tag",
			array(
				"radio" => false,
				"selected" => $defaults["ignore_more_tag"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "ignore_more_tag",
						)
					),
				)
			)
		),
		"read_more_show" => lptw_check(
			"read_more_show",
			array(
				"radio" => false,
				"selected" => $defaults["read_more_show"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "read_more_show",
						)
					),
				)
			)
		),
		"read_more_inline" => lptw_check(
			"read_more_inline",
			array(
				"radio" => false,
				"selected" => $defaults["read_more_inline"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "read_more_inline",
						)
					),
				)
			)
		),
		"ignore_more_tag" => lptw_check(
			"ignore_more_tag",
			array(
				"radio" => false,
				"selected" => $defaults["ignore_more_tag"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "ignore_more_tag",
						)
					),
				)
			)
		),
		"read_more_content" => lptw_text(
			"read_more_content",
			array(
				"value" => $defaults["read_more_content"]
			)
		),
		"show_date_before_title" => lptw_check(
			"show_date_before_title",
			array(
				"radio" => false,
				"selected" => $defaults["show_date_before_title"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "show_date_before_title",
						)
					),
				)
			)
		),
		"show_date" => lptw_check(
			"show_date",
			array(
				"radio" => false,
				"selected" => $defaults["show_date"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "show_date",
						)
					),
				)
			)
		),
		"show_time" => lptw_check(
			"show_time",
			array(
				"radio" => false,
				"selected" => $defaults["show_time"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "show_time",
						)
					),
				)
			)
		),
		"show_time_before" => lptw_check(
			"show_time_before",
			array(
				"radio" => false,
				"selected" => $defaults["show_time_before"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "show_time_before",
						)
					),
				)
			)
		),
		"show_subtitle" => lptw_check(
			"show_subtitle",
			array(
				"radio" => false,
				"selected" => $defaults["show_subtitle"],
				"options" => array(
					"0" => array(
						"attributes" => array(
							"id" => "show_subtitle",
						)
					),
				)
			)
		),
		"sb_date_format" => lptw_check(
			"sb_date_format",
			array(
				"radio" => true,
				"selected" => $defaults["sb_date_format"],
				"options" => array(
					"d.m.Y" => array(
						"attributes" => array(
							"id" => "d.m.Y",
						)
					),
					"m/d/Y" => array(
						"attributes" => array(
							"id" => "m/d/Y",
						)
					),
					"d/m/Y" => array(
						"attributes" => array(
							"id" => "d.m.Y",
						)
					),
					"F j, Y" => array(
						"attributes" => array(
							"id" => "d.m.Y",
						)
					),
					"M j, Y" => array(
						"attributes" => array(
							"id" => "d.m.Y",
						)
					),
				)
			)
		),
		"sb_time_format" => lptw_check(
			"sb_time_format",
			array(
				"radio" => true,
				"selected" => $defaults["sb_time_format"],
				"options" => array(
					"H:i" => array(
						"attributes" => array(
							"id" => "H:i",
						)
					),
					"H:i:s" => array(
						"attributes" => array(
							"id" => "H:i:s",
						)
					),
					"g:i a" => array(
						"attributes" => array(
							"id" => "g:i a",
						)
					),
					"g:i:s a" => array(
						"attributes" => array(
							"id" => "g:i:s a",
						)
					),
				)
			)
		),
	);

	/*	echo "<pre>";
	echo htmlspecialchars(var_export($formFields, true));
	echo "</pre>";*/
?>
<div class="wrap">
	<h2><?php _e("Advanced Recent Posts Shortcode Builder", "lptw_recent_posts"); ?></h2>
	<form method="post" action="">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e("Layouts", "lptw_recent_posts"); ?>:&nbsp;</th>
					<td id="layouts">
						<fieldset id="layout-types" class="layout-list">
							<ul>
								<li>
									<label for="layout-basic"><?php echo $formFields["sb_layout"]["basic"]; ?>&nbsp;<?php _e("Basic", "lptw_recent_posts"); ?></label>&nbsp;&nbsp;
									<a class="demo-link" href="http://demo.lp-tricks.com/recent-posts/basic-layout/" target="_blank"><span class="dashicons dashicons-admin-links"></span>&nbsp;<span class="demo"><?php _e("View demo (external link)", "lptw_recent_posts"); ?></span></a>
								</li>
								<li>
									<label for="layout-thumbnail"><?php echo $formFields["sb_layout"]["thumbnail"]; ?>&nbsp;<?php _e("Thumbnail", "lptw_recent_posts"); ?></label>&nbsp;&nbsp;
									<a class="demo-link" href="http://demo.lp-tricks.com/recent-posts/thumbnail-layout/" target="_blank"><span class="dashicons dashicons-admin-links"></span>&nbsp;<span class="demo"><?php _e("View demo (external link)", "lptw_recent_posts"); ?></span></a>
								</li>
								<li>
									<label for="layout-dropcap"><?php echo $formFields["sb_layout"]["dropcap"]; ?>&nbsp;<?php _e("Drop Cap", "lptw_recent_posts"); ?></label>&nbsp;&nbsp;
									<a class="demo-link" href="http://demo.lp-tricks.com/recent-posts/drop-cap-layout/" target="_blank"><span class="dashicons dashicons-admin-links"></span>&nbsp;<span class="demo"><?php _e("View demo (external link)", "lptw_recent_posts"); ?></span></a>
								</li>
								<li>
									<label for="layout-grid-medium"><?php echo $formFields["sb_layout"]["grid-medium"]; ?>&nbsp;<?php _e("Responsive Grid", "lptw_recent_posts"); ?></label>&nbsp;&nbsp;
									<a class="demo-link" href="http://demo.lp-tricks.com/recent-posts/responsive-grid-dark/" target="_blank"><span class="dashicons dashicons-admin-links"></span>&nbsp;<span class="demo"><?php _e("View demo (external link)", "lptw_recent_posts"); ?></span></a>
								</li>
							</ul>
						</fieldset>
						<label for="no_thumbnails"><?php echo $formFields["no_thumbnails"]["hide"]; ?>
							<?php _e("Do not display Posts without Featured Image.", "lptw_recent_posts"); ?></label>
					</td>
				</tr>
				<tr id="columns_and_width">
					<th scope="row"><?php _e("Columns and dimensions", "lptw_recent_posts"); ?>:&nbsp;</th>
					<td>
						<div class="lptw-sb-row">
							<legend class="screen-reader-text"><span><?php _e("Adaptive layout ", "lptw_recent_posts"); ?></span></legend>
							<label for="sb_columns"><?php echo $formFields["sb_columns"]; ?>
								<?php _e("Number of columns.", "lptw_recent_posts"); ?></label>
						</div>
						<div class="lptw-sb-row">
							<?php _e("Space beetween columns", "lptw_recent_posts"); ?>:&nbsp;&nbsp;&nbsp;
							<label for="sb_space_hor"><?php echo $formFields["sb_space_hor"]; ?>
								<?php _e("Horizontal.", "lptw_recent_posts"); ?></label>
							<label for="sb_space_ver"><?php echo $formFields["sb_space_ver"]; ?>
								<?php _e("Vertical.", "lptw_recent_posts"); ?></label>
						</div>
						<div class="lptw-sb-row">
							<label for="sb_fluid_images"><?php echo $formFields["sb_fluid_images"]["0"]; ?>
								<?php _e("The width of the image adapts to the width of the container.", "lptw_recent_posts"); ?></label>
						</div>
						<div class="lptw-sb-row">
							<label for="sb_width"><?php echo $formFields["sb_width"]; ?>
								<?php _e("The width of the column in pixels, if not already selected adaptive layout.", "lptw_recent_posts"); ?></label>
						</div>
						<div class="lptw-sb-row">
							<label for="sb_height"><?php echo $formFields["sb_height"]; ?>
								<?php _e("The fixed height of the Post in pixels, only for Responsive Grid. If value = 0, height of all Featured set to auto height.", "lptw_recent_posts"); ?></label>
						</div>
						<div class="lptw-sb-row">
							<label for="sb_featured_height"><?php echo $formFields["sb_featured_height"]; ?>
								<?php _e("The fixed height of the <b><u>Featured</u></b> Post in pixels, only for Responsive Grid. If value = 0, height of all <b><u>Featured</u></b> Posts set to 400 px.", "lptw_recent_posts"); ?></label>
						</div>
						<div class="lptw-sb-row">
							<label for="sb_min_height"><?php echo $formFields["sb_min_height"]; ?>
								<?php _e("The minimal height of all Posts in pixels, only for Responsive Grid. If value = 0, minimal height is not limited.", "lptw_recent_posts"); ?></label>
						</div>
					</td>
				</tr>
				<tr id="post_types">
					<th scope="row"><label for="post_type"><?php _e("Post type", "lptw_recent_posts"); ?>:&nbsp;</label></th>
					<td><?php echo $formFields["post_type"]; ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="link_target"><?php _e("Post link", "lptw_recent_posts"); ?>:&nbsp;</label></th>
					<td>
						<?php echo $formFields["link_target"]; ?>
						<p class="description"><?php _e("Open link in a this or new window.", "lptw_recent_posts"); ?></p>
					</td>
				</tr>
				<tr id="post_authors">
					<th scope="row"><label for="authors"><?php _e("Authors", "lptw_recent_posts"); ?>:&nbsp;</label></th>
					<td>
						<?php echo $formFields["authors"]; ?>
						<p class="description"><?php _e("If none of authors is selected - will be displayed posts of all authors.", "lptw_recent_posts"); ?></p>
					</td>
				</tr>
				<tr id="post_tags">
					<th scope="row"><label for="tags"><?php _e("Tags", "lptw_recent_posts"); ?>:&nbsp;</label><p class="description"><?php _e("Now only work with posts.", "lptw_recent_posts"); ?></p></th>
					<td>
						<?php echo $formFields["tags"]; ?>
						<p class="description"><?php _e("If none of tags is selected - will be displayed posts with tags and without tags.", "lptw_recent_posts"); ?></p>
						<p>
							<label for="tags_exclude">
								<?php echo $formFields["tags_exclude"]["0"]; ?>
								<?php _e("Exclude posts with this tags from the posts list.", "lptw_recent_posts"); ?>
							</label>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="category_id"><?php _e("Category", "lptw_recent_posts"); ?>:&nbsp;</label></th>
					<td>
						<fieldset id="categories_list">
							<ul class="lptw-list-categories">
								<?php wp_category_checklist(0,0,$defaults["post_category"]); ?>
							</ul>
						</fieldset>
						<p class="description"><?php _e("If none of the categories is selected - will be displayed the posts from all the categories.", "lptw_recent_posts"); ?></p>
						<p>
							<label for="same_category">
								<?php echo $formFields["same_category"]["-1"]; ?>
								<?php _e("Use the same category, where is the post with a shortcode. This option override selected categories.", "lptw_recent_posts"); ?></label>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sorting"><?php _e("Sort & order posts", "lptw_recent_posts"); ?>:</label></th>
					<td>
						<fieldset id="sorting">
							<label for="orderby"><?php _e("Sort posts by", "lptw_recent_posts"); ?>:&nbsp;
								<?php echo $formFields["orderby"]; ?>
							</label>&nbsp; &nbsp;
							<label for="order"><?php _e("Order", "lptw_recent_posts"); ?>:&nbsp;
								<?php echo $formFields["order"]; ?>
							</label>
							<p>
								<label for="posts_per_page">
									<?php echo $formFields["posts_per_page"]; ?>
									<?php _e("Posts per page.", "lptw_recent_posts"); ?></label>
							</p>
							<p class="description"><?php _e("Only for shortcode, not global!", "lptw_recent_posts"); ?></p>
							<p>
								<label for="post_offset">
									<?php echo $formFields["post_offset"]; ?>
									<?php _e("Post offset.", "lptw_recent_posts"); ?></label>
							</p>
							<p class="description"><?php _e("Number of post to displace or pass over.", "lptw_recent_posts"); ?></p>
							<p>
								<label for="reverse_post_order">
									<?php echo $formFields["reverse_post_order"]["0"]; ?>
									<?php _e("Reverse post order: display the latest post last in the list. By default the latest post displays first.", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="exclude_current_post">
									<?php echo $formFields["exclude_current_post"]["0"]; ?>
									<?php _e("Exclude current post if the shortcode inserted in the post content.", "lptw_recent_posts"); ?></label>
							</p>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thumbnail_size"><?php _e("Image size", "lptw_recent_posts"); ?>:&nbsp;</label></th>
					<td>
						<?php echo $formFields["thumbnail_size"]; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="color_scheme"><?php _e("Color scheme", "lptw_recent_posts"); ?>:&nbsp;</label></th>
					<td>
						<?php echo $formFields["color_scheme"]; ?>
						<p class="description"><?php _e("Only for Basic layout.", "lptw_recent_posts"); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php _e("Colors", "lptw_recent_posts"); ?>:&nbsp;</label></th>
					<td>
						<div class="color-picker-wrapper">
							<label for="dropcap-background-color"><?php _e("Background color", "lptw_recent_posts"); ?></label><br>
							<?php echo $formFields["dropcap-background-color"]; ?>
						</div>
						<div class="color-picker-wrapper">
							<label for="dropcap-text-color"><?php _e("Text color", "lptw_recent_posts"); ?></label><br>
							<?php echo $formFields["dropcap-text-color"]; ?>
						</div>
						<p class="description"><?php _e("For Basic and Drop Cap layout. Also used in other Layouts if Posts have no Featured Image.", "lptw_recent_posts"); ?></p>
						<p>
							<label for="override_colors">
								<?php echo $formFields["override_colors"]["0"]; ?>
								<?php _e("Override colors in CSS.", "lptw_recent_posts"); ?></label>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Post Excerpt", "lptw_recent_posts"); ?>:&nbsp;</th>
					<td>
						<fieldset id="post_excerpt" class="layout-basic-hide layout-grid-show layout-thumbnail-hide layout-dropcap-hide" disabled="disabled">
							<p>
								<label for="excerpt_show">
									<?php echo $formFields["excerpt_show"]["0"]; ?>
									<?php _e("Show the Post Excerpt.", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="excerpt_length">
									<?php echo $formFields["excerpt_length"]; ?>
									<?php _e("Post Excerpt length in words.", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="ignore_more_tag">
									<?php echo $formFields["ignore_more_tag"]["0"]; ?>
									<?php _e("Ignore &lt;!-- more --&gt; tag.", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="read_more_show">
									<?php echo $formFields["read_more_show"]["0"]; ?>
									<?php _e("Display <em>'Read more'</em> link.", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="read_more_inline">
									<?php echo $formFields["read_more_inline"]["0"]; ?>
									<?php _e("Display <em>'Read more'</em> link inline with the post excerpt.", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="read_more_content">
									<?php echo $formFields["read_more_content"]; ?>
									<?php _e("Display <em>'Read more'</em> link text (use HTML symbols if needed).", "lptw_recent_posts"); ?></label>
							</p>
						</fieldset>
						<p class="description"><?php _e("Only for Responsive Grid layout yet.", "lptw_recent_posts"); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Date, time and subtitle settings", "lptw_recent_posts"); ?>:&nbsp;</th>
					<td>
						<fieldset id="display_date_time" class="layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-hide">
							<legend class="screen-reader-text"><span><?php _e("Show date and time ", "lptw_recent_posts"); ?></span></legend>
							<p>
								<label for="show_date_before_title">
									<?php echo $formFields["show_date_before_title"]["0"]; ?>
									<?php _e("Display <strong>date and time</strong> before post title.", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="show_date">
									<?php echo $formFields["show_date"]["0"]; ?>
									<?php _e("Display <strong>date</strong> in recent posts list", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="show_time">
									<?php echo $formFields["show_time"]["0"]; ?>
									<?php _e("Display <strong>time</strong> in recent posts list", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="show_time_before">
									<?php echo $formFields["show_time_before"]["0"]; ?>
									<?php _e("Display time <strong><u>before date</u></strong> in recent posts list. By default - after date.", "lptw_recent_posts"); ?></label>
							</p>
							<p>
								<label for="show_subtitle">
									<?php echo $formFields["show_subtitle"]["0"]; ?>
									<?php _e("Display <strong>subtitle</strong> in recent posts list after the post title if exist", "lptw_recent_posts"); ?></label>
							</p>
							<p class="description"><?php _e("Only for Basic and Thumbnail layouts.", "lptw_recent_posts"); ?></p>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Date Format", "lptw_recent_posts"); ?></th>
					<td>
						<fieldset id="date_formats" class="layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-hide">
							<legend class="screen-reader-text"><span><?php _e("Date Format", "lptw_recent_posts"); ?></span></legend>
							<label title="d.m.Y">
								<?php echo $formFields["sb_date_format"]["d.m.Y"]; ?> <span><?php echo date('d.m.Y');?></span>
							</label><br>
							<label title="m/d/Y">
								<?php echo $formFields["sb_date_format"]["m/d/Y"]; ?> <span><?php echo date('m/d/Y');?></span>
							</label><br>
							<label title="d/m/Y">
								<?php echo $formFields["sb_date_format"]["d/m/Y"]; ?> <span><?php echo date('d/m/Y');?></span>
							</label><br>
							<label title="F j, Y">
								<?php echo $formFields["sb_date_format"]["F j, Y"]; ?> <span><?php echo date('F j, Y');?></span>
							</label><br>
							<label title="M j, Y">
								<?php echo $formFields["sb_date_format"]["M j, Y"]; ?> <span><?php echo date('M j, Y');?></span>
							</label><br>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Time Format", "lptw_recent_posts"); ?></th>
					<td>
						<fieldset id="time_formats" class="layout-basic-show layout-grid-show layout-thumbnail-show layout-dropcap-hide">
							<legend class="screen-reader-text"><span><?php _e("Time Format", "lptw_recent_posts"); ?></span></legend>
							<label title="H:i">
								<?php echo $formFields["sb_time_format"]["H:i"]; ?> <span><?php echo date('H:i');?></span>
							</label><br>
							<label title="H:i:s">
								<?php echo $formFields["sb_time_format"]["H:i:s"]; ?> <span><?php echo date('H:i:s');?></span>
							</label><br>
							<label title="g:i a">
								<?php echo $formFields["sb_time_format"]["g:i a"]; ?> <span><?php echo date('g:i a');?></span>
							</label><br>
							<label title="g:i:s a">
								<?php echo $formFields["sb_time_format"]["g:i:s a"]; ?> <span><?php echo date('g:i:s a');?></span>
							</label><br>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">Result:</th>
					<td id="result">
						<a href="#" class="button button-default button-large" id="lptw_generate_shortcode"><?php _e("Generate Shortcode", "lptw_recent_posts"); ?></a>
						<div class="lptw-sb-row">
							<textarea name="lptw_generate_shortcode_result" id="lptw_generate_shortcode_result" class="lptw-sb-result"></textarea>
						</div>
						<button name="action" value="savedefault" class="button button-default button-large"><?php _e("Save as default", "lptw_recent_posts"); ?></button>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
<?php
}
?>