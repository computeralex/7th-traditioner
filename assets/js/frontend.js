/**
 * 7th Traditioner Frontend JavaScript
 *
 * Handles PayPal integration and form submission
 */

(function($) {
    'use strict';

    const SeventhTrad = {
        form: null,
        recaptchaToken: null,

        /**
         * Initialize
         */
        init: function() {
            this.form = $('#seventh-trad-form');

            if (this.form.length === 0) {
                return;
            }

            this.initPayPal();
            this.initReCaptcha();
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Validate amount field
            $('#seventh-trad-amount').on('input', function() {
                const value = parseFloat($(this).val());
                if (value < 1) {
                    $(this).val('');
                }
            });
        },

        /**
         * Initialize reCAPTCHA v3
         */
        initReCaptcha: function() {
            const siteKey = seventhTradData.recaptcha_site_key;

            if (!siteKey) {
                console.log('7th Traditioner: reCAPTCHA not configured');
                return;
            }

            // grecaptcha is loaded via the Google reCAPTCHA script
            if (typeof grecaptcha !== 'undefined') {
                grecaptcha.ready(function() {
                    console.log('7th Traditioner: reCAPTCHA ready');
                });
            }
        },

        /**
         * Get reCAPTCHA token
         */
        getReCaptchaToken: function() {
            const self = this;
            const siteKey = seventhTradData.recaptcha_site_key;

            if (!siteKey || typeof grecaptcha === 'undefined') {
                return Promise.resolve(null);
            }

            return grecaptcha.execute(siteKey, { action: 'seventh_trad_contribution' });
        },

        /**
         * Initialize PayPal
         */
        initPayPal: function() {
            const self = this;

            if (typeof paypal === 'undefined') {
                console.error('7th Traditioner: PayPal SDK not loaded');
                self.showError(seventhTradData.strings.error);
                return;
            }

            paypal.Buttons({
                // Set up the transaction
                createOrder: function(data, actions) {
                    // Validate form
                    if (!self.validateForm()) {
                        return actions.reject();
                    }

                    const amount = $('#seventh-trad-amount').val();
                    const currency = $('#seventh-trad-currency').val();

                    // Create the order
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: amount,
                                currency_code: currency
                            },
                            description: self.getOrderDescription()
                        }]
                    });
                },

                // Finalize the transaction
                onApprove: async function(data, actions) {
                    self.showLoading();

                    // Capture the order
                    return actions.order.capture().then(async function(orderData) {
                        console.log('7th Traditioner: Order captured', orderData);

                        // Get reCAPTCHA token
                        const recaptchaToken = await self.getReCaptchaToken();

                        // Save contribution to database
                        await self.saveContribution(orderData, recaptchaToken);
                    });
                },

                // Handle errors
                onError: function(err) {
                    console.error('7th Traditioner: PayPal error', err);
                    self.hideLoading();
                    self.showError(seventhTradData.strings.error);
                },

                // Handle cancellation
                onCancel: function(data) {
                    console.log('7th Traditioner: Payment cancelled', data);
                    self.hideLoading();
                }

            }).render('#seventh-trad-paypal-button');
        },

        /**
         * Validate form
         */
        validateForm: function() {
            const name = $('#seventh-trad-member-name').val().trim();
            const groupId = $('#seventh-trad-group').val();
            const email = $('#seventh-trad-member-email').val();
            const amount = parseFloat($('#seventh-trad-amount').val());

            if (!name) {
                this.showError('Please enter your name');
                return false;
            }

            if (!groupId) {
                this.showError(seventhTradData.strings.select_group);
                return false;
            }

            if (!email || !this.isValidEmail(email)) {
                this.showError('Please enter a valid email address');
                return false;
            }

            if (!amount || amount <= 0) {
                this.showError(seventhTradData.strings.enter_amount);
                return false;
            }

            return true;
        },

        /**
         * Validate email
         */
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Get order description
         */
        getOrderDescription: function() {
            const groupId = $('#seventh-trad-group').val();
            const groupName = $('#seventh-trad-group option:selected').text();
            return `7th Tradition Contribution - ${groupName}`;
        },

        /**
         * Save contribution to database
         */
        saveContribution: async function(orderData, recaptchaToken) {
            const self = this;

            const formData = {
                action: 'seventh_trad_save_contribution',
                nonce: seventhTradData.nonce,
                recaptcha_token: recaptchaToken || '',
                transaction_id: orderData.id,
                paypal_order_id: orderData.id,
                member_name: $('#seventh-trad-member-name').val(),
                member_email: $('#seventh-trad-member-email').val(),
                group_id: $('#seventh-trad-group').val(),
                amount: $('#seventh-trad-amount').val(),
                currency: $('#seventh-trad-currency').val(),
                paypal_status: orderData.status,
                custom_notes: $('#seventh-trad-notes').val()
            };

            $.ajax({
                url: seventhTradData.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    self.hideLoading();

                    if (response.success) {
                        self.showSuccess(response.data.message);
                        self.resetForm();
                    } else {
                        self.showError(response.data.message || seventhTradData.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('7th Traditioner: AJAX error', error);
                    self.hideLoading();
                    self.showError(seventhTradData.strings.error);
                }
            });
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            const $success = $('.seventh-trad-success');
            const $error = $('.seventh-trad-error');

            $error.hide().css('display', 'none');
            $success.html(message).css('display', 'block').hide().slideDown();

            // Scroll to message
            $('html, body').animate({
                scrollTop: $success.offset().top - 100
            }, 500);

            // Auto-hide after 10 seconds
            setTimeout(function() {
                $success.slideUp();
            }, 10000);
        },

        /**
         * Show error message
         */
        showError: function(message) {
            const $error = $('.seventh-trad-error');
            const $success = $('.seventh-trad-success');

            $success.hide().css('display', 'none');
            $error.html(message).css('display', 'block').hide().slideDown();

            // Scroll to message
            $('html, body').animate({
                scrollTop: $error.offset().top - 100
            }, 500);
        },

        /**
         * Show loading indicator
         */
        showLoading: function() {
            $('#seventh-trad-loading').show();
            $('#seventh-trad-paypal-button').hide();
        },

        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            $('#seventh-trad-loading').hide();
            $('#seventh-trad-paypal-button').show();
        },

        /**
         * Reset form
         */
        resetForm: function() {
            this.form[0].reset();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SeventhTrad.init();
    });

})(jQuery);
