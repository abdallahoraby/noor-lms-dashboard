jQuery(document).ready(function() {

    if (window.matchMedia('(min-width: 1191px)').matches && jQuery('.pms-form-design-wrapper').width() < 1000) {
        jQuery('.pms-form-design-wrapper').addClass('wrapper-small');
    }

    //  Style 2 only
    if ( jQuery('.pms-form-design-wrapper.pms-form-style-2').length > 0 ) {
        let billingStateField = jQuery('.pms-form-design-wrapper.pms-form-style-2 .pms-billing-details .pms-billing-state');

        stateFieldLabelSpacing(billingStateField);

        billingStateField.on('DOMSubtreeModified', function() {
            stateFieldLabelSpacing(billingStateField);
        });
    }

    //  Style 3 only
    if ( jQuery('.pms-form-design-wrapper.pms-form-style-3').length > 0 ) {

        if ( jQuery('#pms_register-form .pms-form-fields-wrapper > .pms-gm-message').length > 0 ) {
            let inviteMessageHeight = jQuery('#pms_register-form .pms-form-fields-wrapper > .pms-gm-message').outerHeight();

            jQuery('#pms_register-form .pms-form-fields-wrapper .pms-field-subscriptions').css({
                'padding': '0',
                'border': 'none'
            });

            jQuery('#pms_register-form .pms-account-details-title').css({
                'padding-top': inviteMessageHeight + 'px',
                'margin-bottom': '-' + ( 115 + inviteMessageHeight ) + 'px'
            });
        }

        if ( jQuery('#pms_register-form #pms-paygates-wrapper input.pms_pay_gate[type="hidden"]').length > 0 ) {
            jQuery('#pms_register-form #pms-paygates-wrapper').css({
                'padding': '0',
                'border': 'none'
            });
        }

        jQuery('.pms-account-subscription-details-table').each(function() {
            let lastTableRow = jQuery(this).find('tr:last-of-type');

            if ( lastTableRow.hasClass('pms-account-subscription-details-table__actions') ) {
                lastTableRow.prev().addClass('last-table-row')
            } else {
                lastTableRow.addClass('last-table-row')
            }
        });

    }


    jQuery('.pms-field input:disabled').parent().addClass('disabled-field');

    pmsHandleFloatingLabels(jQuery('.pms-field:not(.pms-field-subscriptions, .pms-gdpr-field, .pms-field-type-select, .pms-field-type-select_state, :has(input#pms-delete-account)), #pms-subscription-plans-discount, form#pms_login p.login-username, form#pms_login p.login-password, form#pms-invite-members'));

    pmsFocusInOutSelectFields(jQuery('.pms-billing-details .pms-field-type-select, .pms-billing-details .pms-field-type-select_state'));

    animateSubscriptionHeader();

    jQuery('.pms-field.pms-field-subscriptions, .pms-upgrade__group').on('change', function(element) {
        pmsHandleFloatingLabels(jQuery('.pms-billing-details .pms-field, #pms-subscription-plans-discount'));

        if (jQuery('#pms-credit-card-information #pms-stripe-payment-elements').length > 0 && !jQuery(element.target).hasClass('pms_pay_gate')) {
            paymentSidebarElementsHidden();
            setTimeout(function() {
                paymentSidebarPosition();
                paymentSidebarElementsVisible();
            }, 700);
        }
        else {
            paymentSidebarPosition();
        }

    });

    jQuery('#pms_user_email').on('change', function() {
        pmsHandleFloatingLabels(jQuery('.pms-billing-details .pms-field.pms-billing-email'));
    });

    jQuery('.pms-form-design-wrapper #pms-paygates-wrapper').on('change', function() {
        jQuery('.pms-form-design-wrapper #pms-credit-card-information:not(#pms-update-payment-method-form #pms-credit-card-information)').css('opacity','0');
        setTimeout(function() {
            paymentSidebarPosition();
            jQuery('.pms-form-design-wrapper #pms-credit-card-information:not(#pms-update-payment-method-form #pms-credit-card-information)').css({
                'opacity': '1',
                'transition': 'opacity 250ms ease-in-out',

            });
        }, 100);
    });

    jQuery('.pms-form-design-wrapper .pms_pwyw_pricing').on('keyup click', function() {
        paymentSidebarElementsHidden();
        setTimeout(function() {
            paymentSidebarPosition();
            paymentSidebarElementsVisible();
        }, 100);
    });

    jQuery('.pms-form-design-wrapper .pms-billing-country').on('change', function() {
        setTimeout(function() {
            paymentSidebarPosition();
        }, 100);
    });

    jQuery('.pms-form-design-wrapper .pms-billing-state').on('DOMSubtreeModified', function() {
        pmsFocusInOutSelectFields(jQuery(this));
        pmsHandleFloatingLabels(jQuery(this));
    });


    if ( jQuery('.pms-form-design-wrapper nav.pms-account-navigation').length > 0 ) {
        handleLogoutButtonPositioning()
    }

});

