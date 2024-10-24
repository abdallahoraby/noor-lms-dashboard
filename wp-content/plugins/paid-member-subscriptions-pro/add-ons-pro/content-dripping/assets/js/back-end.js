jQuery( function($) {

    var meta_name = "pms-content-dripping-rules";
    var $contentDrippingRulesTable = $('#pms-content-dripping-rules tbody');
    var pmstkn = $('input[type=hidden][name=pmstkn]').val();


    // Select discount code on click
    jQuery('.pms-dp-contents-table-shortcode').click( function() {
        this.select();
    });


    /*
     * Prepare the page
     */
    $(document).ready( function() {

        $('body.post-type-pms-content-dripping #delete-action').remove();
        $('body.post-type-pms-content-dripping .edit-post-status').remove();
        $('body.post-type-pms-content-dripping #visibility').remove();
        $('body.post-type-pms-content-dripping #minor-publishing-actions').remove();
        $('body.post-type-pms-content-dripping div.misc-pub-post-status').remove();
        $('body.post-type-pms-content-dripping #misc-publishing-actions').hide();
        $('body.post-type-pms-content-dripping input#publish').val('Save Content Dripping Set');

        calculateRowIndexes();

        if( $contentDrippingRulesTable.find('tr').length == 0 )
            $('#pms-content-dripping-add-rule').trigger('click');
    });


    /*
     * Initialise sortable for the dripping content rules
     */
    if($.fn.sortable != undefined ) {
        $contentDrippingRulesTable.sortable({
            items : '> tr',
            handle : '.pms-handle',
            update : function() {
                calculateRowIndexes();
            }
        });
    }


    /*
     * Remove drip content rule when hitting the X link
     */
    $(document).on( 'click', '.pms-content-dripping-remove-rule', function(e) {
        e.preventDefault();

        $(this).closest('tr:not(.loading)').remove();

        if( $contentDrippingRulesTable.find('tr').length == 0 )
            $('#pms-content-dripping-add-rule').trigger('click');

        calculateRowIndexes();
    });


    /*
     * Initialise chosen
     *
     */
    if( $.fn.chosen != undefined ) {

        $('.pms-chosen').chosen();

    }


    /*
     * Add new row
     */
    $('#pms-content-dripping-add-rule').click( function(e) {
        e.preventDefault();

        var output;

        output  = '<tr class="pms-content-dripping-rule">';

            // Add order row cell
            output += '<td>';
                output += '<span class="pms-handle"></span><div class="spinner"></div>';
            output += '</td>';

            // Add delay row cell
            output += '<td>';
                output += '<select name="' + meta_name + '[][delay]">';
                    for( var i = 0; i <= 365; i++ )
                        output += '<option value="' + i + '">' + i + '</option>';
                output += '</select>';

                output += '<select name="' + meta_name + '[][delay_unit]">';
                    for( var key in pmsDelayUnits ) {
                       output +=  '<option value="' + key + '">' + pmsDelayUnits[key] + '</option>';
                    }
                output += '</select>';
            output += '</td>';

            // Add CPT row cell
            output += '<td>';
                output += '<select name="' + meta_name + '[][post_type]" class="widefat pms-select-post-type">';
                    output += '<option value="0">Choose...</option>';

                    for( var key in pmsPostTypes ) {
                        output +=  '<option value="' + key + '">' + pmsPostTypes[key] + '</option>';
                    }
                output += '</select>';
            output += '</td>';

            // Add Type row cell
            output += '<td>';
                output += '<select name="' + meta_name + '[][type]" class="widefat pms-select-type" disabled>';
                    output += '<option value="0">Choose...</option>';
                    output += '<optGroup label="By Post"><option value="pms_list_of_posts">List of Posts</option></optGroup>';
                output += '</select>';
            output += '</td>';

            // Add content row cell
            output += '<td><select name="' + meta_name + '[][content][]" multiple disabled class="widefat pms-chosen pms-select-content"></select></td>';

            // Add close link
            output += '<td>';
                output += '<a href="#" class="pms-content-dripping-remove-rule cozmoslabs-remove-item" title="Remove this rule"><span class="dashicons dashicons-no"></span></a>';
            output += '</td>';

        output += '</tr>';

        // Append output and recalculate row indexes
        $contentDrippingRulesTable.append( output );
        $contentDrippingRulesTable.find('.pms-content-dripping-rule').last().find('.pms-chosen').chosen();
        calculateRowIndexes();

    });


    /*
     * Handle when the admin changes the value of the Post Type Select
     * This should load the taxonomies in the Type select field
     *
     */
    $(document).on( 'change', '.pms-select-post-type', function() {

        // Get parent row and it's index
        var $parentRow = $(this).closest('tr');
        var rowIndex = $parentRow.attr('data-index');

        if( $(this).val() == 0 ) {

            $parentRow.find('.pms-select-type, .pms-select-content').attr('disabled', true).trigger('chosen:updated');

        } else {

            // Add loading
            $parentRow.addClass('loading');
            $parentRow.find('input, select').attr('disabled', true).trigger('chosen:updated');

            var data = {
                action    : 'get_select_type_options',
                pmstkn    : pmstkn,
                row_index : rowIndex,
                post_type : $(this).val()
            };

            // Make the ajax call
            $.post( ajaxurl, data, function( response ) {

                response = JSON.parse( response );

                $row = $('.pms-content-dripping-rule[data-index="' + parseInt(response.row_index) + '"]');

                // Remove the last optGroup which contains the taxonomies
                $selectType = $row.find('.pms-select-type');
                $selectType.find('optGroup[data-tax="true"]').remove();

                // Add taxonomies if there are any
                var taxonomies = '';
                for( taxonomy in response.taxonomies ) {
                    taxonomies += '<option value="' + taxonomy + '">' + response.taxonomies[taxonomy] + '</option>';
                }

                if( taxonomies != '' )
                    $selectType.append( '<optGroup data-tax="true" label="By Taxonomy">' + taxonomies + '</optGroup>' );

                if( $selectType.val() != 0 ) {
                    $selectType.find('option').removeAttr('selected');
                    $selectType.find('option[value="0"]').attr('selected', true);
                    $selectType.trigger('change');
                }

                // Remove loading
                $row.removeClass('loading');
                $row.find('input, select').attr('disabled', false).trigger('chosen:updated');

            });

        }

    });


    /*
     * Handle when the admin changes the value of the Type selection
     * This should load contents in the Content chosen multiple select field
     * given the post type and type
     *
     */
    $(document).on( 'change', '.pms-select-type', function() {

        // Get parent row and it's index
        var $parentRow = $(this).closest('tr');
        var rowIndex = $parentRow.attr('data-index');

        if( $(this).val() == 0 ) {

            $parentRow.find('.pms-select-content').html('').attr('disabled', true).trigger('chosen:updated');

        } else {

            // Add loading
            $parentRow.addClass('loading');
            $parentRow.find('input, select').attr('disabled', true).trigger('chosen:updated');

            var data = {
                action      : 'get_select_content_options',
                pmstkn      : pmstkn,
                row_index   : rowIndex,
                post_type   : $parentRow.find('.pms-select-post-type').val(),
                type        : $(this).val()
            };

            // Make the ajax call
            $.post( ajaxurl, data, function( response ) {

                response = JSON.parse( response );

                $row = $('.pms-content-dripping-rule[data-index="' + parseInt(response.row_index) + '"]');

                $selectContent = $row.find('.pms-select-content');
                $selectContent.html('');

                var contents = '';
                for( object_id in response.data )
                    contents += '<option value="' + object_id + '">' + response.data[object_id] + '</option>';

                $selectContent.html(contents);
                $selectContent.attr('disabled', false).val('').trigger('chosen:updated');

                // Remove loading
                $row.removeClass('loading');
                $row.find('input, select').attr('disabled', false).trigger('chosen:updated');

            });


        }

    });


    /*
     * Add indexes to all content dripping rules so we know the order
     */
    function calculateRowIndexes() {
        $contentDrippingRulesTable.children('tr').each( function( index ) {
            $(this).attr( 'data-index', index );

            $(this).find('select').each( function() {

                var element_attr_name = $(this).attr('name');

                var element_attr_name_beg = element_attr_name.substr( 0, element_attr_name.indexOf('[') + 1 );
                var element_attr_name_end = element_attr_name.substr( element_attr_name.indexOf(']'), element_attr_name.length - 1 );

                $(this).attr( 'name', element_attr_name_beg + index + element_attr_name_end );
            });

        });
    }


    /**
     * Add Link to PMS Docs next to page title
     * */
    $(document).ready( function () {
        $(function(){
            $('.wp-admin.edit-php.post-type-pms-content-dripping .wrap .wp-heading-inline').append('<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/content-dripping/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>');
        });
    });

});