(function() {
    tinymce.PluginManager.add('custom_shortcode_button', function(editor, url) {
        editor.addButton('custom_shortcode_button', {
            text: 'Add Divider',
            icon: false,
            onclick: function() {
                editor.insertContent('<h3 class="MsoNormal" style="box-sizing: border-box; margin-top: 0px; margin-bottom: 0.5rem; font-weight: 500; line-height: 1.2; font-size: 1.75rem; color: #634aa5;"><span style="box-sizing: border-box; color: #311873;">❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖❖</span></h3>'); // Replace with your actual shortcode
            }
        });
    });
})();
