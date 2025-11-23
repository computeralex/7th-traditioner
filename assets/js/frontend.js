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
                const decimals = parseInt($selected.data('decimals'));
                const position = $selected.data('position');

                // Update symbol display
                $('#seventh-trad-currency-symbol').text(symbol);

                // Update placeholder
                const placeholder = decimals === 0 ? '0' : '0.00';
                $('#seventh-trad-amount').attr('placeholder', placeholder);

                // Store decimals for validation
                $('#seventh-trad-amount').data('decimals', decimals);

                // Clean existing amount value if switching to no-decimal currency
                const $amountField = $('#seventh-trad-amount');
                const currentValue = $amountField.val();
                if (currentValue && decimals === 0) {
                    // Remove decimals from existing value
                    $amountField.val(currentValue.replace(/[^0-9]/g, ''));
                }
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
                                      (meeting.group || '') + '" data-time="' + timeFormatted + '">' +
                                      meetingLabel + '</option>';
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
            const firstName = $('#seventh-trad-first-name').val().trim();
            const lastName = $('#seventh-trad-last-name').val().trim();
            const email = $('#seventh-trad-email').val();
            const contributorType = $('#seventh-trad-contributor-type').val();
            const amountStr = $('#seventh-trad-amount').val().trim();
            const amount = parseFloat(amountStr);

            if (!firstName || !lastName) {
                this.showError('Please enter your first and last name');
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

            const firstName = $('#seventh-trad-first-name').val();
            const lastName = $('#seventh-trad-last-name').val();
            const fullName = firstName + ' ' + lastName;

            const formData = {
                action: 'seventh_trad_save_contribution',
                nonce: seventhTradData.nonce,
                recaptcha_token: recaptchaToken || '',
                transaction_id: orderData.id,
                paypal_order_id: orderData.id,
                member_name: fullName,
                member_email: $('#seventh-trad-email').val(),
                phone: $('#seventh-trad-phone').val(),
                contributor_type: contributorType,
                amount: $('#seventh-trad-amount').val(),
                currency: $('#seventh-trad-currency').val(),
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
         * Show success message - replaces entire form
         */
        showSuccess: function(message) {
            // Fade out the form
            $('.seventh-trad-form').fadeOut(400, function() {
                // Replace with success message
                const successHTML = `
                    <div class="seventh-trad-success-screen" style="text-align: center; padding: 60px 20px;">
                        <div style="margin-bottom: 30px;">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <h2 style="font-size: 32px; font-weight: 700; color: #28a745; margin: 0 0 20px;">Thank You!</h2>
                        <p style="font-size: 20px; line-height: 1.6; color: #000000; margin: 0 0 30px;">${message}</p>
                        <p style="font-size: 16px; color: #666666; margin: 0 0 40px;">A receipt has been sent to your email address.</p>
                        <button type="button" class="seventh-trad-submit-btn" onclick="location.reload();" style="max-width: 300px; margin: 0 auto;">
                            Make Another Contribution
                        </button>
                    </div>
                `;

                $(this).html(successHTML).fadeIn(400);

                // Scroll to top of success message
                $('html, body').animate({
                    scrollTop: $('.seventh-trad-form-wrapper').offset().top - 100
                }, 500);
            });
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
         * Initialize submit button and PayPal
         */
        initSubmitButton: function() {
            const self = this;

            // Check if PayPal SDK is available
            if (typeof paypal === 'undefined') {
                console.warn('7th Traditioner: PayPal SDK not loaded');
                return;
            }

            console.log('7th Traditioner: Rendering PayPal buttons');

            // Render PayPal buttons
            paypal.Buttons({
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

                    // Trigger browser's native form validation
                    const form = self.form[0];
                    if (!form.checkValidity()) {
                        console.log('7th Traditioner: Browser validation failed');
                        form.reportValidity(); // Shows browser validation messages
                        return actions.reject();
                    }

                    // Additional custom validation
                    if (!self.validateForm()) {
                        console.log('7th Traditioner: Form validation failed');
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
                    const itemName = self.getItemName();
                    const email = $('#seventh-trad-email').val();
                    const firstName = $('#seventh-trad-first-name').val();
                    const lastName = $('#seventh-trad-last-name').val();

                    console.log('7th Traditioner: Creating order - Amount:', amount, 'Currency:', currency);

                    // Build order object with item breakdown
                    const orderData = {
                        purchase_units: [{
                            amount: {
                                value: amount,
                                currency_code: currency,
                                breakdown: {
                                    item_total: {
                                        value: amount,
                                        currency_code: currency
                                    }
                                }
                            },
                            items: [{
                                name: itemName,
                                unit_amount: {
                                    value: amount,
                                    currency_code: currency
                                },
                                quantity: '1'
                            }]
                        }],
                        application_context: {
                            shipping_preference: 'NO_SHIPPING'
                        }
                    };

                    // Only add payer info if we have complete name and email (avoid empty field errors)
                    if (email && email.trim() && firstName && firstName.trim() && lastName && lastName.trim()) {
                        orderData.payer = {
                            email_address: email.trim(),
                            name: {
                                given_name: firstName.trim(),
                                surname: lastName.trim()
                            }
                        };
                    }

                    // Create order client-side (NO SERVER SECRETS NEEDED!)
                    return actions.order.create(orderData);
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

                    // Show detailed error for debugging
                    let errorMessage = 'An error occurred with PayPal.';
                    if (err && err.message) {
                        errorMessage += ' Error: ' + err.message;
                        console.error('7th Traditioner: Error details:', err.message);
                    }

                    // Check if it's a currency issue
                    const currency = $('#seventh-trad-currency').val();
                    if (currency !== 'USD') {
                        errorMessage += ' NOTE: Sandbox test cards may only work with USD. Try USD or test in live mode with real transactions.';
                    }

                    self.showError(errorMessage);
                }
            }).render('#seventh-trad-paypal-button-container');
        },

        /**
         * Get item name for PayPal
         */
        getItemName: function() {
            const contributorType = $('#seventh-trad-contributor-type').val();
            let itemName = '';

            if (contributorType === 'group') {
                const meetingDay = $('#seventh-trad-meeting-day option:selected').text();
                const isManualEntry = $('#other-meeting-field').is(':visible');

                // Abbreviate day name (Monday -> Mon, Tuesday -> Tue, etc.)
                const dayAbbrev = meetingDay.substring(0, 3);

                if (isManualEntry) {
                    const meetingName = $('#seventh-trad-other-meeting').val();
                    const meetingTime = $('#seventh-trad-meeting-time').val() || '';
                    itemName = '7th Trad Group ' + dayAbbrev + ' ' + meetingTime + ' ' + meetingName;
                } else {
                    // Meeting dropdown already has format "TIME - MEETING NAME"
                    const meetingLabel = $('#seventh-trad-meeting option:selected').text();
                    itemName = '7th Trad Group ' + dayAbbrev + ' ' + meetingLabel;
                }
            } else {
                itemName = '7th Trad Individual';
            }

            return itemName.substring(0, 127); // PayPal limit
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SeventhTrad.init();
    });

})(jQuery);
