<?php
class WPVC_Widget extends WP_Widget{

    private $wpdb;
    private $table_name;

    public function __construct(){

        global $wpdb;

        $this->wpdb = &$wpdb;
        $this->table_name = $this->wpdb->prefix . WPVC_TBL_NAME;

        $params=array(
            'description' => 'Display Visitor Counter and Statistics Traffic', //plugin description
            'name' => 'WP Visitors Counter'
        );

        parent::__construct('WPVC_Widget', '', $params);

        if( is_admin() ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
        }
    }

    private function get_themes(){
        $themes = array('default' => 'Default');
        foreach ( glob( WPVC_PATH . "odometer/themes/*.css" ) as $theme ) {
            $theme_name = str_replace('odometer-theme-', '', basename($theme, '.css'));
            $themes[$theme_name] = ucwords(str_replace('-', ' ', $theme_name));
        }
        return $themes;
    }

    private function get_options(){

        $options = array(
            'visit_today' => __('Visits today', 'wpvc_td'),
            'visit_yesterday' => __('Visits yesterday', 'wpvc_td'),
            'visit_month' => __('Visits this month', 'wpvc_td'),
            'visit_year' => __('Visits this year', 'wpvc_td'),
            // 'show_visit_total' => __('Show visits total?'),
            // 'show_hits_today' => __('Show hits today?'),
            // 'show_hits_yesterday' => __('Show hits yesterday?'),
            // 'show_hits_month' => __('Show hits this Month?'),
            // 'show_hits_year' => __('Show hits this year?'),
            // 'show_hits_total' => __('Show hits total?')
        );
        
        return $options;
    }

    private function visit_today(){
        $today_date = date("Y-m-d", current_time('timestamp'));
        $sql = "SELECT COUNT(*) FROM $this->table_name vc WHERE date(vc.last_visit) = %s";
    	$result = $this->wpdb->get_var(
        $this->wpdb->prepare(
                $sql,
                $today_date
            )
        );
        return $result;
    }

    private function visit_yesterday(){
        $yesterday_date = date("Y-m-d", current_time('timestamp') - 60 * 60 * 24);
        $sql = "SELECT COUNT(*) FROM $this->table_name vc WHERE date(vc.last_visit) = %s";
        $result = $this->wpdb->get_var(
        $this->wpdb->prepare(
                $sql,
                $yesterday_date
            )
        );
        return $result;
    }

    private function visit_month(){
        $today_date = date("Y-m-d", current_time('timestamp'));
        $sql = "SELECT COUNT(*) FROM $this->table_name vc WHERE MONTH(vc.last_visit) = MONTH(%s) AND YEAR(vc.last_visit) = YEAR(%s)";
        $result = $this->wpdb->get_var(
        $this->wpdb->prepare(
                $sql,
                $today_date,
                $today_date
            )
        );
        return $result;
    }

    private function visit_year(){
        $today_date = date("Y-m-d", current_time('timestamp'));
        $sql = "SELECT COUNT(*) FROM $this->table_name vc WHERE YEAR(vc.last_visit) = YEAR(%s)";
        $result = $this->wpdb->get_var(
        $this->wpdb->prepare(
                $sql,
                $today_date
            )
        );
        return $result;
    }

    private function visit_total(){
        $sql = "SELECT COUNT(*) FROM $this->table_name";
        $result = $this->wpdb->get_var($sql);
        return $result;
    }

    private function randomNumber($length) {
        $result = '';

        for($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }

        return $result;
    }
       
