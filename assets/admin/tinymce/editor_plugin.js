// http://blog.rapiddg.com/2009/10/writing-a-tinymce-plugin-in-wordpress/

(function() {
	tinymce.create('tinymce.plugins.FootballPoolPlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceFootballPool', function() {
				ed.windowManager.open({
					file : url + '/tinymce-dialog.php',
					width : 500 + parseInt(ed.getLang('footballpool.delta_width', 0)),
					height : 300 + parseInt(ed.getLang('footballpool.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});
			
			// Register buttons
			ed.addButton(
				'footballpool', 
				{ 
					title : 'Add Football Pool Shortcodes', 
					cmd : 'mceFootballPool', 
					image : url + '/footballpool-tinymce-16.png'
				}
			);
		},
		
		getInfo : function() {
			return {
				longname : 'Football Pool Insert Shortcodes',
				author : 'Antoine Hurkmans',
				authorurl : 'http://wordpressfootballpool.wordpress.com/',
				infourl : 'http://wordpress.org/extend/plugins/football-pool/',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('footballpool', tinymce.plugins.FootballPoolPlugin);
})();