jQuery(window).on("load", function() {

    if ( jQuery('#pms_register-form').length > 0 ||
        jQuery('#pms-register-form').length > 0 ||
        jQuery('#pms_new_subscription-form').length > 0 ||
        jQuery('#pms-change-subscription-form').length > 0 ||
        jQuery('#pms-renew-subscription-form').length > 0 ||
        jQuery('#pms-retry-payment-subscription-form').length > 0 )
    {
        markSelectedSubscriptionPlan();

        if (jQuery('#pms-credit-card-information #pms-stripe-payment-elements').length > 0) {
            setTimeout(function() {
                paymentSidebarPosition();
                paymentSidebarElementsVisible();
            }, 700);
        }
        else {
            paymentSidebarPosition();
            paymentSidebarElementsVisible();
        }
    }

});


/**
 * Reposition the Payment SideBar Elements on window resize
 */
jQuery(window).resize(function() {
    if(window.matchMedia('(min-width: 1191px)').matches) {
        let pmsFormWrapper = jQuery('#pms_register-form, #pms_new_subscription-form, #pms-change-subscription-form, #pms-renew-subscription-form, #pms-retry-payment-subscription-form');

        if (pmsFormWrapper.length > 0)
            paymentSidebarPosition();
    }
    else {
        const formButtons = [
            '.pms-form-design-wrapper input[type="submit"][name="pms_register"]',
            '.pms-form-design-wrapper input[type="submit"][name="pms_new_subscription"]',
            '.pms-form-design-wrapper input[type="submit"][name="pms_confirm_retry_payment_subscription"]',
            '.pms-form-design-wrapper input[type="submit"][name="pms_renew_subscription"]',
            '.pms-form-design-wrapper input[type="submit"][name="pms_change_subscription"]'
        ];

        jQuery('.pms-form-design-wrapper div#pms-paygates-wrapper').css({
            'position': 'unset',
            'width': '100%'
        });
        jQuery('.pms-form-design-wrapper #pms-credit-card-information').css({
            'position': 'unset'
        });
        jQuery('.pms-form-design-wrapper .pms-price-breakdown__holder').css({
            'position': 'unset',
            'padding': '30px',
            'margin-bottom': '30px'
        });
        jQuery(formButtons.join(',')).filter(':visible').first().css({
            'position': 'unset',
            'margin-left': 0
        });
    }
});


/**
 * Position The Payment Sidebar Elements
 */
