<?php
/*
Plugin Name: Latest Posts with Share
Plugin URI: http://suhanto.net/latest-posts-share-widget-wordpress/
Description: Display the latest posts + a shared button (facebook or twitter). This share button can be used to share your blog posts to social media like facebook and twitter by your readers..
Author: Agus Suhanto
Version: 1.1
Author URI: http://suhanto.net/

Copyright 2010 Agus Suhanto (email : agus@suhanto.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// wordpress plugin action hook
add_action('plugins_loaded', 'latest_posts_share_init');

// initialization function
global $latest_posts_share;
function latest_posts_share_init() {
   $latest_posts_share = new latest_posts_share();
}

/*
 * This is the namespace for the 'top_commenters_gravatar' plugin / widget.
 */
class latest_posts_share {

   protected $_name = "Latest Posts with Share";
   protected $_folder;
   protected $_path;
   protected $_width = 370;
   protected $_height = 320;
   protected $_link = 'http://suhanto.net/latest-posts-share-widget-wordpress/';
   protected $_facebook_fbshare_api = 'http://static.ak.fbcdn.net/connect.php/js/FB.Share';
   protected $_tweetmeme_button_api = 'http://api.tweetmeme.com/button.js';
   
   /*
    * Constructor
    */
   function __construct() {
      $path = __FILE__;
      if (!$path) { $path = $_SERVER['PHP_SELF']; }
         $current_dir = dirname($path);
      $current_dir = str_replace('\\', '/', $current_dir);
      $current_dir = explode('/', $current_dir);
      $current_dir = end($current_dir);
      if (empty($current_dir) || !$current_dir)
         $current_dir = 'latest-posts-share';
      $this->_folder = $current_dir;
      $this->_path = '/wp-content/plugins/' . $this->_folder . '/';

      $this->init();
   }
   
   /*
    * Initialization function, called by plugin_loaded action.
    */
   function init() {
      add_action('template_redirect', array(&$this, 'template_redirect'));
      add_filter("plugin_action_links_$plugin", array(&$this, 'link'));
      load_plugin_textdomain($this->_folder, false, $this->_folder);      
      
      if (!function_exists('register_sidebar_widget') || !function_exists('register_widget_control'))
         return;
      register_sidebar_widget($this->_name, array(&$this, "widget"));
      register_widget_control($this->_name, array(&$this, "control"), $this->_width, $this->_height);
   }
   
   /*
    * Inserts the style into the head section.
    */
   function template_redirect() {
      $options = get_option($this->_folder);
      $this->validate_options($options);
      
      if ($options['social_media'] == 'facebook')
         wp_enqueue_script('fbshare', $_facebook_fbshare_api);
      if (!isset($options['use_style']) || $options['use_style'] != 'checked')
         wp_enqueue_style($this->_folder, $this->_path . 'style.css', null, '1.1');
   }
   
   /*
    * Options validation.
    */
   function validate_options(&$options) {
      if (!is_array($options)) {
         $options = array(
            'title' => 'Latest Posts',
            'num_of_posts' => '5', 
            'social_media' => 'facebook',
            'twitter_username' => '',
            'bitly_username' => '',
            'bitly_apikey' => '',
            'show_in_home' => '',
            'use_style' => '',
            'link_to_us' => '');
      }
      
      // validations and defaults
      if (intval($options['num_of_posts']) == 0) $options['num_of_posts'] = '5';
   }
   
   /*
    * Get time diff between 2 times.
    */
   function get_time_diff($time) {

      $difference = time() - strtotime($time);
      
      $weeks = round($difference / 604800);  
      $difference = $difference % 604800;
      $days = round($difference / 86400);
      $difference = $difference % 86400;
      $hours = round($difference / 3600);
      $difference = $difference % 3600;
      $minutes = round($difference / 60);
      $difference = $difference % 60;
      $seconds = $difference;
      
      if ($weeks > 0)
         return $weeks . ' ' . __('weeks', $this->_folder);
      else if ($days > 0)
         return $days . ' ' . __('days', $this->_folder);
      else if ($hours > 0)
         return $hours . ' ' . __('hours', $this->_folder);
      else if ($minutes > 0)
         return $minutes . ' ' . __('minutes', $this->_folder);
      else if ($seconds > 0)
         return $seconds . ' ' . __('seconds', $this->_folder);
   }
   
