jQuery( function(){
    /* add new rule */
    jQuery( "#pms_subscription_content_restriction" ).on( "click", '.pms-add-rule', function(e) {
        e.preventDefault();

        add_anchor = jQuery(this);
        postID = add_anchor.data('post-id');
        nrOfRules = add_anchor.data('nr-of-rules');

        jQuery.post(ajaxurl, { 'action': 'pms_add_new_restriction_rule', 'post_id': postID, 'current_nr_of_rules' : nrOfRules }, function(response) {
            add_anchor.parent().after(response);
            add_anchor.parent().remove();
        });
    });

    /* show/modify the "add taxonomy" link */
    jQuery( "#pms_subscription_content_restriction" ).on( "change", '.pms-post-type-select', function() {
        postTypeSelect = jQuery(this);
        postType = postTypeSelect.val();

        postTypeSelect.closest('.pms-meta-box-field-wrapper').siblings('.pms-meta-box-field-wrapper').remove();
        jQuery( '.pms-add-taxonomy', postTypeSelect.parents('.pms_content-rule') ).attr('data-current-post-type', postType );
        jQuery( '.pms-add-taxonomy', postTypeSelect.parents('.pms_content-rule')).removeClass('disabled');

        // Add data information to show taxonomy lines
        postTypeSelect.closest('.pms_content-rule-inside').attr('data-taxonomy-lines', '1');
    });

    /* generate taxonomy dropdown */
    jQuery( "#pms_subscription_content_restriction" ).on( "click", '.pms-add-taxonomy', function(e) {
        e.preventDefault();

        var add_anchor = jQuery(this);
        postType = add_anchor.attr('data-current-post-type');
        nrOfRules = add_anchor.attr('data-nr-of-rules');

        jQuery.post(ajaxurl, { 'action': 'pms_add_new_taxonomy_in_rule', 'current_post_type': postType, 'current_nr_of_rules' : nrOfRules }, function(response) {
            add_anchor.before(response);
        });
    });

    /* remove taxonomy */
    jQuery( "#pms_subscription_content_restriction" ).on( "click", '.pms-remove-taxonomy', function(e) {
        e.preventDefault();
        jQuery(this).closest('.pms-meta-box-field-wrapper').remove();
    });

    /* when selecting a taxonomy show the terms */
    jQuery( "#pms_subscription_content_restriction" ).on( "change", '.pms-taxonomy-rule-change', function(e) {
        taxSelect = jQuery(this);
        taxonomy = taxSelect.val();
        postType = taxSelect.closest('.pms_content-rule').find('.pms-post-type-select').val();
        postId = taxSelect.closest('.inside').find('.pms-add-rule').data('post-id');

        jQuery.post(ajaxurl, { 'action': 'pms_render_taxonomy', 'taxonomy': taxonomy, 'post-type': postType, 'post_id': postId }, function(response) {
            taxSelect.siblings('ul').remove();
            taxSelect.next('a').after(response);
        });
    });

    /* remove rule */
    jQuery( "#pms_subscription_content_restriction" ).on( "click", '.pms_-remove-rule a', function(e) {
        e.preventDefault();
        jQuery(this).parents('.pms_content-rule').remove();
        /* reindex all remaining rows so they are in order */

        remainingRules =  jQuery('#pms_subscription_content_restriction .inside .pms_content-rule').length;
        if( remainingRules != 0 ){
            counter = 1;
            jQuery('#pms_subscription_content_restriction .inside .pms_content-rule').each( function(){
                jQuery( '.pms-taxonomy-rule-change', jQuery(this)).attr( 'name', 'pms_content_rule_taxonomy_'+ counter +'[]' );
                counter++;
            });

            jQuery( 'a.pms-add-rule').attr('data-nr-of-rules', remainingRules+1 )
        }
    });


    /**
     * Add Link to PMS Docs next to metabox title
     * */
    jQuery(document).ready( function () {
        jQuery(function(){
            jQuery('#pms_subscription_content_restriction .postbox-header h2').append('<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/global-content-restriction/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>');
        });
    });

} );