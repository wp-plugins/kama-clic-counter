<?php
/*
Plugin Name: Kama Click Counter
Plugin URI: http://wp-kama.ru/?p=430
Description: Подсчет загрузок файла и кликов по ссылке. Используйте в тексте шоткод <code>[download url="URL"]</code> или добавьте class <code>count</code> к ссылке - <code>&lt;a class=&quot;count&quot; href=&quot;ссылка&quot;&gt;текст&lt;/a&gt;</code>
Version: 3.2.3.3
Author: Kama
Author URI: http://wp-kama.ru/
*/

// инициализируем плагин
add_action('plugins_loaded', array('KCC', 'instance') );

// активация плагина
register_activation_hook( __FILE__, create_function('', 'KCC::instance()->activation();') );


// Плагин
class KCC {
	const OPT_NAME  = 'kcc_options';
	const COUNT_KEY = 'kcccount';
	const PID_KEY   = 'kccpid';
	const DB_NAME   = 'kcc_clicks';
	
	var $opt;
	var	$link_data;
	var	$redirect_preffix;
	var $redirect_preffix_with_post;
	
	var $plugin_dir_path;
	var $plugin_dir_url;
	var $plugin_dir_name;

	protected static $instance; 
	
	public static function instance(){
		is_null( self::$instance ) && self::$instance = new self;
		return self::$instance;
	}
	
	private function __construct(){
		if( ! is_null( self::$instance ) ) return self::$instance;
			
		$this->opt = get_option( self::OPT_NAME );
		$this->table_name = $GLOBALS['table_prefix'] . self::DB_NAME;
		
		$this->redirect_preffix = home_url() . '?' . self::COUNT_KEY .'=';
		$this->redirect_preffix_with_post = $this->opt['in_post'] ? str_replace('?', '?'.self::PID_KEY.'=%d&', $this->redirect_preffix ) : $this->redirect_preffix;

		$this->plugin_dir_path = plugin_dir_path(__FILE__);     // путь до каталога плагина
		$this->plugin_dir_url  = plugin_dir_url(__FILE__);       // УРЛ каталога плагинов
		$this->plugin_dir_name = basename( dirname(__FILE__) ); // название каталога kama click counter

		// локализация
		if( ($locale = get_locale()) && ($locale != 'ru_RU') )
			$res = load_textdomain('kcc', dirname(__FILE__) . '/lang/'. $locale . '.mo' );
			
		// Рабочая часть
		if( $this->opt['links_class'] )
			add_filter('the_content', array($this, 'modify_links') );
			
		if( $this->opt['links_class'] && $this->opt['js_count'] ){
			add_action('wp_footer', array($this, 'add_script_to_footer'), 99);
			add_action('wp_enqueue_scripts', create_function('', 'wp_enqueue_script("jquery");'), -10 ); // early jquery enqueue, in order it could be changed
		}
		
		// добавляем шоткод загрузок
		add_shortcode('download', array($this, 'download_shortcode') );
		
		// событие редиректа
		add_filter('plugins_loaded', array($this, 'redirect'), -10);
		
		// Добавляем Виджет
		if( $this->opt['widget'] )
			require_once $this->plugin_dir_path . 'widget.php';
		
		// Админка
		if( is_admin() ) $this->admin_init();		
	}
	
