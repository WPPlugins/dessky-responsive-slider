	(function(){
		tinymce.PluginManager.requireLangPack('blist');
		tinymce.create('tinymce.plugins.blist', {
			init : function(ed, url){
				ed.addCommand('dessky_responsive_slider', function(){
					ilc_sel_content = tinyMCE.activeEditor.selection.getContent();
					tinyMCE.activeEditor.selection.setContent('[dessky_responsive_slider]' + ilc_sel_content);
				});
				ed.addButton('rs_code', {
					title: 'Dessky Responsive Slider - Shortcode',
					cmd: 'dessky_responsive_slider'
				});
			},
			createControl : function(n, cm){
				return null;
			},
		});
		tinymce.PluginManager.add('blist', tinymce.plugins.blist);
	})();
