<?php
/**
 * HTML Output for the PMS Settings page -> Tax tab
 */
?>

<div id="pms-settings-tax" class="pms-tab <?php echo ( $active_tab == 'tax' ? 'tab-active' : '' ); ?>">

    <?php do_action( 'pms-settings-page_tab_tax_before_content', $options ); ?>

    <div class="cozmoslabs-form-subsection-wrapper" id="enable-tax">

        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Tax Activation', 'paid-member-subscriptions' ); ?></h4>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="enable-tax-rates"><?php esc_html_e( 'Enable Tax Rates', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="enable-tax-rates" name="pms_tax_settings[enable_tax]" value="1" <?php echo ( isset( $options['enable_tax'] ) ? checked($options['enable_tax'], '1', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="enable-tax-rates"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="enable-tax-rates" class="cozmoslabs-description"><?php esc_html_e( 'Enable taxes and tax calculations on all subscription plan purchases.', 'paid-member-subscriptions' ); ?></label>
            </div>
        </div>

        <?php do_action( 'pms-settings-enable_tax_after_content', $options ); ?>

    </div>

    <div class="cozmoslabs-form-subsection-wrapper" id="tax-options">

        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Tax Options', 'paid-member-subscriptions' ); ?></h4>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-column-radios-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Prices entered with tax', 'paid-member-subscriptions' ) ?></label>

            <?php // Set default
            if ( !isset($options['prices_include_tax'] ) )
            $options['prices_include_tax'] = 'no'; ?>

            <div class="cozmoslabs-radio-inputs-column">
                <label>
                    <input type="radio" name="pms_tax_settings[prices_include_tax]" value="yes" <?php checked($options['prices_include_tax'], 'yes', true); ?> />
                    <span><?php esc_html_e( 'Yes, I will enter subscription prices inclusive of tax', 'paid-member-subscriptions' ); ?></span>
                </label>

                <label>
                    <input type="radio" name="pms_tax_settings[prices_include_tax]" value="no"  <?php checked($options['prices_include_tax'], 'no', true); ?> />
                    <span><?php esc_html_e( 'No, I will enter subscription prices exclusive of tax', 'paid-member-subscriptions' ); ?></span>
                </label>
            </div>

        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="default-billing-country"><?php esc_html_e( 'Default Billing Country', 'paid-member-subscriptions' ) ?></label>

            <select id="default-billing-country" class="pms-chosen" name="pms_tax_settings[default-billing-country]">
                <option value="" <?php selected( isset( $options['default-billing-country'] ) ? $options['default-billing-country'] : '', '') ?>><?php esc_html_e( 'None', 'paid-member-subscriptions' ); ?></option>

                <?php
                    $countries = pms_get_countries();
                    unset( $countries[''] );

                    foreach( $countries as $key => $value )
                        echo '<option value="'.esc_attr( $key ).'" '. selected( isset( $options['default-billing-country'] ) ? $options['default-billing-country'] : '', $key ) .'>'.esc_html( $value ).'</option>';
                ?>

            </select>

            <p class="cozmoslabs-description cozmoslabs-description-align-right">
                <?php esc_html_e( 'Pre-select the Billing Country field from the form.', 'paid-member-subscriptions' ); ?>
            </p>
        </div>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="eu-vat-enable"><?php esc_html_e( 'Enable EU VAT', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="eu-vat-enable" name="pms_tax_settings[eu-vat-enable]" value="1" <?php echo ( isset( $options['eu-vat-enable'] ) ? checked($options['eu-vat-enable'], '1', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="eu-vat-enable"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="eu-vat-enable" class="cozmoslabs-description"><?php esc_html_e( 'Enable EU VAT on subscription purchases.', 'paid-member-subscriptions' ); ?></label>
            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Your customers will also be able to provide a VAT ID in order to be exempt of paying the vat.', 'paid-member-subscriptions' ); ?></p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'The plugin already includes the VAT rates for EU countries so you don\'t have to add them, but you can overwrite them below if necessary.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper" id="eu-merchant__wrapper">
            <label class="cozmoslabs-form-field-label" for="merchant-vat-country"><?php esc_html_e( 'Merchant VAT Country', 'paid-member-subscriptions' ) ?></label>

            <select id="merchant-vat-country" class="pms-chosen" name="pms_tax_settings[merchant-vat-country]">

                <?php
                    foreach( pms_in_tax_get_eu_vat_countries( true ) as $key => $value )
                        echo '<option value="'.esc_attr( $key ).'" '. selected( isset( $options['merchant-vat-country'] ) ? $options['merchant-vat-country'] : '', $key ) .'>'.esc_html( $value ).'</option>';
                ?>

            </select>

            <p class="cozmoslabs-description cozmoslabs-description-align-right">
                <?php esc_html_e( 'Select the Country where the VAT MOSS of your business is registered.', 'paid-member-subscriptions' ); ?>
            </p>
        </div>

        <?php do_action( 'pms-settings-page_tax_options_after_content', $options ); ?>

    </div>

    <div class="cozmoslabs-form-subsection-wrapper" id="tax-rates">

        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e('Tax Rates', 'paid-member-subscriptions'); ?></h4>

        <div class="cozmoslabs-custom-tax-rates-wrapper">

        <?php if(isset( $tax_rates ) && !empty( $tax_rates )): ?>

            <div id="custom-tax-rates">

                <table id="pms_custom_tax_rates" class="wp-list-table widefat">
                    <thead>
                    <tr>
                        <th scope="col" class="manage-column"><?php esc_html_e('Country', 'paid-member-subscriptions'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('State', 'paid-member-subscriptions'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('City', 'paid-member-subscriptions'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Rate %', 'paid-member-subscriptions'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Name', 'paid-member-subscriptions'); ?></th>
                        <th scope="col" class="manage-column">&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach( $tax_rates as $i => $tax_rate ): ?>
                        <tr id="pms_tax_rate_row_<?php echo esc_attr( $tax_rate['id'] ); ?>" <?php echo ( ( ($i % 2) == 0 ) ? 'class="alternate"' : '' ); ?>>
                            <td><?php echo empty($tax_rate['tax_country']) ? '*' : esc_html( $tax_rate['tax_country'] ); ?></td>
                            <td><?php echo empty($tax_rate['tax_state']) ? '*' : esc_html( $tax_rate['tax_state'] ); ?></td>
                            <td><?php echo empty($tax_rate['tax_city']) ? '*' : esc_html( $tax_rate['tax_city'] ); ?></td>
                            <td><?php echo number_format($tax_rate['tax_rate'], 2); ?>%</td>
                            <td><?php echo esc_html( $tax_rate['tax_name'] ); ?></td>
                            <td width="25px"><a title="<?php esc_html_e('Delete','paid-member-subscriptions'); ?>" href="" class="pms-tax-rate-remove alignright cozmoslabs-remove-item" data-id="<?php echo esc_attr( $tax_rate['id'] ); ?>"><span class="dashicons dashicons-no-alt"></span></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

            <div class="cozmoslabs-custom-tax-rates-actions">
                <a href="<?php
                    echo esc_url( add_query_arg( array( 'action' => 'pms_export_tax_rates', 'pms-tax-nonce' => wp_create_nonce( 'pms_tax_export_rates' ) ), admin_url( 'admin-ajax.php' ) ) );
                    ?>" class="button button-secondary"><?php esc_html_e('Export Tax Rates', 'paid-member-subscriptions'); ?></a>

                <a href="<?php
                    echo esc_url( wp_nonce_url (add_query_arg( array('pms-action' => 'pms_clear_tax_rates'), pms_get_current_page_url() ), 'pms_clear_tax_rates', 'pms-tax' ) );
                    ?>" class="button button-secondary" onclick="if(!confirm('<?php esc_html_e('Are you sure? This will delete all tax rates from the database', 'paid-member-subscriptions'); ?>')){return false;}"><?php esc_html_e('Clear Tax Rates', 'paid-member-subscriptions'); ?></a>
            </div>



        <?php else: ?>
            <div id="custom-tax-rates">
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><strong><?php esc_html_e('No custom tax rates have been set. You can add some by uploading a CSV file below.', 'paid-member-subscriptions'); ?></strong></p>
            </div>
        <?php endif; ?>

        </div>

        <div class="cozmoslabs-form-field-wrapper">

            <label class="cozmoslabs-form-field-label" for="default-tax-rate"><?php esc_html_e('Default Tax Rate (%)', 'paid-member-subscriptions'); ?></label>

            <input type="text" id="default-tax-rate" name="pms_tax_settings[default_tax_rate]" value="<?php echo isset( $options['default_tax_rate'] ) ? (float)$options['default_tax_rate'] : 0; ?>" />

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e('Enter a tax percentage. Customers not in a specific tax rate (defined above) will be charged this rate.', 'paid-member-subscriptions'); ?></p>

        </div>

        <div class="cozmoslabs-form-field-wrapper">

            <label class="cozmoslabs-form-field-label" for="tax-rates-csv"><?php esc_html_e('Upload Tax Rates', 'paid-member-subscriptions'); ?></label>

            <input type="file" id="tax-rates-csv" name="pms_tax_rates_csv" />

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e('Upload Tax Rates via a CSV file. Use this to select a csv file, then to upload, just click "Save Settings" button.', 'paid-member-subscriptions')?> </p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><a href="<?php echo esc_url( PMS_IN_TAX_PLUGIN_DIR_URL . 'sample-data/sample_tax_rates.csv' ); ?>"> <?php esc_html_e('Download this sample CSV Tax Rates file', 'paid-member-subscriptions')?></a> <?php esc_html_e(' and modify it by adding your required tax rates.','paid-member-subscriptions')?></p>


        </div>

        <?php do_action('pms-settings-page_tax_rates_after_content', $options); ?>

    </div>

</div>