	/* jQuery добавка для подсчета ссылок на всем сайте */
	function add_script_to_footer(){		
		?>
		<!-- Kama Click Counter -->
		<script type="text/javascript">
			try{
				jQuery('a.<?php echo $this->opt['links_class'] ?>').each(function(){
					var href = jQuery(this).attr('href');
					// only for not modified links
					if( ! /<?php echo self::COUNT_KEY ?>/g.exec( href ) )
						jQuery(this).attr( { 
							onclick: "this.href='<?php echo $this->redirect_preffix ?>"+ href +"'",
							href: href +'#kcc'
						} ); 
				});
			} 
			catch(er){ console.log( er ); }
		</script>
		<?php
	}
	
	
	
	
	/* counting part
	-------------------------------------- */
	// add clicks by given url
	function do_count( $url ){
		global $wpdb;
		
		$url_data = $this->kcc_parce_url( $url );
		$link_url = $url_data[ self::COUNT_KEY ];
		$in_post = (int) $url_data[ self::PID_KEY ];
		$downloads = isset( $url_data['download'] ) ? 'yes' : '';

		if( $this->opt['in_post'] )
			$AND_in_post  = 'AND in_post='. $in_post;

		$last_click_date = current_time('mysql');
		
		// пробуем обновить данные
		$sql = $wpdb->prepare( "UPDATE {$this->table_name} SET link_clicks=(link_clicks + 1), last_click_date='$last_click_date', downloads='$downloads' WHERE link_url='%s' $AND_in_post LIMIT 1", $link_url );

		if( $wpdb->query( $sql ) )
			return 1;
		
		
		// Считаем первый раз, добавляем данные
		// Для загрузок, когда запись добавляется просто при просмотре, все равно добавляется 1 первый, промсмотр, чтобы добавить запись в бД
		$link_clicks = isset( $GLOBALS['from_download_shortcode_not_count'] ) ? 0 : 1;
		$link_date = current_time('mysql'); //gmdate('Y-m-d H:i:s', (time() + (get_option('gmt_offset') * 3600)));
		
		$link_name = preg_replace('@(https?|ftp)://@', '', $link_url);
		
		if( $this->is_file( $link_url ) )
			$link_name = basename( $link_url );

		$file_size = $this->file_size( $link_url );	
		
		$attach_id = 0;
		if( $attach = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type='attachment' AND guid='%s'", $link_url) ) ){
			$attach_id = $attach->ID;
			$link_title = $attach->post_title;
			$link_description = esc_sql( $attach->post_content );			
		} 
		
		if( ! $link_title ){
			if( $this->is_file( $link_url ) ){				
				$link_title = preg_replace('@(.*)\..*$@', '\\1', $link_name);
				$link_title = preg_replace('@[_-]@', ' ', $link_title);
				$link_title = ucwords( $link_title );
			}
			else
				$link_title = $this->get_title( $link_url );
		}
		
		$data = compact( 'attach_id', 'in_post', 'link_clicks', 'link_name', 'link_title', 'link_description', 'link_date', 'last_click_date', 'link_url', 'file_size', 'downloads' );
		$data = wp_unslash( $data ); // stripslashes внутри массива
		
		$wpdb->insert( $this->table_name, $data );
		
		return $wpdb->insert_id;
	}

	// Разибирает УРЛ. 
	// Конвертирует относительный путь "/blog/dir/file" в абсолютный (от корня сайта) и чистит УРЛ
	// возвращает array( параметры переданой строки )
	function kcc_parce_url( $url ){
		preg_match('~\?([^#]+)~', $url, $query );

		//parse_str( $query[1], $url_args );
		# разбираем строку parse_str() не подходит
		$url_args = array();
		foreach( explode('&', $query[1] ) as $part ){
			$t = array_map('trim', explode('=', $part, 2) ); // 2 для случаев, когда значение параметра само содержит "="
			$url_args[ $t[0] ] = $t[1];
		}
		
		if( ! $real_url = $url_args[ self::COUNT_KEY ] )
			return array();
		
		$real_url = preg_replace('/#.*$/', '', $real_url); // delete the #anchor part
		
		// ссылка на сайт, для относительных ссылок
		if( ! preg_match("@^(https?|ftp)://@i", $real_url) )			
			$real_url = home_url() . ( preg_match("@^/@", $real_url) ? '' : '/' ) . $real_url;
		
		$return = array(
			self::COUNT_KEY => $real_url,
			self::PID_KEY   => (int) $url_args[ self::PID_KEY ]
		);
		return $return;
	}

	function is_file( $url ){
		$extensions = array ("ace", "arj", "bin", "bz2", "dat", "deb", "gz", "hqx", "pak", "pk3", "rar", "rpm", "sea", "sit", "tar", "wsz", "zip", "aif", "aiff", "au", "mid", "mod", "mp3", "ogg", "ram", "rm", "wav",	"ani", "bmp", "dwg", "eps", "eps2", "gif", "ico", "jpeg", "jpg", "png", "psd", "psp", "qt", "svg", "swf", "tga", "tiff", "wmf", "xcf", "avi", "mov", "mpeg", "mpg",	"c", "class", "h", "java ", "jar", "js", "bat", "chm", "cur", "dll", "exe", "hlp", "inf", "ocx", "pps", "ppt", "reg", "scr", "xls",	"css", "conf", "doc", "ini", "pdf", "rtf", "ttf", "txt");		

		$ext = preg_replace('@.*\.([a-zA-Z0-9]+)$@', '\\1', $url);

		if( in_array($ext, $extensions) ) 
			return $ext;
			
		return false;
	}

