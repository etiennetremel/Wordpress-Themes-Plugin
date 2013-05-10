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
                'classname'        => 'twitter-widget',
                'description'      => __( 'Add Twitter feed' )
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

            $title                = $instance['title'];
            $tweeter_username     = $instance['tweeter_username'];
            $slide_tweets         = $instance['slide_tweets'];
            $limit_number         = $instance['limit_number'];

            if ( empty( $tweeter_username ) || ! $limit_number)
                return;


            //Check if feed already in transient (wordpress cache system)
            if ( get_transient( 'twitter_feed' ) === false ) {

                $twitter_url = 'http://api.twitter.com/1/statuses/user_timeline.json?screen_name=' . $tweeter_username;
                $twitter_tweets = json_decode( file_get_contents( $twitter_url ) );
                foreach ( $twitter_tweets as $tweet ) {
                    $tweets[] = array(
                        'id'              => $tweet->id,
                        'created_at'      => $tweet->created_at,
                        'text'            => $tweet->text
                    );
                }

                set_transient( 'twitter_feed', $tweets, 60*15 ); //Store the feed for 15 minutes

            } else {
                //Get feed from database instead of doing a request and reach the limitation
                $tweets = get_transient( 'twitter_feed' );
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
                            <li><span class="message"><?php echo $this->generateTweeterMetas( $tweet['text'] ); ?></span> <span class="date"><?php echo $this->timeAgo( $tweet['created_at'] ); ?></span></li>
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

        private function timeAgo( $time ) {
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

        private function generateTweeterMetas( $text ) {
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
            $instance = wp_parse_args( (array) $instance, array( 'tweeter_username' => '', 'slide_tweets' => '', 'limit_number' => '' ) );

            $title                 = esc_attr( isset( $instance['title'] ) ? $instance['title'] : '' );
            $tweeter_username      = esc_attr( isset( $instance['tweeter_username'] ) ? $instance['tweeter_username'] : '' );
            $slide_tweets          = esc_attr( isset( $instance['slide_tweets'] ) ? $instance['slide_tweets'] : '' );
            $limit_number          = esc_attr( isset( $instance['limit_number'] ) ? $instance['limit_number'] : '5' );
            ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label> <em>(not visible if empty)</em>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
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

            delete_transient( 'twitter_feed' );

            $instance['tweeter_username'] = strip_tags( $new_instance['tweeter_username'] );
            $instance['title']            = strip_tags( $new_instance['title'] );
            $instance['slide_tweets']     = strip_tags( $new_instance['slide_tweets'] );
            $instance['limit_number']     = intval( $new_instance['limit_number'] );
            return $instance;
        }
    }
}

?>