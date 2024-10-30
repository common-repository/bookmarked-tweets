<?php
/* 
* 
Plugin Name: MLX Bookmark Tweets
* Version: 1.0 
* Plugin URI: 
* Description: This plugin is used to show bookmarked or saved tweets on Twitterbase.com and pulled into a wordpress site.
* Author: MindLogix Techologies
* Author URI: http://mindlogixtech.com/
* 
*/ 

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter('widget_text','do_shortcode');

add_action('admin_init', 'mlx_bt_load_styles');

function mlx_bt_load_styles(){
	wp_register_style( 'book_tweet-style_css',  plugins_url( 'css/custom-style.css' , __FILE__ )  );
	wp_enqueue_style( 'book_tweet-style_css' );
	
}

function mlx_bt_load_styles_frontend(){
	wp_enqueue_style( 'book_tweet-style_css' , plugins_url( 'css/custom-style.css' , __FILE__ ));
	//wp_enqueue_style( 'book_tweet-fa_css' , plugins_url( 'css/font-awesome.min.css' , __FILE__ ));
	wp_enqueue_style( 'book_tweet-fa_css' , 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css');
	
}
add_action('wp_enqueue_scripts', 'mlx_bt_load_styles_frontend');

function mlx_bt_activate() {

    add_option( 'mlx_bt_last_updated_time', time(), '', 'yes' );
}
register_activation_hook( __FILE__, 'mlx_bt_activate' );

add_action( 'admin_menu', 'mlx_bt_book_tweet_menu' );

function mlx_bt_book_tweet_menu()
{
    add_menu_page(
        'Bookmark Tweet',     // page title
        'Bookmark Tweet',     // menu title
        'manage_options',   // capability
        'bookmar_tweet',     // menu slug
        'mlx_bt_bookmark_tweet_page_callback' // callback function
    );
}
function mlx_bt_bookmark_tweet_page_callback()
{
    global $title;
	$alert = false;
	$web_key_val = '';
	$web_sec_key_val = '';
	if(isset($_POST['addWebsiteKey']))
	{
		extract($_POST);
		$web_key_val = $website_key;
		$web_sec_key_val = $website_secret_key;
		
		if(!get_option('mlx_bt_website_key'))
		{
			$alert = true;
			add_option( 'mlx_bt_website_key', $website_key, '', 'yes' );
		}
		else
		{
			$alert = true;
			update_option( 'mlx_bt_website_key', $website_key );
		}
		
		if(!get_option('mlx_bt_website_secret_key'))
		{
			$alert = true;
			add_option( 'mlx_bt_website_secret_key', $website_secret_key, '', 'yes' );
		}
		else
		{
			$alert = true;
			update_option( 'mlx_bt_website_secret_key', $website_secret_key );
		}
	}
	else
	{
		if(get_option('mlx_bt_website_key'))
			$web_key_val = get_option('mlx_bt_website_key');
		
		if(get_option('mlx_bt_website_secret_key'))
			$web_sec_key_val = get_option('mlx_bt_website_secret_key');
	}
	?>
    <div class="wrap">
		<h1><?php echo $title; ?></h1>
		
		<p>Add Website Key to Fetch Saved Tweets.</p>
		
		<?php if($alert) { ?>
		<div class="notice notice-success is-dismissible" id="message">
				<p><?php echo 'Keys Updated Successfully';?></p>
				<button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button>
		</div>
		<?php }?>
		<form name="website_form" method="Post" id="createuser">
			<table class="form-table">
				<tbody>
					<tr class="form-field ">
						<th scope="row"><label for="shortcode">Shortcode</label></th>
						<td><input type="text" autocorrect="off" autocapitalize="none" aria-required="true" readonly="readonly"
						value="[mlx_bookmarked_tweets]" id="shortcode" name="shortcode">
						<p class="description indicator-hint">Copy above shortcode to post/page to get bookmarked tweets.</p>
						</td>
					</tr>
				
					<tr class="form-field form-required">
						<th scope="row"><label for="website_key">Website Key<span class="description">(required)</span></label></th>
						<td><input type="text" autocorrect="off" autocapitalize="none" aria-required="true" value="<?php echo $web_key_val; ?>" id="website_key" name="website_key" required></td>
					</tr>
					
					<tr class="form-field form-required">
						<th scope="row"><label for="website_secret_key">Website Secret Key<span class="description">(required)</span></label></th>
						<td><input type="text" autocorrect="off" autocapitalize="none" aria-required="true" value="<?php echo $web_sec_key_val; ?>" id="website_secret_key" name="website_secret_key" required></td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" value="Save" class="button button-primary" 
				id="addWebsiteKey" name="addWebsiteKey">
			</p>
		</form>
		
		
		
    </div>	
	<?php
}

function MLX_book_tweet_func($atts){
	ob_start(); 
	$website_key = get_option('mlx_bt_website_key');
	$website_secret_key = get_option('mlx_bt_website_secret_key');
	$par = '?key=';
	if(isset($website_key))
		$par .= $website_key;
	else
		$par .= "0";
	
	$par .= '&secret=';
	if(isset($website_secret_key))
		$par .= $website_secret_key;
	else
		$par .= "0";
	
	$remote_url = 'http://www.twitterbase.com/tw-admin/api/';
	$remote_url .= $par;
	
	$bt_last_updated_time = get_option('mlx_bt_last_updated_time');
	$cur_time = time();
	$date_diff = ($cur_time - $bt_last_updated_time);
	$min = round(abs($date_diff) / 60);
	//echo $min.' ';
	if($min > 30)
	{
		update_option( 'mlx_bt_last_updated_time', time() );
		$remote_url .= '&update=true';
		echo $remote_url;
	}
	
	$response =  wp_remote_get($remote_url);
	if( is_array($response) ) {
	  $header = $response['headers']; // array of http header lines
	  $body = $response['body']; // use the content
	  $body = json_decode($body);
	  if($body->data_returned_type == 'success' && count($body->result) > 0)
	  {
		  $tweetObj = new mlxTweet;
		  echo '<ul class="bookmark_tweets">
		  <li class="bt_first_li">Bookmarked Tweets</li>';
		  foreach($body->result as $k=>$v)
			{
				echo "<li id='t".$v->id_str."'>";
				$url = "https://twitter.com/statuses/".$v->id_str;
				echo "<a class='tweet_link_url' href='".$url."' target='_blank'></a>";
				
				if(isset($v->retweeted_status)  && is_object($v->retweeted_status))
				{
					
				echo "<div class='row retweeted'>
					<div class='col-md-2 pad-right-0'>&nbsp;</div>
					<div class='col-md-10 pad-right-0'><span class='retweet retweeted'><i class='fa fa-retweet'></i></span>
					<a href='http://twitter.com/".$v->user->screen_name."'>You Retweeted</a></div>
					  </div>";
					  $v = $v->retweeted_status;
				}	
				
				$newTweet = $tweetObj->replace_tweet_data($v);
				
				echo $newTweet->text;
				
				echo "</li>";
			}
		  echo '<li class="bt_last_li">Powered By www.twitterbase.com</li>
		  </ul>';
	  }
	  else 
	  {
		  echo $body->result;
	  }
	}
	return ob_get_clean();
}

add_shortcode('mlx_bookmarked_tweets', 'MLX_book_tweet_func');

class mlxTweet
{
	
	public function replace_tweet_data($tweet)
	{
		$options  = array("reply","retweet","like","remove","bulk_remove","select","save", "heart");
		$newTweet = $this->replaceHashtags($tweet);
		$newTweet = $this->replaceUserMentions($tweet);
		
		$newTweet = $this->addProfileImage($tweet);
		
		$newTweet = $this->addTweetOptions($tweet ,$options);
		return $newTweet;
	}
	
	
	public function replaceUrls($tweet){

		foreach($tweet->entities->urls  as $url)
		{
			$new_url = "<a rel='nofollow' target='_blank' href='".$url->url."' title='".$url->expanded_url."'>".$url->display_url."</a>";
			$tweet->text = str_replace($url->url,$new_url,$tweet->text );
		}
		return $tweet;
	}
	
	public function replaceHashtags($tweet){
		
		$tweet->text = $tweet->text . " ";	
		foreach($tweet->entities->hashtags  as $hashtag)
		{
			$url = "https://twitter.com/hashtag/";
			$new_url = "<a rel='nofollow' target='_blank' href='".$url.$hashtag->text."?src=hash' >#".$hashtag->text." </a>";
			$tweet->text = str_replace("#".$hashtag->text." ",$new_url,$tweet->text );
		}
		return $tweet;
	}
	
	public function replaceUserMentions($tweet){

		foreach($tweet->entities->user_mentions  as $user_mention)
		{
			$url = "https://twitter.com/";
			$new_url = "<a rel='nofollow' target='_blank' href='".$url.$user_mention->screen_name."' >@".$user_mention->screen_name."</a>";
			$tweet->text = str_replace("@".$user_mention->screen_name,$new_url,$tweet->text );
		}
		return $tweet;
	}
	
	public function addProfileImage($tweet){

		$image_url = $tweet->user->profile_image_url;
		$name = $tweet->user->name;
		$screen_name = $tweet->user->screen_name;
		$image = "<img src='".$image_url."'  />";
		
		$tweet->text = "<div class='row'><div class='col-md-2' style='padding-right: 0px;'>".$image . "</div><div class='col-md-10' style='padding-left: 0px;'><strong>".$name."</strong> @".$screen_name."<br>". $tweet->text . "</div></div>";
		
		return $tweet;
	}
	
	public function addTweetOptions($tweet , $options, $type=NULL){

		$tweet->text .= "<div class='row' data-tid='".$tweet->id_str."'><div class='col-md-2' style='padding-right: 0px;'>&nbsp;</div>";
		
		if($type != NULL and $type == 'user')
		{
			$btn_disable_text = "disabled='disabled'";
		}
		else
			$btn_disable_text = "";
		
		
		
		if($tweet->retweet_count)
			$retweet_count = $tweet->retweet_count;
		else 
			$retweet_count = "";
			
		if($tweet->retweeted  == 1)
			$btn_retweeted = " btn-retweeted ";
		else 
			$btn_retweeted = "";	
		
		
		if($tweet->favorite_count)
			$favorite_count = $tweet->favorite_count;
		else 
			$favorite_count = "";
		
		if(in_array('retweet',$options))
			$tweet->text .= "<div class='col-md-2 no-padding'>
					<button class='btn btn-box-tool btn-retweet ".$btn_retweeted."'><i class='fa fa-retweet'></i> ".
					$retweet_count."</button></div>";
		
		if(in_array('heart',$options))
			$tweet->text .= "<div class='col-md-2 no-padding'>
					<button class='btn btn-box-tool btn-fevorite'><i class='fa fa-heart'></i> ".
					$favorite_count."</button></div>";
		
		
		
		$tweet->text .= "</div>";
		return $tweet;
	}

	
}