	// return title of a (local or remote) webpage
	function get_title( $url ){
		$file = @file_get_contents( $url );
		if( preg_match("@<title>(.*)</title>@is", $file, $out) )
			return addslashes($out[1]);
			
		return "";
	}
	
	// Получает размер файла по сылке
	function file_size( $url ){
		$size = strlen( @file_get_contents( $url ) );
		if( ! $size ){
			if( strpos( $url, home_url() ) === false ){
				$url = urlencode( $url );
				if( function_exists('get_headers') ){
					$headers = @get_headers( $url, 1 );
					if( $headers['Content-Length'] == '' ) 
						return 0;
						
					$size = $headers['Content-Length'];
				}
			} else {
				$file_dir = preg_replace("@^/(.*)@", '$1', $url);
				$file_dir = preg_replace("@^https?://[^/]+/(.*)@", '$1', $file_dir);
				$file = ABSPATH . $file_dir;
				$size = @filesize($file);
			}
		}
		
		if( ! $size )
			return 0;
			
		$i = 0;
		$type = array(" B", " KB", " MB", " GB");
		while( ( $size/1024 ) > 1 ){
			$size = $size/1024;
			$i++;
		}
		return substr( $size, 0, strpos($size,'.')+2 ) . $type[$i];
	}

	
	
	
	
	
	
	/* text replacement part
	-------------------------------------- */
	// change links that have special class in given content
	function modify_links( $content ){
		if( false === strpos( $content, $this->opt['links_class'] ) )
			return $content;
			
		return preg_replace_callback( "@<a ([^>]*class=['\"][^>]*{$this->opt['links_class']}(?=[\s'\"])[^>]*)>(.+?)</a>@", array( $this, do_simple_link ), $content );
	}
		
	// parses string to detect and process pairs of tag="value"
	function do_simple_link( $match ){
		global $post;
		
		$link_attrs = $match[1];
		$link_anchor = $match[2];
		preg_match_all ('@[^=]+=([\'"])[^\\1]+?\\1@', $link_attrs, $args);
		foreach($args[0] as $pair){
			list($tag, $value) = explode("=", $pair, 2);
			$value = trim( trim( trim($value, "'"), '"' ) );
			$args[trim($tag)] = $value;
		}
		unset($args[0]);
		unset($args[1]);
		
		$args['href'] = sprintf( $this->redirect_preffix_with_post, $post->ID ) . $args['href'];
		if( $this->opt['add_hits'] ){
			$this->set_link_data( $args['href'] ); // получаем данные ссылки
			if( $this->link_data->link_clicks ){			
				if ( $this->opt['add_hits']=='in_title' ){
						$args['title'] = "(". __('кликов:','kcc') ." {$this->link_data->link_clicks})". $args['title'];
				} else {
					$after = ($this->opt['add_hits']=='in_plain') ? ' <span class="hitcounter">('. __('кликов:','kcc') .' '. $this->link_data->link_clicks .')</span>' : '';
				}
			}
		}

		$link_attrs = '';
		foreach( $args as $key => $value )
			$link_attrs .= "$key=\"$value\" ";
			
		$link_attrs = trim($link_attrs);
		
		return "<a {$link_attrs}>{$link_anchor}</a>$after";
	}
	
	// получает ссылку на картинку иконки g расширению переданной ссылки
	function get_icon_url( $link_url ){
		$link_ex = preg_replace( '@.*\.([a-zA-Z0-9]+)$@', '\\1', $link_url );
		$ex = 'png';
		$icon_url = file_exists( $this->plugin_dir_path . "icons/$link_ex.$ex" ) ? ( $this->plugin_dir_url . "icons/$link_ex.$ex" ) : ( $this->plugin_dir_url . "icons/default.$ex" );
		return $icon_url;
	}
	
