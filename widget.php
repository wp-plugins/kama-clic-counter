<?php
// регистрация Kcc_Widget в WordPress
function register_kcc_widget(){ register_widget( 'Kcc_Widget' ); }
add_action( 'widgets_init', 'register_kcc_widget' );


class Kcc_Widget extends WP_Widget {

	// Регистрация видежта используя основной класс
	function __construct() {
		parent::__construct(
			'kcc_widget', // Base ID
			__('KCC: Топ Загрузок', 'kcc'), // Name
			array( 'description' => __( 'Виджет Kama Click Counter', 'kcc' ), ) // Args
		);
	}

	
	/**
	 * Вывод виджета во Фронт-энде
	 *
	 * @param array $args     аргументы виджета.
	 * @param array $data сохраненные данные из настроек
	 */
	public function widget( $args, $data ) {
		global $wpdb;
		$data = (object) $data;
		
		$title  = apply_filters( 'widget_title', $data->title );
		$number = (int) $data->number;
		$template  = $data->template;

		echo $args['before_widget'];
		echo $args['before_title'] . $title . $args['after_title'];
	
		$KCC = & KCC::instance();
		
		if( $data->last_date ){
			$sql_date = esc_sql( $data->last_date );
			$AND_last_data = "AND link_date > '$sql_date'";
		}
		if( $data->only_downloads )
			$AND_downloads = "AND downloads != ''";
			
		$ORDER_BY = 'ORDER BY link_clicks DESC';
		if( $data->sort == 'clicks_per_day' )
			$ORDER_BY = 'ORDER BY (link_clicks/DATEDIFF( CURDATE(), link_date )) DESC, link_clicks DESC';
			
		$sql = "SELECT * FROM $KCC->table_name WHERE 1 $AND_last_data $AND_downloads $ORDER_BY LIMIT $number";
		if( ! $results = $wpdb->get_results( $sql ) )
			echo 'Error: empty SQL result';
		
		echo '<style type="text/css">'. $data->template_css .'</style>';
		
		echo '<ul class="kcc_widget">';
		foreach( $results as $link ){
			# замена шаблона
			# меняем основное
			$_tpl = $template; // временный шаблон
			if( false !== strpos( $template, '[link_description') ){
				$ln = 70;
				$desc = ( mb_strlen( $link->link_description, 'utf-8' ) > $ln ) ? mb_substr ( $link->link_description , 0 , $ln, 'utf-8' ) . ' ...' : $link->link_description;
				$_tpl = str_replace('[link_description]', $desc, $_tpl);
			}
			
			if( false !== strpos( $template, '[link_url') )
				$_tpl = str_replace('[link_url]', $KCC->redirect_preffix . $link->link_url, $_tpl );
			
			# меняем остальное
			echo '<li>'. $KCC->tpl_replace_shortcodes( $_tpl, $link ) .'</li>';
		}
		echo '</ul>';
		
		echo $args['after_widget'];
	}

	
	/**
	 * Админ-часть виджета
	 */
	public function form( $instance ) {
		$title     = $instance['title']     ? $instance[ 'title' ]     : __('Топ загрузок', 'kcc' );
		$number    = $instance['number']    ? $instance[ 'number' ]    : 5;
		$last_date = $instance['last_date'] ? $instance[ 'last_date' ] : '';
		$template_css   = $instance['template_css']   ? $instance[ 'template_css' ] : '.kcc_widget{ padding:15px; }
.kcc_widget li{ margin-bottom:10px; }
.kcc_widget li:after{ content:""; display:table; clear:both; }
.kcc_widget img{ width:30px; float:left; margin:5px 10px 5px 0; }
.kcc_widget p{ margin-left:40px; }';
		$template  = $instance['template']  ? $instance['template']    : '<img src="[icon_url]" alt="" />'. "\n" 
				.'<a href="[link_url]">[link_title]</a> ([link_clicks])'. "\n"
				.'<p>[link_description]</p>';
		?>
		<p><label><?php _e( 'Заголовок:', 'kcc' ); ?>
				<input type="text" class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>">
		</label></p>
		
		<p><label>
				<input type="text" class="widefat" style="width:40px;" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr( $number ); ?>"> ← <?php _e( 'сколко ссылок показыать?', 'kcc' ); ?>
		</label></p>
		
		<p><select name="<?php echo $this->get_field_name('sort'); ?>">
				<option value="all_clicks" <?php selected( $instance['sort'], 'all_clicks') ?>><?php _e( 'все клики', 'kcc' ); ?></option>
				<option value="clicks_per_day" <?php selected( $instance['sort'], 'clicks_per_day') ?>><?php _e( 'кликов в день', 'kcc' ); ?></option>
		</select> ← <?php _e( 'как сортировать результат?', 'kcc' ); ?></p>
		
		<p><label>
				<input type="text" class="widefat" style="width:100px;" placeholder="YYYY-MM-DD" name="<?php echo $this->get_field_name( 'last_date' ); ?>" value="<?php echo esc_attr( $last_date ); ?>"> ← <?php _e( 'показывать ссылки позднее этой даты (пр. 2014-08-09)', 'kcc' ); ?>
		</label></p>
		
		<p><label>
				<input type="checkbox" name="<?php echo $this->get_field_name( 'only_downloads' ); ?>" value="1" <?php checked( $instance['only_downloads'], 1 ) ?>> ← <?php _e( 'выводить только загрузки, а не все ссылки?', 'kcc' ); ?>
		</label></p>
		
		<hr>
		<p>
			<?php _e('Шаблон:', 'kcc' ); ?>
			<textarea class="widefat" style="height:100px;" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo $template; ?></textarea>
			<?php kcc_tpl_available_tags(); ?>
		</p>
		
		<p>
			<?php _e('CSS шаблона:', 'kcc' ); ?>
			<textarea class="widefat" style="height:100px;" name="<?php echo $this->get_field_name( 'template_css' ); ?>"><?php echo $template_css; ?></textarea>
		</p>
		<?php 
	}

	
	/**
	 * Сохранение настроек виджета. Здесь данные должны быть очищены и возвращены для сохранения их в базу данных.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title']     = $new_instance['title']  ? strip_tags( $new_instance['title'] ) : '';
		$instance['number']    = $new_instance['number'] ? (int) $new_instance['number']        : 5;
		$instance['last_date'] = preg_match('~[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}~', $new_instance['last_date'] ) ? $new_instance['last_date']     : '';
		// $instance['template']  = $new_instance['template'];
		
		$result = array_merge( $instance, $new_instance );

		return $result;
	}

} 

