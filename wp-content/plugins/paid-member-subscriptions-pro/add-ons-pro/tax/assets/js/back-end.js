jQuery(document).ready(function($) {

    $('body').on('click', '.pms-tax-rate-remove', function(e) {
        e.preventDefault();
        if(confirm(PMSTaxOptions.taxRateRemoveMessage)) {
            var id = $(this).data('id');
            var ajax_data = {
                id: id,
                action: 'pms_remove_tax_rate',
                pms_tax_nonce: PMSTaxOptions.tax_nonce
            }
            $.post(ajaxurl, ajax_data, function (response){
                if ( response.success = true ){
                    $('#pms_tax_rate_row_'+id).remove();
                }
            })
        }
    });

    pms_tax_merchat_country_display()

    jQuery('#eu-vat-enable').on( 'change', function() {
        pms_tax_merchat_country_display()
    })

    if( $.fn.chosen != undefined )
        $('.pms-chosen').chosen( { search_contains: true } )

});

function pms_tax_merchat_country_display(){

    if( jQuery('#eu-vat-enable').is(':checked') )
        jQuery('#eu-merchant__wrapper').show()
    else
        jQuery('#eu-merchant__wrapper').hide()

}