	function download_shortcode( $attr ){
		global $post;
		
		$url = $attr['url'];
		$url = sprintf( $this->redirect_preffix_with_post, $post->ID ) . $url;
		$url = str_replace('?', '?download&', $url);
		
		// записываем данные в БД
		if( ! $this->set_link_data( $url ) ){
			$GLOBALS['from_download_shortcode_not_count'] = 1; // для проверки чтобы не считать эту операцию
			$this->do_count( $url );
			$this->set_link_data( $url );
		}
		
		$_tpl = $this->opt['download_tpl'];
		$_tpl = str_replace('[link_url]', $url, $_tpl );
		
		return $this->tpl_replace_shortcodes( $_tpl, $this->link_data );
	}
	
	// заменяет шоткоды в шаблоне на реальные данные
	// $link_data - (объект) данные ссылки из БД
	function tpl_replace_shortcodes( $tpl, $link_data ){
		if( false !== strpos($tpl, '[icon_url') )
			$tpl = str_replace('[icon_url]', $this->get_icon_url( $link_data->link_url ), $tpl );
		
		if( false !== strpos($tpl, '[edit_link') )
			$tpl = str_replace('[edit_link]', $this->edit_link( $link_data->link_id ), $tpl ); 
		
		if( preg_match('@\[link_date:([^\]]+)\]@', $tpl, $date) )
			$tpl = str_replace( $date[0], apply_filters('get_the_date', mysql2date($date[1], $link_data->link_date) ), $tpl );
		
		// меняем все остальные шоткоды
		preg_match_all('@\[([^\]]+)\]@', $tpl, $match);
		foreach( $match[1] as $data ){
			$tpl = str_replace("[$data]", $link_data->$data, $tpl );
		}
		
		return $tpl;
	}
		
	// получает данные уже существующие ссылки из БД
	function set_link_data( $url ){
		global $wpdb;
		
		$url_data = $this->kcc_parce_url( $url );
		$real_url = esc_sql( $url_data[ self::COUNT_KEY ] );
		$in_post = ( $id = (int) $url_data[self::PID_KEY] ) ? 'AND in_post=' . $id : '';

		$this->link_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE link_url='%s' $in_post", $real_url) );

		return $this->link_data;
	}
	
	// возвращает УРЛ на редактирование ссылки в админке
	function edit_link( $link_id, $edit_text = '' ){
		if( ! current_user_can('manage_options') ) return;
			
		if( ! $edit_text ) $edit_text = __('редактировать', 'kcc');
		
		return '<a href="'. admin_url('admin.php?page=' . $this->plugin_dir_name . '&edit_link=' . $link_id ) . '">'. $edit_text .'</a>';
	}
	
	
	
	
	
	
	/* admin part
	-------------------------------------- */
	function admin_init(){
		require $this->plugin_dir_path . 'admin_functions.php';
		
		add_action('admin_menu',        array($this, 'admin_menu') );
		
		add_action('delete_attachment', array($this, 'delete_link_by_attach_id') );	
		add_action('edit_attachment',   array($this, 'update_link_with_attach') );
		
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugins_page_links') );
		
		// MCE
		include  $this->plugin_dir_path . 'mce/mce.php';
	}
	
	// Ссылки на страницы статистики и настроек со страницы плагинов
	function plugins_page_links( $actions ){			
		$actions[] = '<a href="admin.php?page='. $this->plugin_dir_name .'&options">'. __('Настройки', 'kcc') .'</a>'; 
		$actions[] = '<a href="admin.php?page='. $this->plugin_dir_name  .'">'. __('Статистика', 'kcc') .'</a>'; 
		return $actions; 
	}	
	
