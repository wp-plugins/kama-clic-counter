tinymce.create('tinymce.plugins.KCC', {
	init : function( editor, url ){
		editor.addButton('kcc', {
			title : 'Kama Click Counter Download Shortcode',
			onclick: function() {
				var url = prompt('Download URL:', 'http://');
				if( url )
					editor.selection.setContent('[download url="' + url + '"]');
			},
			text: 'DW'
		});		
	}
});
// Register plugin
tinymce.PluginManager.add( 'KCC', tinymce.plugins.KCC );