function paymentSidebarPosition() {

    // Form Submit Buttons
    const formButtons = [
        '.pms-form-design-wrapper input[type="submit"][name="pms_register"]',
        '.pms-form-design-wrapper input[type="submit"][name="pms_new_subscription"]',
        '.pms-form-design-wrapper input[type="submit"][name="pms_confirm_retry_payment_subscription"]',
        '.pms-form-design-wrapper input[type="submit"][name="pms_renew_subscription"]',
        '.pms-form-design-wrapper input[type="submit"][name="pms_change_subscription"]'
    ];


    // Form Design Wrapper
    let wrapper = jQuery('#pms_register-form, #pms_new_subscription-form, #pms-change-subscription-form, #pms-renew-subscription-form, #pms-retry-payment-subscription-form'),
        wrapperHeight = wrapper.outerHeight(),
        wrapperWidth = wrapper.width(),
        wrapperOffsetTop = wrapper.offset().top,

        sidebarLeftPosition = wrapper.width() - 470,

        // Payment Sidebar Element Containers
        paymentMethod = jQuery('.pms-form-design-wrapper div#pms-paygates-wrapper'),
        creditCardInfo = jQuery('.pms-form-design-wrapper #pms-credit-card-information'),
        priceBreakdownTable = jQuery('.pms-form-design-wrapper .pms-price-breakdown__holder'),

        // Initialize Payment Sidebar Element Containers Heights
        paymentMethodHeight = 0,
        creditCardInfoHeight = 0,
        priceBreakdownTableHeight = 0,

        // Check for Form Submit Button and initialize height
        formSubmitButton = jQuery(formButtons.join(',')).filter(':visible').first(),
        formSubmitButtonHeight = 48,

        // setup offset variable
        offset = 0;

    // Calculate Payment Sidebar Element Containers Heights
    if (paymentMethod.length > 0) {
        if (jQuery('#pms-paygates-inner').length > 0) {
            paymentMethodHeight = paymentMethod[0].getBoundingClientRect().height + parseFloat(getComputedStyle(paymentMethod[0]).marginBottom) - offset;
        }
        else {
            paymentMethodHeight = 1;
        }
    }

    if (creditCardInfo.length > 0) {
        creditCardInfoHeight = creditCardInfo[0].getBoundingClientRect().height;

        if (getComputedStyle(creditCardInfo[0]).display !== "none") {
            creditCardInfoHeight += parseFloat(getComputedStyle(creditCardInfo[0]).marginBottom);
        }

    }

    if (priceBreakdownTable.length > 0 && priceBreakdownTable.is(":visible")) {
        priceBreakdownTableHeight = priceBreakdownTable.outerHeight(true);
    }

    formSubmitButtonHeight = formSubmitButton[0].getBoundingClientRect().height + parseFloat(getComputedStyle(formSubmitButton[0]).marginBottom);


    // Set Payment Sidebar
    if ( wrapperWidth >= 1000 ) {
        let distanceTop = jQuery(this).scrollTop(),
            paymentSidebarHeight = paymentMethodHeight + creditCardInfoHeight + priceBreakdownTableHeight;


        if ( paymentSidebarHeight === 0 ) {
            paymentSidebarHeight += formSubmitButtonHeight + 50;
        }
        else if ( priceBreakdownTableHeight === 0 ) {
            paymentSidebarHeight += formSubmitButtonHeight;
        }

        formSubmitButton.css({
            'margin-left': '30px'
        });

        wrapper.css({
            'min-height': paymentSidebarHeight + 'px'
        });

        if ( wrapperWidth >= 1000 && distanceTop > wrapperOffsetTop && distanceTop <  wrapperOffsetTop + wrapperHeight - paymentSidebarHeight ) {
            paymentSidebarScrollPosition();
        }
        else if (distanceTop <  wrapperOffsetTop + wrapperHeight - paymentSidebarHeight) {
            paymentSidebarTopPosition();
        }
        else {
            paymentSidebarBottomPosition();
        }

        wrapperHeight = wrapper.outerHeight();
        jQuery(window).scroll(function () {
            if ( jQuery('.pms-form-design-wrapper').length > 0 && window.matchMedia('(min-width: 1191px)').matches ) {
                let scrollTop = jQuery(this).scrollTop();

                if (scrollTop > wrapperOffsetTop && scrollTop < wrapperOffsetTop + wrapperHeight - paymentSidebarHeight) {
                    paymentSidebarScrollPosition();
                }
                else if (scrollTop >= wrapperOffsetTop + wrapperHeight - paymentSidebarHeight) {
                    paymentSidebarBottomPosition();
                }
                else {
                    paymentSidebarTopPosition();
                }
            }
        });
    }

    jQuery('.pms-subscription-plan label .pms-subscription-plan-price').each(function() {
        jQuery(this).css({
            'font-size': '16px'
        });
        adjustPriceLabelFontSize(this);
    });


    /**
     * Position The Payment Sidebar at the top
     */
    function paymentSidebarTopPosition() {
        paymentMethod.css({
            'position': 'absolute',
            'bottom': '',
            'top': '0',
            'left': sidebarLeftPosition + 'px',
            'width': '470px',
        });
        creditCardInfo.css({
            'position': 'absolute',
            'bottom': '',
            'top': paymentMethodHeight + 'px',
            'left': sidebarLeftPosition + 'px'
        });
        priceBreakdownTable.css({
            'position': 'absolute',
            'bottom': '',
            'top': paymentMethodHeight + creditCardInfoHeight + 'px',
            'left': sidebarLeftPosition + 'px'
        });
        formSubmitButton.css({
            'position': 'absolute',
            'bottom': '',
            'left': sidebarLeftPosition + 'px'
        });

        if (paymentMethodHeight + creditCardInfoHeight + priceBreakdownTableHeight === 0) {
            formSubmitButton.css({
                'top': '50px', // correctly position the Submit Button if no other payment elements are present
            });
        }

        else if ( priceBreakdownTableHeight > 0 ) {
            formSubmitButton.css({
                'top': paymentMethodHeight + creditCardInfoHeight + priceBreakdownTableHeight - formSubmitButtonHeight - 30 + 'px' // 30px ("Purchase Summary" table bottom padding)
            });
        }
        else {
            formSubmitButton.css({
                'top': paymentMethodHeight + creditCardInfoHeight + 'px'
            });
        }
    }


    /**
     * Position The Payment Sidebar when scrolling
     */
    function paymentSidebarScrollPosition(){
        let scrollSidebarLeftPosition = sidebarLeftPosition + wrapper.offset().left;

        paymentMethod.css({
            'position': 'fixed',
            'bottom': '',
            'top': '0',
            'left': scrollSidebarLeftPosition + 'px'
        });
        creditCardInfo.css({
            'position': 'fixed',
            'bottom': '',
            'top': offset + paymentMethodHeight + 'px',
            'left': scrollSidebarLeftPosition + 'px'
        });
        priceBreakdownTable.css({
            'position': 'fixed',
            'bottom': '',
            'top': offset + paymentMethodHeight + creditCardInfoHeight + 'px',
            'left': scrollSidebarLeftPosition + 'px'
        });
        formSubmitButton.css({
            'position': 'fixed',
            'bottom': '',
            'left': scrollSidebarLeftPosition + 'px'
        });

        if (paymentMethodHeight + creditCardInfoHeight + priceBreakdownTableHeight === 0) {
            formSubmitButton.css({
                'top': '50px',
            });
        }

        else if ( priceBreakdownTableHeight > 0 ) {
            formSubmitButton.css({
                'top': offset + paymentMethodHeight + creditCardInfoHeight + priceBreakdownTableHeight - formSubmitButtonHeight - 30 + 'px' // 30px ("Purchase Summary" table bottom padding)
            });
        }
        else {
            formSubmitButton.css({
                'top': offset + paymentMethodHeight + creditCardInfoHeight + 'px'
            });
        }
    }


    /**
     * Position The Payment Sidebar at the bottom
     */
    function paymentSidebarBottomPosition(){
        paymentMethod.css({
            'position': 'absolute',
            'top': '',
            'left': sidebarLeftPosition + 'px'
        });
        creditCardInfo.css({
            'position': 'absolute',
            'top': '',
            'left': sidebarLeftPosition + 'px'
        });
        priceBreakdownTable.css({
            'position': 'absolute',
            'top': '',
            'left': sidebarLeftPosition + 'px'
        });
        formSubmitButton.css({
            'position': 'absolute',
            'top': '',
            'left': sidebarLeftPosition + 'px'
        });

        if ( priceBreakdownTableHeight > 0 ) {
            paymentMethod.css({
                'bottom': creditCardInfoHeight + priceBreakdownTableHeight + 'px'
            });
            creditCardInfo.css({
                'bottom': priceBreakdownTableHeight + 'px'
            });
            priceBreakdownTable.css({
                'bottom': '0'
            });
            formSubmitButton.css({
                'bottom': '30px'
            });
        }
        else {
            paymentMethod.css({
                'bottom': creditCardInfoHeight + formSubmitButtonHeight + 'px'
            });
            creditCardInfo.css({
                'bottom': formSubmitButtonHeight + 'px'
            });
            formSubmitButton.css({
                'bottom': '0'
            });
        }
    }

}

