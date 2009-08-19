<?php 
/*
Plugin Name: Suggest Tags
Plugin URI: http://slaptijack.com/tag/suggest-tags/
Description: This plugin uses the <a href="http://tagthe.net">tagthe.net</a> tagging service to generate a list of tag suggestions based on your post's content. The concept for this plugin was originally included in the <a href="http://wordpress.org/extend/plugins/tag-suggest-thing/">Tag Suggest Thing</a> plugin. 
Version: 1.0.0b1
Author: Scott Hebert
Author URI: http://www.heberts.net/about/
*/

class SuggestTags {
  function add_tag_box() {
    if ( function_exists('add_meta_box') ) {
      add_meta_box('tag_suggestions', 'Tag Suggestions', array('SuggestTags', 'add_new_box'), 'post', 'side', 'low');
    } else {
      add_action('dbx_post_advanced', array('SuggestTags', 'add_old_box'));
    }
  }
  
  function add_new_box() {
    ?>
<p>
  <input name="get_suggestions" type="button" onclick="suggest_tags_get_suggestions()" value="<?php _e('Suggest Tags') ?>">
  <div id="suggestion_results"></div>
</p>
    <?php
    
  }

	function add_old_box() {
		?>
<fieldset id="tagsuggestions" class="dbx-box">
<h3 class="dbx-handle"><?php _e('Tag Suggestions') ?></h3>
<div class="dbx-content">
	<p>
		<input name="get_suggestions" type="button" onclick="suggest_tags_get_suggestions()" value="<?php _e('Get Tag Suggestions') ?>">
		<div id="suggestion_results"></div>
	</p>
</div>
</fieldset>
<?
	}

	function add_javascript() {
	  // use JavaScript SACK library for AJAX
	  wp_print_scripts(array( 'sack' ));

	  // Define custom JavaScript function
	?>
<script type="text/javascript">
	//<![CDATA[
	
	function suggest_tags_get_suggestions() {
	  var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php");    

	  mysack.execute = 1;
	  mysack.method = 'POST';
	  mysack.setVar( "action", "suggest_things_ajax_get_suggestions" );
	  mysack.setVar( "content", document.getElementById("content").value);
	  mysack.encVar( "cookie", document.cookie, false );
	  mysack.onError = function() { alert('AJAX error while looking up tag suggestions' ) };
	  mysack.runAJAX();
		
	  return TRUE;
	}
	
	function process_matches(matches) {
		html = "";
		htmlall = "";
		if(matches.memes[0]) {
			for(i = 0; i < matches.memes[0].dimensions.topic.length; i++) {
				html += "<a onclick=\"suggest_tags_add_tag('" + matches.memes[0].dimensions.topic[i] +"')\">" + matches.memes[0].dimensions.topic[i] +"</a><br />";
				if (htmlall != "") htmlall += ", ";
				htmlall += matches.memes[0].dimensions.topic[i];
			}

			html += "<br /><a onclick=\"suggest_tags_add_tag('" + htmlall + "')\"><?php _e('Add All') ?></a>";
		} else {
			html = "<i><?php _e('No Suggestions') ?></i>";
		}
		elt = document.getElementById("suggestion_results");
		elt.innerHTML = html;
	}
	
	function suggest_tags_add_tag(tag) {
	  tag_field = document.getElementById('new-tag-post_tag');
		if (tag_field.value == "" || tag_field.value == "Add new tag" ) {
			tag_field.value = tag;
		} else {
			tag_field.value += ", " + tag;
		}
	}
	//]]>
</script>
<?php
	}

	function get_suggestions() {
		$content = $_POST['content'];
		$title   = $_POST['title'];
		$content = stripslashes($content) . ' ' . stripslashes($title);
		
		if ( empty($content) ) {
		  error_log("No text received.");
		  exit();
		}
		
		$r = wp_remote_post('http://tagthe.net/api/?text='.urlencode($content).'&view=json&count=20');
		
		if ( !is_wp_error($r) ) {
		  $code = wp_remote_retrieve_response_code($r);
		  if ($code == 200 ) {
		    $json = maybe_unserialize(wp_remote_retrieve_body($r));
		  } else {
		    error_log("Received error code from tagthe.net: $code"); 
		  }
		}
		
		die("process_matches(eval(" . $json . "));");
	}
}

add_action('admin_print_scripts', array('SuggestTags','add_javascript'));
add_action('admin_menu', array('SuggestTags', 'add_tag_box'));
add_action('wp_ajax_suggest_things_ajax_get_suggestions', array('SuggestTags','get_suggestions'));
?>