    // extract($instance);
    public function form($instance)  {
        //Defaults
        $instance = wp_parse_args( 
            (array) $instance, 
            array( 
                'title' => '',
                'theme' => 'default',
                'start_number' => 0
            )
        );
        $title = esc_attr( $instance['title'] );
        $theme = esc_attr( $instance['theme'] );
        $start_number = absint( $instance['start_number'] );

        foreach ($this->get_options() as $key => $value) {
            $instance[$key] = isset($instance[$key]) ? true : false;
            $instance[$key.'_label'] = !empty($instance[$key.'_label']) ? $instance[$key.'_label'] : $value;
        }

        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
        <hr>
        <h3><?php _e('Widget Styling'); ?></h3>
        <p>
          <select name="<?php echo $this->get_field_name('theme'); ?>" id="<?php echo $this->get_field_id('theme'); ?>" class="widefat">
            <?php foreach ($this->get_themes() as $key => $value) { ?>
                <option value="<?php echo $key; ?>"<?php selected( $theme, $key ); ?>><?php echo $value; ?></option>
            <?php } ?>
          </select>
        </p>
        <hr>
        <h3><?php _e('Widget Option'); ?></h3>
        <p><label for="<?php echo $this->get_field_id('start_number'); ?>"><?php _e('Start Number:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('start_number'); ?>" name="<?php echo $this->get_field_name('start_number'); ?>" type="text" value="<?php echo $start_number; ?>" /></p>
        <p>
        <?php foreach ($this->get_options() as $key => $value) { ?>
            <input class="checkbox" type="checkbox" <?php checked($instance[$key], true) ?> id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name($key); ?>" value="1" />
            <label for="<?php echo $this->get_field_id($key); ?>"><?php echo $value; ?></label><br />
            <input class="widefat" id="<?php echo $this->get_field_id($key.'_label'); ?>" name="<?php echo $this->get_field_name($key.'_label'); ?>" type="text" value="<?php echo $instance[$key.'_label']; ?>" /><br /><br />
        <?php } ?>
        </p>
        <hr>
        <?php
    }

    public function widget($args, $instance){

        $instance = wp_parse_args( 
            (array) $instance, 
            array( 
                'title' => '',
                'theme' => 'default',
                'start_number' => 0
            )
        );

        $theme = esc_attr( $instance['theme'] );
        $start_number = absint( $instance['start_number'] );

        extract($args, EXTR_SKIP);

	
        echo $before_widget;

        $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
 
        if (!empty($title)){
            echo $before_title . $title . $after_title;
        }

        foreach ($this->get_options() as $key => $value) {
            $instance[$key] = isset($instance[$key]) ? true : false;
            $instance[$key.'_label'] = !empty($instance[$key.'_label']) ? $instance[$key.'_label'] : $value;
        }

        $total_visits = $this->visit_total() + $start_number;

        wp_enqueue_style( 'odometer-theme-'.$theme, WPVC_URL . 'odometer/themes/odometer-theme-'.$theme.'.css' );
        wp_enqueue_script( 'odometer', WPVC_URL . 'odometer/odometer.js' );

        echo '<div class="visitors-total">';
        echo '<div id="'.$this->id.'-odometer">123</div>';
        echo '</div>';

        if($this->get_options()){
        	echo '<ul>';
        	foreach ($this->get_options() as $key => $value) {
        		if(method_exists($this, $key)){
        			echo '<li class="'.$key.'">';
	        		echo '<span class="stats_label">';
	        		echo $instance[$key.'_label'];
	        		echo '</span>';
	        		echo ' <span class="stats_value">';
	        		echo call_user_func(array($this, $key));
	        		echo '</span>';
	        		echo '</li>';
        		}
        		
        	}
        	echo '</ul>';
        }

        echo $after_widget;
        ?>
        <script type="text/javascript">
        (function($){
            $(document).ready(function() {
                var el = document.querySelector('#<?php echo $this->id; ?>-odometer');
                od = new Odometer({
                    el: el,
                    value: <?php echo $this->randomNumber(strlen($total_visits)); ?>,
                    format: '',
                    theme: '<?php echo $theme; ?>'
                });
                setTimeout(function(){
                    el.innerHTML = <?php echo $total_visits; ?>;
                }, 1000);
            });
        })(jQuery);
        </script>
        <?php
    }
}