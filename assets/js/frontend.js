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
            const self = this;

            // Contributor type change
            $('#seventh-trad-contributor-type').on('change', function() {
                const type = $(this).val();
                if (type === 'group') {
                    $('#group-fields').slideDown();
                    $('#seventh-trad-meeting-day').prop('required', true);
                    $('#seventh-trad-meeting').prop('required', true);
                } else {
                    $('#group-fields').slideUp();
                    $('#seventh-trad-meeting-day').prop('required', false);
                    $('#seventh-trad-meeting').prop('required', false);
                }
            });

            // Meeting day change - load meetings for that day or show "Other" field
            $('#seventh-trad-meeting-day').on('change', function() {
                const day = $(this).val();

                if (day === 'other') {
                    // Show "Other" text input and disable meeting dropdown
                    $('#other-day-field').slideDown();
                    $('#seventh-trad-other-day').prop('required', true);
                    $('#seventh-trad-meeting').prop('disabled', true).html('<option value="">-- Select a specific day to choose meeting --</option>');
                    $('#seventh-trad-meeting').prop('required', false);
                } else {
                    // Hide "Other" text input
                    $('#other-day-field').slideUp();
                    $('#seventh-trad-other-day').prop('required', false);
                    $('#seventh-trad-meeting').prop('required', true);

                    if (day !== '') {
                        self.loadMeetings(day);
                    } else {
                        $('#seventh-trad-meeting').prop('disabled', true).html('<option value="">-- Select Day First --</option>');
                    }
                }
            });

            // Currency change - update symbol and decimal places
            $('#seventh-trad-currency').on('change', function() {
                const $selected = $(this).find('option:selected');
                const symbol = $selected.data('symbol');
                const decimals = $selected.data('decimals');
                const position = $selected.data('position');

                // Update symbol display
                $('#seventh-trad-currency-symbol').text(symbol);

                // Update placeholder
                const placeholder = decimals === 0 ? '0' : '0.00';
                $('#seventh-trad-amount').attr('placeholder', placeholder);

                // Store decimals for validation
                $('#seventh-trad-amount').data('decimals', decimals);
            }).trigger('change'); // Trigger on page load

            // Validate and format amount field with proper decimal places
            $('#seventh-trad-amount').on('input', function() {
                let value = $(this).val();
                const decimals = parseInt($(this).data('decimals')) || 2;

                // Remove any non-numeric characters except decimal point
                value = value.replace(/[^0-9.]/g, '');

                // Only allow one decimal point
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }

                // Limit decimal places based on currency
                if (parts.length === 2) {
                    if (decimals === 0) {
                        // No decimals allowed for this currency
                        value = parts[0];
                    } else {
                        // Limit to specified decimal places
                        parts[1] = parts[1].substring(0, decimals);
                        value = parts[0] + '.' + parts[1];
                    }
                }

                $(this).val(value);
            });

            // Recurring contribution change - disable card option if recurring
            $('#seventh-trad-recurring').on('change', function() {
                const isRecurring = $(this).val() === 'yes';
                const $cardMethod = $('#payment-method-card');
                const $cardInput = $cardMethod.find('input[type="radio"]');
                const $paypalInput = $('#payment-method-paypal input[type="radio"]');

                if (isRecurring) {
                    // Disable card option
                    $cardInput.prop('disabled', true);
                    $cardMethod.addClass('seventh-trad-payment-method-disabled');

                    // Select PayPal automatically
                    $paypalInput.prop('checked', true);

                    // Show notice
                    $('.seventh-trad-recurring-notice').slideDown();
                } else {
                    // Enable card option
                    $cardInput.prop('disabled', false);
                    $cardMethod.removeClass('seventh-trad-payment-method-disabled');

                    // Hide notice
                    $('.seventh-trad-recurring-notice').slideUp();
                }
            });
        },

        /**
         * Load meetings for a specific day via AJAX
         */
        loadMeetings: function(day) {
            const $meetingSelect = $('#seventh-trad-meeting');

            $meetingSelect.prop('disabled', true).html('<option value="">Loading...</option>');

            $.ajax({
                url: seventhTradData.ajax_url,
                type: 'POST',
                data: {
                    action: 'seventh_trad_get_meetings_by_day',
                    nonce: seventhTradData.nonce,
                    day: day
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let options = '<option value="">-- Select Meeting --</option>';
                        response.data.forEach(function(meeting) {
                            const timeFormatted = meeting.time_formatted || '';
                            const meetingLabel = timeFormatted + ' - ' + meeting.name;
                            options += '<option value="' + meeting.id + '" data-group-name="' +
                                      (meeting.group || '') + '">' + meetingLabel + '</option>';
                        });
                        $meetingSelect.html(options).prop('disabled', false);
                    } else {
                        $meetingSelect.html('<option value="">No meetings found for this day</option>');
                    }
                },
                error: function() {
                    $meetingSelect.html('<option value="">Error loading meetings</option>');
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
            const name = $('#seventh-trad-name').val().trim();
            const email = $('#seventh-trad-email').val();
            const contributorType = $('#seventh-trad-contributor-type').val();
            const amountStr = $('#seventh-trad-amount').val().trim();
            const amount = parseFloat(amountStr);

            if (!name) {
                this.showError('Please enter your name');
                return false;
            }

            if (!email || !this.isValidEmail(email)) {
                this.showError('Please enter a valid email address');
                return false;
            }

            if (!contributorType) {
                this.showError('Please select whether you are contributing as an individual or on behalf of a group');
                return false;
            }

            // Validate group fields if contributing on behalf of group
            if (contributorType === 'group') {
                const day = $('#seventh-trad-meeting-day').val();

                if (!day) {
                    this.showError('Please select the meeting day');
                    return false;
                }

                // If "Other" day selected, validate the text input
                if (day === 'other') {
                    const otherDay = $('#seventh-trad-other-day').val().trim();
                    if (!otherDay) {
                        this.showError('Please specify the meeting day');
                        return false;
                    }
                } else {
                    // For specific days, validate meeting selection
                    const meeting = $('#seventh-trad-meeting').val();
                    if (!meeting) {
                        this.showError('Please select your meeting');
                        return false;
                    }
                }
            }

            if (!amountStr || !amount || amount <= 0) {
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

            const contributorType = $('#seventh-trad-contributor-type').val();
            const meetingDay = $('#seventh-trad-meeting-day').val();

            const formData = {
                action: 'seventh_trad_save_contribution',
                nonce: seventhTradData.nonce,
                recaptcha_token: recaptchaToken || '',
                transaction_id: orderData.id,
                paypal_order_id: orderData.id,
                member_name: $('#seventh-trad-name').val(),
                member_email: $('#seventh-trad-email').val(),
                phone: $('#seventh-trad-phone').val(),
                contributor_type: contributorType,
                amount: $('#seventh-trad-amount').val(),
                currency: $('#seventh-trad-currency').val(),
                recurring: $('#seventh-trad-recurring').val(),
                paypal_status: orderData.status,
                custom_notes: $('#seventh-trad-notes').val()
            };

            // Add group-specific fields if contributing on behalf of group
            if (contributorType === 'group') {
                if (meetingDay === 'other') {
                    formData.meeting_day = $('#seventh-trad-other-day').val();
                    formData.meeting_id = '';
                } else {
                    formData.meeting_day = meetingDay;
                    formData.meeting_id = $('#seventh-trad-meeting').val();
                }
                formData.group_id = $('#seventh-trad-group-id').val();
            }

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
