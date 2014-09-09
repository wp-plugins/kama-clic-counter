<?php

// доступные шоткоды в шаблонах ссылок
function kcc_tpl_available_tags(){
	?>
	<small>
		<?php _e('Теги, которые можно использовать в шаблоне:', 'kcc') ?><br />
		<?php _e('[icon_url] - УРЛ на иконку к файлу', 'kcc') ?><br />
		<?php _e('[link_url] - УРЛ на закачку', 'kcc') ?><br />
		<?php _e('[link_name] - название файла', 'kcc') ?><br />
		<?php _e('[link_title] - заголовок файла', 'kcc') ?><br />
		<?php _e('[link_clicks] - количество скачиваний', 'kcc') ?><br />
		<?php _e('[file_size] - размер файла', 'kcc') ?><br />
		<?php _e('[link_date:d.M.Y] - дата в формате "d.M.Y"', 'kcc') ?><br />
		<?php _e('[link_description] - описание файла', 'kcc') ?><br />
		<?php _e('[edit_link] - УРЛ на редактирование ссылки', 'kcc') ?>
	</small>
	<?php
}