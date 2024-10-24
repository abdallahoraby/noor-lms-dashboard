<?php
/**
 * HTML Output for the PMS Settings page -> Invoices tab
 */

$invoice_number = get_option( 'pms_inv_invoice_number', '1' );

$image_src = false;

if( !empty( $options['logo'] ) )
    $image_src = wp_get_attachment_url( $options['logo'] );

?>

<div id="pms-settings-invoices" class="pms-tab <?php echo ( $active_tab == 'invoices' ? 'tab-active' : '' ); ?>">

    <?php do_action( 'pms-settings-page_tab_invoices_before_content', $options ); ?>

    <div class="cozmoslabs-form-subsection-wrapper" id="invoice-details">

        <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Invoice Details', 'paid-member-subscriptions' ); ?></h3>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
            <label class="cozmoslabs-form-field-label" for="invoices-company-details"><?php esc_html_e( 'Company Details', 'paid-member-subscriptions' ) ?></label>
            <?php wp_editor( ( isset($options['company_details']) ? wp_kses_post($options['company_details']) : '' ), 'invoices-company-details', array( 'textarea_name' => 'pms_invoices_settings[company_details]', 'editor_height' => 180 ) ); ?>
            <p class="cozmoslabs-description"> <?php echo wp_kses_post( __( 'Enter your company details as you would like them to appear on the invoice. ( Company Name, Address, Country, etc.)','paid-member-subscriptions' ) ) ?></p>
            <p class="cozmoslabs-description cozmoslabs-notice-message"> <?php echo wp_kses_post( __( 'NOTE: Company details are required to create invoices.','paid-member-subscriptions' ) ) ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
            <label class="cozmoslabs-form-field-label" for="invoices-notes"><?php esc_html_e( 'Invoice Notes', 'paid-member-subscriptions' ) ?></label>
            <?php wp_editor( ( isset($options['notes']) ? wp_kses_post($options['notes']) : __( 'Thank you for your business!' ,'paid-member-subscriptions') ), 'invoices-notes', array( 'textarea_name' => 'pms_invoices_settings[notes]', 'editor_height' => 180 ) ); ?>
            <p class="cozmoslabs-description"> <?php esc_html_e('These notes will appear at the bottom of each invoice.','paid-member-subscriptions') ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="invoices-company-logo"><?php esc_html_e( 'Company Logo', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-invoice-company-logo">
            	<a href="#" class="pms-invoices-company-logo-upload <?php echo empty( $image_src ) ? 'button button-secondary' : ''; ?>">

                    <?php if( !empty( $image_src ) ) : ?>
                        <img src="<?php echo esc_url( $image_src ); ?>" />
                    <?php else : ?>
                        <?php esc_html_e( 'Upload Image', 'paid-member-subscriptions' ) ?>
                    <?php endif; ?>

                </a>

            	<input type="hidden" name="pms_invoices_settings[logo]" value="<?php echo isset( $options['logo'] ) ? esc_attr( $options['logo'] ) : ''; ?>" />
            	<a href="#" class="pms-invoices-company-logo-remove" style="<?php echo empty( $image_src ) ? 'display:none' : ''; ?>">Remove image</a>

            </div>

            <p class="cozmoslabs-description cozmoslabs-description-align-right"> <?php esc_html_e( 'The logo will appear in the top-right corner of the Invoice.','paid-member-subscriptions') ?></p>
        </div>

        <?php do_action( 'pms-settings-page_invoice_details_after_content', $options ); ?>

    </div>

    <div class="cozmoslabs-form-subsection-wrapper" id="invoice-settings">

        <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Invoice Settings', 'paid-member-subscriptions' ); ?></h3>
        <p class="cozmoslabs-description"> <?php echo wp_kses_post( sprintf(__('For invoice title and format you can use the following tags: <code>%s</code>, <code>%s</code>, <code>%s</code>, <code>%s</code>','paid-member-subscriptions' ), '{{number}}', '{{DD}}', '{{MM}}', '{{YYYY}}' )) ?></p>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="invoices-title"><?php esc_html_e( 'Invoice Title', 'paid-member-subscriptions' ) ?></label>
            <input type="text" id="invoice-title" class="widefat" name="pms_invoices_settings[title]" value="<?php echo ( isset($options['title']) ? esc_attr( $options['title'] ) : esc_html__('Invoice','paid-member-subscriptions') ) ?>">
            <p class="cozmoslabs-description cozmoslabs-description-align-right"> <?php esc_html_e('Depending on your country fiscal regulations you can change it to things like: Tax Invoice etc.','paid-member-subscriptions') ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="invoices-format"><?php esc_html_e( 'Format', 'paid-member-subscriptions' ) ?></label>
            <input type="text" id="invoices-format" class="widefat" name="pms_invoices_settings[format]" value="<?php echo ( isset($options['format']) ? esc_attr( $options['format'] ) : '{{number}}' ) ?>">
            <p class="cozmoslabs-description cozmoslabs-description-align-right"> <?php echo wp_kses_post( sprintf( __('<strong>Note</strong>: <code>%s</code> is required.','paid-member-subscriptions' ), '{{number}}' ) ) ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="invoices-font"><?php esc_html_e( 'Font', 'paid-member-subscriptions' ); ?></label>

            <select id="invoices-font" name="pms_invoices_settings[font]">
                <option value="dejavusans" <?php ( isset( $options['font'] ) ? selected( $options['font'],  'dejavusans', true ) : ''); ?>><?php esc_html_e( 'English / Cyrillic', 'paid-member-subscriptions' ); ?></option>
                <option value="aealarabiya" <?php ( isset( $options['font'] ) ? selected( $options['font'], 'aealarabiya', true ) : ''); ?>><?php esc_html_e( 'Arabic', 'paid-member-subscriptions' ); ?></option>
                <option value="cid0cs" <?php ( isset( $options['font'] ) ? selected( $options['font'],      'cid0cs', true ) : ''); ?>><?php esc_html_e( 'Chinese (simplified)', 'paid-member-subscriptions' ); ?></option>
                <option value="cid0ct" <?php ( isset( $options['font'] ) ? selected( $options['font'],      'cid0ct', true ) : ''); ?>><?php esc_html_e( 'Chinese (traditional)', 'paid-member-subscriptions' ); ?></option>
                <option value="cid0jp" <?php ( isset( $options['font'] ) ? selected( $options['font'],      'cid0jp', true ) : ''); ?>><?php esc_html_e( 'Japanese', 'paid-member-subscriptions' ); ?></option>
                <option value="cid0kr" <?php ( isset( $options['font'] ) ? selected( $options['font'],      'cid0kr', true ) : ''); ?>><?php esc_html_e( 'Korean', 'paid-member-subscriptions' ); ?></option>
            </select>

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Select the font to be used on the Invoice.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="invoices-pre-generate-invoices"><?php esc_html_e( 'Pre-generate Invoices for Payments', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="invoices-pre-generate-invoices" name="pms_invoices_settings[pre_generate_invoices]" value="1" <?php echo ( isset( $options['pre_generate_invoices'] ) ? checked($options['pre_generate_invoices'], '1', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="invoices-pre-generate-invoices"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="invoices-pre-generate-invoices" class="cozmoslabs-description"><?php esc_html_e( 'By enabling this, Invoices will be available right after a payment is added, instead of waiting for the payment to be completed.', 'paid-member-subscriptions' ); ?></label>
            </div>
        </div>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="invoices-reset-invoice-counter"><?php esc_html_e( 'Reset Invoice Counter', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="invoices-reset-invoice-counter" name="pms_invoices_settings[reset_invoice_counter]" value="1" <?php echo ( isset( $options['reset_invoice_counter'] ) ? checked($options['reset_invoice_counter'], '1', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="invoices-reset-invoice-counter"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="invoices-reset-invoice-counter" class="cozmoslabs-description"><?php esc_html_e( 'Enable this if you want to reset the invoice counter.', 'paid-member-subscriptions' ); ?></label>
            </div>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="invoices-next-invoice-number"><?php esc_html_e( 'Next Invoice Number', 'paid-member-subscriptions' ) ?></label>
            <input type="number" id="invoices-next-invoice-number" class="widefat" name="pms_inv_invoice_number" min="1" readonly disabled value="<?php echo esc_attr( $invoice_number ); ?>">
            <p class="cozmoslabs-description cozmoslabs-description-align-right"> <?php esc_html_e('Enter the next invoice number. Default value is 1 and increments every time an invoice is issued. Existing invoices will not be changed.','paid-member-subscriptions') ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="invoices-reset-yearly"><?php esc_html_e( 'Reset Yearly', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="invoices-reset-yearly" name="pms_invoices_settings[reset_yearly]" value="1" <?php echo ( isset( $options['reset_yearly'] ) ? checked($options['reset_yearly'], '1', false) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="invoices-reset-yearly"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="invoices-reset-yearly" class="cozmoslabs-description"><?php esc_html_e( 'Automatically reset invoice numbers on new year\'s day. Resets invoice number to 1.', 'paid-member-subscriptions' ); ?></label>
            </div>
        </div>

        <?php do_action( 'pms-settings-page_invoice_settings_after_content', $options ); ?>

    </div>

</div>