/**
 * Hide Payment Sidebar Elements
 */
function paymentSidebarElementsHidden() {
    jQuery('.pms-form-design-wrapper div#pms-paygates-wrapper, .pms-form-design-wrapper .pms-price-breakdown__holder, .pms-form-design-wrapper #pms-credit-card-information:not(#pms-update-payment-method-form #pms-credit-card-information), .pms-form-design-wrapper.pms-form > input[type=submit] ').css('opacity','0');
}


/**
 * Show Payment Sidebar Elements
 */
function paymentSidebarElementsVisible() {
    jQuery('.pms-form-design-wrapper div#pms-paygates-wrapper, .pms-form-design-wrapper .pms-price-breakdown__holder, .pms-form-design-wrapper #pms-credit-card-information:not(#pms-update-payment-method-form #pms-credit-card-information), .pms-form-design-wrapper.pms-form > input[type=submit]').css('opacity','1');
}


/**
 * Mark the selected Subscription Plan by adding the "selected" class to its label
 */
function markSelectedSubscriptionPlan() {
    let radioButtons = jQuery('input[name="subscription_plans"]');

    if ( radioButtons.length === 1 ) {
        jQuery('.pms-field-subscriptions .pms-subscription-plan label').addClass("selected");
    }
    else if ( radioButtons.length > 1 ) {
        radioButtons.each(function() {
            if (jQuery(this).is(":checked")) {
                jQuery(this).closest("label").addClass("selected");
            }
        });

        radioButtons.on("click", function() {
            jQuery('label').removeClass('selected');
            jQuery(this).closest("label").addClass("selected");
        });
    }
}