   /*
    * Called by register_sidebar_widget() function.
    * Rendering of the widget happens here.
    */
   function widget($args) {
      extract($args);
   
      $options = get_option($this->_folder);
      $this->validate_options($options);
      
      if (is_home() && $options['show_in_home'] != 'checked') return;
      
      echo $before_widget;
      echo $before_title;
      echo $options['title'];
      echo $after_title;

      echo '<div class="lps-div">';
      global $post;
      $myposts = get_posts('numberposts=' . $options['num_of_posts']);
      echo '<ul>';
      foreach($myposts as $post) {
         setup_postdata($post);
         echo '<li>';
         if ($options['social_media'] == 'facebook') {
            echo '<a name="fb_share" type="button_count" share_url="' . get_permalink() . '">' . __('Share', $this->_folder) . '</a>';
         }
         elseif ($options['social_media'] == 'twitter') {
            echo '<div class="lps-tweetmeme"><iframe width="70" scrolling="no" height="18" frameborder="0" src="' . $this->_tweetmeme_button_api . '?url=' . get_permalink() . '&amp;style=compact';
            if (!empty($options['twitter_username'])) {
               echo '&amp;source=' . $options['twitter_username'];
            }
            if (!empty($options['bitly_username']))
               echo '&amp;service=bit.ly&amp;service_api=' . $options['bitly_username'] . '%3A' . $options['bitly_apikey'];
            echo '"></iframe></div>';
         }
         echo '&#160;&#160;<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
         echo ' (' . $this->get_time_diff($post->post_date_gmt) . ' ' . __('ago', $this->_folder) . ')';
         echo '</li>';
 
      }; 

      echo '</ul>';
      if ($options['link_to_us'] == 'checked') {
         echo '<div class="lps-link"><a href="' . $this->_link . '" target="_blank">'. __('Get this widget for your own blog free!', $this->_folder) . '</a></div>';
      }
      
      echo '</div>';
      echo $after_widget;
   }
   
