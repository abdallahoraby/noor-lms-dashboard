<?php
/**
 * Admin Import step 2 view.
 *
 * @since 3.0.0
 */
?>

<h4><?php _e( 'Importing...', 'learnpress-import-export' ); ?></h4>

<input type="hidden" name="save_import" value="<?php echo $_REQUEST['save_import']; ?>"/>
<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'lpie-import-file' ); ?>"/>
<input type="hidden" name="import-user-file" value="<?php echo $_REQUEST['import-user-file']; ?>"/>

<script type="text/javascript">
    jQuery(function ($) {
        $('#import-user-form').submit();
    })
</script>