/**
 * Handles Billing State Field Label spacing for Form Design - Style-2
 *
 */
function stateFieldLabelSpacing(stateField) {
    let label = stateField.find('label'),
        elementType = jQuery('#' + label.attr('for')).prop('nodeName');

    if (elementType === 'INPUT')
        label.css('left', '30px');
    else stateField.find('label').css('left', '0');
}


/**
 * Animate (scroll on hover) the Subscription Plan Name if too long
 */
function animateSubscriptionHeader() {

    // Scroll animate text to show the Subscription Plan title if too long
    jQuery('.pms-field-subscriptions .pms-subscription-plan label').hover(

        function() {
            let text = jQuery(this).find('.pms-subscription-plan-name'),
                scrollDistance = text[0].scrollWidth - text.width();

            text.stop().animate({
                'text-indent': '-' + scrollDistance + 'px'
            }, 5000);
        },

        function() {
            jQuery(this).find('.pms-subscription-plan-name').stop().animate({
                'text-indent': '0'
            }, 1000);
        }

    );

}

/**
 * Adjust the font size of the Subscription Plan Price section if too long
 */

function adjustPriceLabelFontSize(element) {
    let text = jQuery(element),
        fontSize = parseInt(text.css('font-size')),
        lineHeight = parseInt(text.css('line-height')),
        numberOfLines = text.height() / lineHeight;

    if (numberOfLines >= 2 && fontSize > 10) {
        fontSize--;
        text.css('font-size', fontSize + 'px');
        adjustPriceLabelFontSize(element);
    }
}

/**
 * Handles Floating Labels
 */
function pmsHandleFloatingLabels (formFields) {

    formFields.each(function () {

        let field = jQuery(this),
            input = field.find('input'),
            textarea = field.find('textarea'),
            label = field.find('label'),
            inviteMembers = jQuery('form#pms-invite-members');

        if (textarea.length > 0 && (field[0] === inviteMembers[0] || input.length === 0)) {
            input = textarea;
        }

        if ( field.find('.pms_field-errors-wrapper').length > 0 ) {
            field.addClass('pms-field-error');
        }
        else {
            field.removeClass('pms-field-error');
        }

        if ( input.val() )
            label.addClass('active');

        input.focusin(function () {
            label.addClass('active focused');
            label.addClass('focused');

        })

        input.focusout(function () {
            label.removeClass('focused');
            checkInput();
        })

        /**
         * Mark Labels as needed
         *
         */
        function checkInput() {
            if (input.val()) {
                label.addClass('active');
            }
            else {
                label.removeClass('active');
            }
        }

    });

}


/**
 * Handles Select Field Label (focus in/out on Field Labels)
 */
function pmsFocusInOutSelectFields(formFields) {

    formFields.each(function () {
        let field = jQuery(this),
            select = field.find('select'),
            label = field.find('label');

        if ( select.val() ) {
            label.addClass('active');
        }

        if ( !select.val() || select.val().length === 0) {
            label.removeClass('active');
        }

        field.focusin(function () {
            label.addClass('active focused');
        });

        field.focusout(function () {
            label.removeClass('focused');
            checkSelect();
        })

        field.click(function (e) {
            if ( jQuery(e.target).parents('.wppb_bdp_visibility_settings').length === 0 )
                label.addClass('focused');
        })

        select.change(function() {
            checkSelect();
        })


        /**
         * Mark Fields and Labels as needed
         *
         */
        function checkSelect() {
            if ( (select.val() && select.val().length > 0) || ( field.is('.pms-field-type-select_state') && field.find('input').val() ) ){
                label.addClass('active');
            }
            else {
                label.removeClass('active');
            }
        }
    })

}


/**
 * Handles Logout Button positioning for a crowded PMS Account navigation bar
 */
function handleLogoutButtonPositioning() {
    let containerWidth = jQuery('.pms-form-design-wrapper nav.pms-account-navigation').outerWidth(true),
        navTabs = jQuery('.pms-form-design-wrapper nav.pms-account-navigation ul li'),
        tabsNumber = navTabs.length,
        totalTabsWidth = 0;

    navTabs.each(function() {
        totalTabsWidth += jQuery(this).outerWidth(true);
    });

    if ( totalTabsWidth > 0 )
        totalTabsWidth += (tabsNumber - 1) * 40; // 40px is the gap between the Navigation Tabs

    if (totalTabsWidth > containerWidth)
        jQuery('.pms-form-design-wrapper nav.pms-account-navigation .pms-account-navigation-link--logout').css('position', 'unset');
}