	function set_def_options(){
		update_option( self::OPT_NAME, $this->def_option_array() );
		
		return $this->opt = get_option( self::OPT_NAME );
	}
	function def_option_array(){
		$array = array(
			'download_tpl' => '<div class="kcc_block" title="Скачать" onclick="document.location.href=\'[link_url]\'">
	<img class="alignleft" src="[icon_url]" alt="" />
	<a href="[link_url]" title="[link_name]">[link_title]</a>
	<div class="description">[link_description]</div>
	<small>Скачано: [link_clicks], размер: [file_size], дата: [link_date:d.M.Y]</small> 
	<b><!-- clear --></b>
</div>
[edit_link]

<style type="text/css">
.kcc_block{ padding:15px 10px; margin-bottom:20px; cursor:pointer; transition:background-color 0.4s; }
.kcc_block:before{ clear:both; } 
.kcc_block a { text-decoration:none; display:block; font-size:200%; margin:10px; } 
.kcc_block img{ width:55px; height:auto; float:left; margin:0 25px 0 5px;border:0px!important; box-shadow:none!important; } 
.kcc_block .description{ color:#666; } 
.kcc_block small{ color:#ccc; } 
.kcc_block b{ display:block; clear:both; }
.kcc_block:hover{ background: #EBF5E1; }
.kcc_block:hover a{ text-decoration:none; }
</style>',
			'links_class'    => 'count',      // проверять class в простых ссылках
			'add_hits' => '',           // may be: '', 'in_title' or 'in_plain' (for simple links)
			'in_post'  => 1,
			'js_count' => 1,            // Добавлять ли скрипт jQuery, которй будет менять УРЛ у всех ссылок с указанным в links_class классом.
			'widget'   => 1,            // включить виджет для WordPress
		);
		
		return wp_unslash( $array );
	}
	
	function admin_menu(){
		add_options_page('Kama Click Counter', 'Kama Click Counter', 'manage_options', $this->plugin_dir_name,  array( $this, 'admin_options_page' ));
	}
	
	function admin_options_page(){		
		if( isset( $_POST['save_options'] ) ){
			$opt = array();
			
			$_POST = wp_unslash( $_POST );
			
			foreach( $this->def_option_array() as $k=>$v )
				$opt[ $k ] = $_POST[ $k ] ? $_POST[ $k ] : '';
			
			update_option( self::OPT_NAME, $opt );
			
			if( $this->opt = get_option( self::OPT_NAME ) )
				$alert = __('Настройки сохранены.', 'kcc');
			else
				$alert = __('Ошибка: не удалось обновить настройки!', 'kcc');
		}
		elseif( isset( $_POST['reset'] ) ){
			$this->set_def_options();
			$alert = __('Настройки сброшены на начальные!', 'kcc');
		}
		elseif( isset($_POST['update_link']) ){
			$id   = (int) $_POST['link_id'];
			$data = wp_unslash( $_POST );
			unset( $data['update_link'], $data['local_referer'] );
			
			$alert = $this->update_link( $id, $data ) ? __('Ссылка обновлена!', 'kcc') : __('Не удалось обновить ссылку!', 'kcc');
		}
		elseif( $_POST['delete_link_id'] ){
			if( $this->delete_links($_POST['delete_link_id']) ) $alert = __('Выбранные ссылки удалены!', 'kcc');
			else $alert = __('Ничего <b>не удалено</b>!', 'kcc');
		}
		
		
		include $this->plugin_dir_path . '/admin.php';
	}
	
	function update_link( $id, $data ){
		global $wpdb;
		$id = (int) $id;
		
		$query = $wpdb->update( $this->table_name, $data, array( 'link_id' => $id ) );
		
		// обновление вложения, если оно есть
		if( $data['attach_id'] > 0 ){
			$up_data = array('post_title' => $data['link_title'], 'post_content' => $data['link_description']);
			$rrr = $wpdb->update( $wpdb->posts, $up_data, array( 'ID' => $data['attach_id'] ) );
		}
		
		return $query;
	}
	
	# Удаление ссылок из БД по переданному массиву ID-шек
	function delete_links( $array_ids = array() ){
		global $wpdb;
		foreach( $array_ids as $k=>$id ) // килл пустые элемены
			if( !trim($id) ) unset( $array_ids[$k] );
		
		if( !$array_ids ) return false;
				
		$sql = "DELETE FROM {$this->table_name} WHERE link_id IN (". implode(',', $array_ids) .")";
		
		return $wpdb->query( $sql );
	}
	
	# Удаление ссылки по ID вложения
	function delete_link_by_attach_id($attach_id){
		global $wpdb;
		if( $attach_id=='' ) return false;
		$sql = "DELETE FROM {$this->table_name} WHERE attach_id={$attach_id}";
		return $wpdb->query( $sql );
	}
	
	# Обновление ссылки, если обновляется вложение
	function update_link_with_attach( $attach_id ){
		global $wpdb;
		$attdata = wp_get_single_post( $attach_id );
		$new_data = array(
			'link_description' => $attdata->post_content
			,'link_title' => $attdata->post_title
			,'link_date' => $attdata->post_date
		);
		
		$new_data = wp_unslash( $new_data );
		
		return $wpdb->update( $this->table_name, $new_data, array( 'attach_id' => $attach_id ) );
	}
	
	function activation(){
		global $wpdb;
		
		// Обновление до версии 3.0
		if( $wpdb->query("SHOW TABLES LIKE '{$this->table_name}'") ){
			// $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content, '[download=', '[download url=')");
			// обновим таблицу
			$charset_collate  = 'CHARACTER SET ' . ( (! empty( $wpdb->charset )) ? $wpdb->charset : 'utf8' );
			$charset_collate .= ' COLLATE ' . ( (! empty( $wpdb->collate )) ? $wpdb->collate : 'utf8_general_ci' );
			
			// добавим поле: дата последнего клика
			$wpdb->query("ALTER TABLE {$this->table_name} ADD `last_click_date` DATE NOT NULL default '0000-00-00' AFTER link_date");
			$wpdb->query("ALTER TABLE {$this->table_name} ADD `downloads` ENUM('','yes') NOT NULL default ''");
			$wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX  `downloads` (`downloads`)");
			// обновим существующие поля
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `link_date`  `link_date` DATE NOT NULL default  '0000-00-00'");
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `link_id`    `link_id`   BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT");
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `attach_id`  `attach_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `in_post`    `in_post`   BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `link_clicks`  `link_clicks` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
			$wpdb->query("ALTER TABLE {$this->table_name} DROP  `permissions`");

			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `link_name`   `link_name`  VARCHAR( 255 ) $charset_collate NOT NULL");
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `link_title`  `link_title` VARCHAR( 255 ) $charset_collate NOT NULL");
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `link_url`    `link_url`   VARCHAR( 255 ) $charset_collate NOT NULL");
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `link_description`  `link_description` VARCHAR( 255 ) $charset_collate NOT NULL");
			$wpdb->query("ALTER TABLE {$this->table_name} CHANGE  `file_size`   `file_size`  VARCHAR( 255 ) $charset_collate NOT NULL");
		}
		else 
		{
			$charset_collate  = (! empty( $wpdb->charset )) ? "DEFAULT CHARSET=$wpdb->charset" : '';
			$charset_collate .= (! empty( $wpdb->collate )) ? " COLLATE $wpdb->collate" : '';
			
			// Создаем таблицу если такой еще не существует
			$sql = "CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
				`link_id`     bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`attach_id`   bigint(20) UNSIGNED NOT NULL default '0',
				`in_post`     bigint(20) UNSIGNED NOT NULL default '0',
				`link_clicks` bigint(20) UNSIGNED NOT NULL default '1',
				`link_name`   varchar(255) NOT NULL,
				`link_title`  varchar(255) NOT NULL,
				`link_description`  varchar(255) NOT NULL,
				`link_date`         date NOT NULL default '0000-00-00',
				`last_click_date`   date NOT NULL default '0000-00-00',
				`link_url`    varchar(255) NOT NULL,
				`file_size`   varchar(255) NOT NULL,
				`downloads`   ENUM('','yes') NOT NULL default '',
				PRIMARY KEY (`link_id`), 
				KEY in_post(`in_post`),
				KEY downloads(`downloads`)
			) $charset_collate";

			$wpdb->query( $sql );
		}
		
		if( ! get_option( self::OPT_NAME ) )
			$this->set_def_options();

		return;
	}
	
	
	
	
	
	/* redirect
	------------------------------------------------------------- */
	function redirect(){
		if( ! $url = $_GET[ self::COUNT_KEY ] )
			return;
		if( ! $_SERVER['HTTP_REFERER'] )
			die(__('Запрещается прямое использвьание этой ссылки', 'kcc'));
			
		global $is_IIS;
		
		if ( !$is_IIS && php_sapi_name() != 'cgi-fcgi' )
			status_header($status); // This causes problems on IIS and some FastCGI setups
			
		# считаем
		$this->do_count( $_SERVER['REQUEST_URI'] );
		
		# перенаправляем
		if( headers_sent() )
			print "<script>location.replace('$url');</script>";
		else
			header("Location: $url", true, 303); // wp_redirect() не подходит...
		
		exit;
	}
	
	
	
}