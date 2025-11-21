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

            console.log('7th Traditioner: Initializing plugin');

            this.initReCaptcha();
            this.bindEvents();
            this.initSubmitButton();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Prevent form submission
            this.form.on('submit', function(e) {
                e.preventDefault();
                return false;
            });

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

            // Meeting day change - load meetings for that day
            $('#seventh-trad-meeting-day').on('change', function() {
                const day = $(this).val();
                if (day !== '') {
                    self.loadMeetings(day);
                } else {
                    $('#seventh-trad-meeting').prop('disabled', true).html('<option value="">-- Select Day First --</option>');
                }
            });

            // Meeting selection change - detect "Other" option
            $('#seventh-trad-meeting').on('change', function() {
                if ($(this).val() === 'other') {
                    $('#seventh-trad-meeting').parent().slideUp();
                    $('#other-meeting-field').slideDown();
                    $('#seventh-trad-meeting').prop('required', false);
                    $('#seventh-trad-other-meeting').prop('required', true);
                }
            });

            // Toggle to manual meeting entry
            $('#seventh-trad-add-other-meeting').on('click', function(e) {
                e.preventDefault();
                $('#seventh-trad-meeting').parent().slideUp();
                $('#other-meeting-field').slideDown();
                $('#seventh-trad-meeting').prop('required', false);
                $('#seventh-trad-other-meeting').prop('required', true);
            });

            // Toggle back to meeting list selection
            $('#seventh-trad-select-from-list').on('click', function(e) {
                e.preventDefault();
                $('#other-meeting-field').slideUp();
                $('#seventh-trad-meeting').parent().slideDown();
                $('#seventh-trad-other-meeting').prop('required', false);
                $('#seventh-trad-meeting').prop('required', true);
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

                // For currencies with no decimals, only allow digits
                if (decimals === 0) {
                    value = value.replace(/[^0-9]/g, '');
                    $(this).val(value);
                    return;
                }

                // Remove any non-numeric characters except decimal point
                value = value.replace(/[^0-9.]/g, '');

                // Only allow one decimal point
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }

                // Limit decimal places based on currency
                if (parts.length === 2) {
                    // Limit to specified decimal places
                    parts[1] = parts[1].substring(0, decimals);
                    value = parts[0] + '.' + parts[1];
                }

                $(this).val(value);
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
                        options += '<option value="other">Other</option>';
                        $meetingSelect.html(options).prop('disabled', false);
                    } else {
                        $meetingSelect.html('<option value="">No meetings found for this day</option><option value="other">Other</option>');
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

                // Check if manual entry mode is active
                const isManualEntry = $('#other-meeting-field').is(':visible');

                if (isManualEntry) {
                    const otherMeeting = $('#seventh-trad-other-meeting').val().trim();
                    if (!otherMeeting) {
                        this.showError('Please enter your meeting name');
                        return false;
                    }
                } else {
                    // Validate dropdown selection
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
                formData.meeting_day = meetingDay;

                // Check if manual entry mode is active
                const isManualEntry = $('#other-meeting-field').is(':visible');

                if (isManualEntry) {
                    formData.meeting_name = $('#seventh-trad-other-meeting').val();
                    formData.meeting_id = '';
                } else {
                    const $selectedMeeting = $('#seventh-trad-meeting option:selected');
                    formData.meeting_id = $('#seventh-trad-meeting').val();
                    formData.meeting_name = $selectedMeeting.text();
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
        },


        /**
         * Render PayPal buttons based on recurring selection
         */
        renderPayPalButtons: function() {
            const self = this;
            const isRecurring = $('#seventh-trad-recurring').val() === 'yes';

            // Clear existing buttons
            $('#seventh-trad-paypal-button-container').empty();

            console.log('7th Traditioner: Rendering PayPal buttons (Recurring: ' + isRecurring + ')');

            // Configure button options based on recurring
            const buttonConfig = {
                // Style configuration
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'rect',
                    label: 'paypal'
                },

                // Validate form before creating order
                onClick: function(data, actions) {
                    console.log('7th Traditioner: PayPal button clicked');

                    // Validate form
                    if (!self.validateForm()) {
                        console.log('7th Traditioner: Form validation failed');
                        return actions.reject();
                    }

                    // Check if this is a recurring contribution
                    const currentRecurring = $('#seventh-trad-recurring').val() === 'yes';
                    if (currentRecurring) {
                        self.showError('Recurring contributions are not yet implemented. Please select "No" for recurring contribution.');
                        return actions.reject();
                    }

                    console.log('7th Traditioner: Form validation passed');

                    // Get reCAPTCHA token to prevent card testing attacks
                    return self.getReCaptchaToken().then(function(token) {
                        if (token) {
                            console.log('7th Traditioner: reCAPTCHA token obtained');
                            self.recaptchaToken = token;
                            return actions.resolve();
                        } else {
                            console.warn('7th Traditioner: reCAPTCHA not configured, proceeding anyway');
                            return actions.resolve();
                        }
                    }).catch(function(err) {
                        console.error('7th Traditioner: reCAPTCHA error:', err);
                        self.showError('Security verification failed. Please try again.');
                        return actions.reject();
                    });
                },
                createOrder: function(data, actions) {
                    console.log('7th Traditioner: createOrder called');

                    // Get form data
                    const amount = $('#seventh-trad-amount').val();
                    const currency = $('#seventh-trad-currency').val();
                    const description = self.getOrderDescription();

                    console.log('7th Traditioner: Creating order - Amount:', amount, 'Currency:', currency);

                    // Create order client-side (NO SERVER SECRETS NEEDED!)
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: amount,
                                currency_code: currency
                            },
                            description: description
                        }],
                        application_context: {
                            shipping_preference: 'NO_SHIPPING'
                        }
                    });
                },
                onApprove: function(data, actions) {
                    console.log('7th Traditioner: Order approved:', data.orderID);

                    // Show loading
                    self.showLoading();

                    // Capture the order
                    return actions.order.capture().then(function(details) {
                        console.log('7th Traditioner: Payment captured:', details);

                        // Save contribution to database
                        self.getReCaptchaToken().then(function(recaptchaToken) {
                            self.saveContribution(details, recaptchaToken);
                        });
                    });
                },
                onCancel: function(data) {
                    console.log('7th Traditioner: Payment cancelled');
                    self.showError('Payment was cancelled. Please try again if you wish to contribute.');
                },
                onError: function(err) {
                    console.error('7th Traditioner: PayPal error:', err);
                    self.showError('An error occurred with PayPal. Please try again.');
                }
            };

            // If recurring, disable card funding
            if (isRecurring) {
                buttonConfig.fundingSource = paypal.FUNDING.PAYPAL;
            }

            // Render the buttons
            paypal.Buttons(buttonConfig).render('#seventh-trad-paypal-button-container');
        },

        /**
         * Initialize submit button and PayPal
         */
        initSubmitButton: function() {
            const self = this;

            // Check if PayPal SDK is available
            if (typeof paypal === 'undefined') {
                console.warn('7th Traditioner: PayPal SDK not loaded');
                return;
            }

            // Render PayPal buttons initially
            self.renderPayPalButtons();

            // Re-render buttons when recurring option changes
            $('#seventh-trad-recurring').on('change', function() {
                console.log('7th Traditioner: Recurring option changed, re-rendering buttons');
                self.renderPayPalButtons();
            });
        },

        /**
         * Get order description
         */
        getOrderDescription: function() {
            const contributorType = $('#seventh-trad-contributor-type').val();
            if (contributorType === 'group') {
                const isManualEntry = $('#other-meeting-field').is(':visible');
                if (isManualEntry) {
                    return '7th Tradition Contribution - ' + $('#seventh-trad-other-meeting').val();
                } else {
                    const meetingName = $('#seventh-trad-meeting option:selected').text();
                    return '7th Tradition Contribution - ' + meetingName;
                }
            }
            return '7th Tradition Contribution';
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SeventhTrad.init();
    });

})(jQuery);