   /*
    * Plugin control funtion, used by admin screen.
    */
   function control() {
      $options = get_option($this->_folder);
      $this->validate_options($options);
   
      if ($_POST[$this->_folder . '-submit']) {
         $options['title'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-title']));
         $options['num_of_posts'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-num_of_posts']));
         $options['social_media'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-social_media']));
         $options['twitter_username'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-twitter_username']));
         $options['bitly_username'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-bitly_username']));
         $options['bitly_apikey'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-bitly_apikey']));
         $options['show_in_home'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-show_in_home']));
         $options['use_style'] = htmlspecialchars($_POST[$this->_folder . '-use_style']);
         $options['link_to_us'] = htmlspecialchars($_POST[$this->_folder . '-link_to_us']);
         update_option($this->_folder, $options);
      }
?>      
      <p>
         <label for="<?php echo($this->_folder) ?>-title"><?php _e('Title: ', $this->_folder); ?></label>
         <input type="text" id="<?php echo($this->_folder) ?>-title" name="<?php echo($this->_folder) ?>-title" value="<?php echo $options['title']; ?>" size="50"></input>
      </p>
      <p>
         <label for="<?php echo($this->_folder) ?>-num_of_posts"><?php _e('Num. of posts to display: ', $this->_folder); ?></label>
         <input type="text" id="<?php echo($this->_folder) ?>-num_of_posts" name="<?php echo($this->_folder) ?>-num_of_posts" value="<?php echo $options['num_of_posts']; ?>" size="2"></input> (<?php _e('default 5', $this->_folder) ?>) (<a href="<?php echo $this->_link?>#num-of-posts" target="_blank">?</a>)
      </p>
      <p>
         <label for="<?php echo($this->_folder) ?>-social_media"><?php _e('Social media button: ', $this->_folder); ?> </label>
         <select id="<?php echo($this->_folder) ?>-social_media" name="<?php echo($this->_folder) ?>-social_media">
   	       <option value="facebook" <?php echo $options['social_media'] == 'facebook' ? 'selected="true"' : ''; ?>>Facebook</option>
   	       <option value="twitter" <?php echo $options['social_media'] == 'twitter' ? 'selected="true"' : ''; ?>>Twitter</option>
   	     </select> (<a href="<?php echo $this->_link?>#social-media" target="_blank">?</a>)
      </p>
      <fieldset class="widefat" style="margin-bottom:10px">
         <legend><?php _e('Twitter & URL Shortening', $this->_folder) ?></legend>
         <div style="padding:10px">
         <p>
            <label for="<?php echo($this->_folder) ?>-twitter_username"><?php _e('Twitter Username: ', $this->_folder); ?></label>
            <input type="text" id="<?php echo($this->_folder) ?>-twitter_username" name="<?php echo($this->_folder) ?>-twitter_username" value="<?php echo $options['twitter_username']; ?>" size="50"></input> (<a href="<?php echo $this->_link?>#twitter-username" target="_blank">?</a>)
         </p>
         <p>
            <label for="<?php echo($this->_folder) ?>-bitly_username"><?php _e('Bit.ly Username: ', $this->_folder); ?></label>
            <input type="text" id="<?php echo($this->_folder) ?>-bitly_username" name="<?php echo($this->_folder) ?>-bitly_username" value="<?php echo $options['bitly_username']; ?>" size="50"></input> (<a href="<?php echo $this->_link?>#bitly-username" target="_blank">?</a>)
         </p>
         <p>
            <label for="<?php echo($this->_folder) ?>-bitly_apikey"><?php _e('Bit.ly API key: ', $this->_folder); ?></label>
            <input type="text" id="<?php echo($this->_folder) ?>-bitly_apikey" name="<?php echo($this->_folder) ?>-bitly_apikey" value="<?php echo $options['bitly_apikey']; ?>" size="50"></input> (<a href="<?php echo $this->_link?>#bitly-apikey" target="_blank">?</a>)
         </p>
         </div>
      </fieldset>
      <p>
         <input type="checkbox" id="<?php echo($this->_folder) ?>-show_in_home" name="<?php echo($this->_folder) ?>-show_in_home" value="checked" <?php echo $options['show_in_home'];?> /> <?php _e('Show in Home', $this->_folder) ?> (<a href="<?php echo $this->_link?>#show-in-home" target="_blank">?</a>)       
      </p>      
      <p>
          <input type="checkbox" id="<?php echo($this->_folder) ?>-use_style" name="<?php echo($this->_folder) ?>-use_style" value="checked" <?php echo $options['use_style'];?> /> <?php _e('Use custom style', $this->_folder) ?> (<a href="<?php echo $this->_link?>#custom-style" target="_blank">?</a>) 
      </p>
      <p>
          <input type="checkbox" id="<?php echo($this->_folder) ?>-link_to_us" name="<?php echo($this->_folder) ?>-link_to_us" value="checked" <?php echo $options['link_to_us'];?> /> <?php _e('Link to us (optional)', $this->_folder) ?> (<a href="<?php echo $this->_link?>#link-to-us" target="_blank">?</a>) 
      </p>
      <p><?php printf(__('More details about these options, visit <a href="%s" target="_blank">Plugin Home</a>', $this->_folder), $this->_link) ?></p>
      <input type="hidden" id="<?php echo($this->_folder) ?>-submit" name="<?php echo($this->_folder) ?>-submit" value="1" />
<?php 
   }
   
   /*
    * Add extra link to widget list.
    */
   function link($links) {
      $options_link = '<a href="' . $this->_link . '">' . __('Donate', $this->_folder) . '</a>';
      array_unshift($links, $options_link);
      return $links;
   }
}

?>