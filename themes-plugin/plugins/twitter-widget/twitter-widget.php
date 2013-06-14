<?php
/*
Plugin Name: Twitter Widget
Version: 0.1
Description: Widget add Twitter Feed
Author: Etienne Tremel
*/

if ( ! class_exists( 'Twitter_Widget' ) ) {
    class Twitter_Widget {
        public function __construct() {
            /* INIT WIDGET */
            add_action( 'widgets_init', array( $this, 'twitter_widget_init' ) );
        }

        public function twitter_widget_init() {
            register_widget( 'Twitter_Widget_Constructor' );
        }
    }
}

if ( ! class_exists( 'Twitter_Widget_Constructor' ) ) {
    class Twitter_Widget_Constructor extends WP_Widget {
        function Twitter_Widget_Constructor() {
            $widget_ops = array(
                'classname'     => 'twitter-widget',
                'description'   => __( 'Add Twitter feed' )
            );

            parent::__construct( 'twitter-widget', __( 'Twitter Widget' ), $widget_ops );

            add_action( 'wp_enqueue_scripts', array( &$this, "enqueue_frontend_scripts" ) );
        }

        function enqueue_frontend_scripts() {
            wp_register_script(
                'twitter-widget_script',
                TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/frontend.js',
                array( 'jquery' )
            );
            wp_register_style( 'twitter-widget_style', TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/frontend.css' );

            wp_enqueue_script( 'twitter-widget_script' );
            wp_enqueue_style( 'twitter-widget_style' );
        }

        function widget( $args, $instance ) {
            extract( $args );

            $title                  = $instance['title'];
            $consumer_key           = $instance['consumer_key'];
            $consumer_secret        = $instance['consumer_secret'];
            $consumer_access_token  = $instance['consumer_access_token'];
            $consumer_token_secret  = $instance['consumer_token_secret'];
            $tweeter_username       = $instance['tweeter_username'];
            $slide_tweets           = $instance['slide_tweets'];
            $limit_number           = $instance['limit_number'];

            if ( empty( $tweeter_username )
                || ! $limit_number
                || empty( $consumer_key )
                || empty( $consumer_secret )
                || empty( $consumer_access_token )
                || empty( $consumer_token_secret )
            )
                return;


            //Check if feed already in transient (wordpress cache system)
            $tweets = get_transient( 'twitter_feed' );

            if ( $tweets === false ) {


                $json = $this->curl_request( array(
                    'consumer_key'          => $consumer_key,
                    'consumer_secret'       => $consumer_secret,
                    'consumer_access_token' => $consumer_access_token,
                    'consumer_token_secret' => $consumer_token_secret,
                    'query' => array(
                        'screen_name'   => $tweeter_username
                    )
                ) );

                $twitter_tweets = json_decode( $json );

                if ( is_array( $twitter_tweets ) ) {
                    foreach ( $twitter_tweets as $tweet ) {
                        $tweets[] = array(
                            'id'            => $tweet->id,
                            'created_at'    => $tweet->created_at,
                            'text'          => $tweet->text
                        );
                    }

                    set_transient( 'twitter_feed', $tweets, 60*15 ); //Store the feed for 15 minutes
                }
            }

            echo $before_widget;

            if ( sizeof( $tweets ) > 0 ):
                $limit_number = ( sizeof( $tweets ) > $limit_number ) ? $limit_number : sizeof( $tweets );
                ?>
                <?php if ( ! empty( $title ) ) : ?>
                    <?php echo $before_title . $title . $after_title; ?>
                <?php endif; ?>
                <?php if ( $slide_tweets ) : ?>
                <div class="slide">
                <?php endif; ?>
                    <ul class="feed">
                        <?php for ( $i = 0; $i < $limit_number; $i++ ) : ?>
                            <?php if ( $tweets[ $i ] ): $tweet = $tweets[ $i ]; ?>
                            <li><span class="message"><?php echo $this->generate_tweeter_metas( $tweet['text'] ); ?></span> <span class="date"><?php echo $this->time_ago( $tweet['created_at'] ); ?></span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </ul>
                <?php if ( $slide_tweets ) : ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p>No tweets found for <?php echo '@' . $tweeter_username; ?></p>
            <?php endif;

            echo $after_widget;
        }

        private function curl_request( $args = array() ) {

            // Default values
            $default_args = array(
                'consumer_key'          => '',
                'consumer_secret'       => '',
                'consumer_access_token' => '',
                'consumer_token_secret' => '',
                'request_URI'           => 'https://api.twitter.com/1.1/statuses/user_timeline.json',
                'query'                 => array(
                    'screen_name'   => 'upweyvalve',
                    'count'         => 10
                )
            );
            $user_datas = array_merge( $default_args, $args );

            // Check params
            if ( empty( $user_datas['consumer_key'] )
                || empty( $user_datas['consumer_secret'] )
                || empty( $user_datas['consumer_access_token'] )
                || empty( $user_datas['consumer_token_secret'] )
            )
                return false;

            // Hash
            $oauth_hash = array(
                'oauth_consumer_key'        => $user_datas['consumer_key'],
                'oauth_nonce'               => time(),
                'oauth_signature_method'    => 'HMAC-SHA1',
                'oauth_timestamp'           => time(),
                'oauth_token'               => $user_datas['consumer_access_token'],
                'oauth_version'             => '1.0',
            );

            // Include Query:
            foreach ( $user_datas['query'] as $key => $value )
                if ( ! array_key_exists( $key, $oauth_hash ) )
                    $oauth_hash[ $key ] = $value;

            // Sort alphabetical order
            ksort( $oauth_hash );

            // Build query
            $oauth_hash = http_build_query( $oauth_hash );

            $base = '';
            $base .= 'GET';
            $base .= '&';
            $base .= rawurlencode( $user_datas['request_URI'] );
            $base .= '&';
            $base .= rawurlencode( $oauth_hash );

            $key = '';
            $key .= rawurlencode( $user_datas['consumer_secret'] );
            $key .= '&';
            $key .= rawurlencode( $user_datas['consumer_token_secret'] );

            $signature = base64_encode( hash_hmac( 'sha1', $base, $key, true ) );
            $signature = rawurlencode( $signature );

            $oauth_header = '';
            $oauth_header .= 'oauth_consumer_key="' . $user_datas['consumer_key'] . '", ';
            $oauth_header .= 'oauth_nonce="' . time() . '", ';
            $oauth_header .= 'oauth_signature="' . $signature . '", ';
            $oauth_header .= 'oauth_signature_method="HMAC-SHA1", ';
            $oauth_header .= 'oauth_timestamp="' . time() . '", ';
            $oauth_header .= 'oauth_token="' . $user_datas['consumer_access_token'] . '", ';
            $oauth_header .= 'oauth_version="1.0", ';
            $curl_header = array("Authorization: Oauth {$oauth_header}", 'Expect:');

            // Curl
            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_HTTPHEADER, $curl_header );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            curl_setopt( $curl, CURLOPT_URL, $user_datas['request_URI'] . '?' . http_build_query( $user_datas['query'] ) );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
            $json = curl_exec( $curl );
            curl_close( $curl );

            return $json;
        }

        private function time_ago( $time ) {
            $right_now = time();
            $then = strtotime( $time );

            if ( ! $then )
                return;

            $diff = abs( $right_now - $then );

            $second = 1;
            $minute = $second * 60;
            $hour = $minute * 60;
            $day = $hour * 24;
            $week = $day * 7;

            if ( $diff < $second * 2 )
                return "right now";

            if ( $diff < $minute )
                return floor( $diff / $second ) . " secondes ago";

            if ( $diff < $minute * 2 )
                return "about 1 minute ago";

            if ( $diff < $hour )
                return floor( $diff / $minute ) . " minutes ago";

            if ( $diff < $hour * 2 )
            return "about 1 hour ago";

            if ( $diff < $day )
            return floor( $diff / $hour ) . " hours ago";

            if ( $diff > $day && $diff < $day * 2 )
                return "yesterday";

            if ( $diff < $day * 365 )
                return floor( $diff / $day) . " days ago";
            else
                return "over a year ago";
        }

        private function generate_tweeter_metas( $text ) {
            //Links
            $links_pattern = '/(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)(?:\([-A-Z0-9+&@#\/%=~_|$?!:;,.]*\)|[-A-Z0-9+&@#\/%=~_|$?!:;,.])*(?:\([-A-Z0-9+&@#\/%=~_|$?!:;,.]*\)|[A-Z0-9+&@#\/%=~_|$])/ix';

            preg_match_all( $links_pattern, $text, $links_matched );
            $links = $links_matched[0];

            foreach( $links as $link ) {
                $prefix = '';
                if ( ! preg_match( '/(http|https|file|ftp):\/\//i', $link ) ) //Add url prefix
                    $prefix = 'http://';
                $text = str_replace( $link, '<a href="' . $prefix . $link . '" target="_blank" rel="nofollow">' . $link . '</a>', $text );
            }

            $text = preg_replace( '/ ?(\#([^ ]+)+) ?/', ' <a href="http://twitter.com/search?q=%23$2" target="_blank">$1</a> ', $text ); //Hash tag
            $text = preg_replace( '/ ?(\@([^ ]+)+) ?/', ' <a href="http://twitter.com/$2" target="_blank">$1</a> ', $text ); //User tag

            return $text;
        }

        function form( $instance ) {
            $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'consumer_key' => '', 'consumer_secret' => '', 'consumer_access_token' => '', 'consumer_token_secret' => '', 'tweeter_username' => '', 'slide_tweets' => '', 'limit_number' => '10' ) );

            extract( $instance );
            ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label> <em>(not visible if empty)</em>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'consumer_key' ); ?>"><?php _e( 'Consumer Key:' ) ?></label>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'consumer_key' ); ?>" name="<?php echo $this->get_field_name( 'consumer_key' ); ?>" value="<?php echo $consumer_key; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'consumer_secret' ); ?>"><?php _e( 'Consumer Secret:' ) ?></label>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'consumer_secret' ); ?>" name="<?php echo $this->get_field_name( 'consumer_secret' ); ?>" value="<?php echo $consumer_secret; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'consumer_access_token' ); ?>"><?php _e( 'Consumer Access Token:' ) ?></label>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'consumer_access_token' ); ?>" name="<?php echo $this->get_field_name( 'consumer_access_token' ); ?>" value="<?php echo $consumer_access_token; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'consumer_token_secret' ); ?>"><?php _e( 'Consumer Secret Token:' ) ?></label>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'consumer_token_secret' ); ?>" name="<?php echo $this->get_field_name( 'consumer_token_secret' ); ?>" value="<?php echo $consumer_token_secret; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'tweeter_username' ); ?>"><?php _e( 'Twitter Username:' ) ?></label>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'tweeter_username' ); ?>" name="<?php echo $this->get_field_name( 'tweeter_username' ); ?>" value="<?php echo $tweeter_username; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'limit_number' ); ?>"><?php _e( 'Limit to:' ) ?></label> <em>(default: 5)</em>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'limit_number' ); ?>" name="<?php echo $this->get_field_name( 'limit_number' ); ?>" value="<?php echo $limit_number; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'slide_tweets' ); ?>"><input type="checkbox" name="<?php echo $this->get_field_name( 'slide_tweets' ); ?>" <?php echo ( $slide_tweets ) ? 'checked="checked"': ''; ?> /><?php _e( 'Slide Tweets' ) ?></label> <em>(Slide tweets)</em>

            </p>
            <?php
        }

        function update( $new_instance, $old_instance ) {
            $instance = $old_instance;
            $instance['tweeter_username']       = strip_tags( $new_instance['tweeter_username'] );
            $instance['title']                  = strip_tags( $new_instance['title'] );
            $instance['consumer_key']           = strip_tags( $new_instance['consumer_key'] );
            $instance['consumer_secret']        = strip_tags( $new_instance['consumer_secret'] );
            $instance['consumer_access_token']  = strip_tags( $new_instance['consumer_access_token'] );
            $instance['consumer_token_secret']  = strip_tags( $new_instance['consumer_token_secret'] );
            $instance['slide_tweets']           = strip_tags( $new_instance['slide_tweets'] );
            $instance['limit_number']           = intval( $new_instance['limit_number'] );
            return $instance;
        }
    }
}
?>