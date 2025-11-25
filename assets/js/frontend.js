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
        selectedCurrency: null,
        paypalSDKLoaded: false,

        /**
         * Initialize
         */
        init: function() {
            this.form = $('#seventh-trad-form');

            if (this.form.length === 0) {
                return;
            }


            this.initReCaptcha();
            this.bindEvents();
            this.initCurrencySelector();
            // Don't call initSubmitButton() yet - will be called after currency selection
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

            // Currency is now selected at the top before form loads
            // No need for currency change handler

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
                return;
            }

            // grecaptcha is loaded via the Google reCAPTCHA script
            if (typeof grecaptcha !== 'undefined') {
                grecaptcha.ready(function() {
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

            // Validate min/max amounts
            const $amountField = $('#seventh-trad-amount');
            const minAmount = parseFloat($amountField.data('min-amount'));
            const maxAmount = parseFloat($amountField.data('max-amount'));
            const currency = this.selectedCurrency;


            if (minAmount && !isNaN(minAmount) && amount < minAmount) {
                const symbol = $('#seventh-trad-currency-symbol').text();
                const decimals = parseInt($amountField.data('decimals')) || 2;
                this.showError('Minimum contribution: ' + symbol + minAmount.toFixed(decimals));
                return false;
            }

            if (maxAmount && !isNaN(maxAmount) && amount > maxAmount) {
                const symbol = $('#seventh-trad-currency-symbol').text();
                const decimals = parseInt($amountField.data('decimals')) || 2;
                this.showError('Maximum contribution: ' + symbol + maxAmount.toFixed(decimals));
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

            // Safety check - if cachedFormData doesn't exist, something went wrong
            if (!self.cachedFormData) {
                self.showError('Form data was lost. Please try again.');
                return;
            }

            // Use cached form data that was captured during createOrder
            const formData = {
                action: 'seventh_trad_save_contribution',
                nonce: seventhTradData.nonce,
                recaptcha_token: recaptchaToken || '',
                transaction_id: orderData.id,
                paypal_order_id: orderData.id,
                member_name: self.cachedFormData.member_name,
                member_email: self.cachedFormData.member_email,
                phone: self.cachedFormData.phone,
                contributor_type: self.cachedFormData.contributor_type,
                amount: self.cachedFormData.amount,
                currency: self.selectedCurrency,
                paypal_status: orderData.status,
                custom_notes: self.cachedFormData.custom_notes
            };

            // Add group-specific fields if this was a group contribution
            if (self.cachedFormData.contributor_type === 'group') {
                formData.meeting_day = self.cachedFormData.meeting_day;
                formData.meeting_id = self.cachedFormData.meeting_id || '';
                formData.meeting_name = self.cachedFormData.meeting_name || '';
                formData.group_id = self.cachedFormData.group_id || '';
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
                    self.hideLoading();
                    self.showError(seventhTradData.strings.error);
                }
            });
        },

        /**
         * Show success message - replaces entire form
         */
        showSuccess: function(message) {
            // Hide the PayPal button container
            $('#seventh-trad-paypal-button-container').parent('.seventh-trad-submit-container').fadeOut(400);

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


            $success.hide();
            $error.html(message).css('display', 'block').show();


            // Scroll to message
            setTimeout(() => {
                const errorOffset = $error.offset();
                if (errorOffset && errorOffset.top > 0) {
                    $('html, body').animate({
                        scrollTop: errorOffset.top - 100
                    }, 500);
                }
            }, 100);
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
         * Initialize currency selector
         */
        initCurrencySelector: function() {
            const self = this;

            // Handle "Start Over" button (must be before early return)
            $('#seventh-trad-start-over').on('click', function() {
                // Reload the page to start fresh
                window.location.reload();
            });

            // Check if only one currency is enabled - auto-select it
            const isSingleCurrency = $('.seventh-trad-container').data('single-currency') === true;
            const autoCurrency = $('.seventh-trad-container').data('auto-currency');

            if (isSingleCurrency && autoCurrency) {
                self.selectCurrency(autoCurrency);
                return;
            }

            // Handle currency selection
            $('#seventh-trad-currency-choice').on('change', function() {
                const currency = $(this).val();
                if (currency) {
                    self.selectCurrency(currency);
                }
            });
        },

        /**
         * Select a currency and load the form
         */
        selectCurrency: function(currency) {
            const self = this;

            // Store selected currency
            self.selectedCurrency = currency;

            // Get currency details from the dropdown or data attributes (for single currency mode)
            const $container = $('.seventh-trad-container');
            const isSingleCurrency = $container.data('single-currency') === true;

            let symbol, decimals, currencyName;

            if (isSingleCurrency) {
                // Get from container data attributes
                symbol = $container.data('currency-symbol');
                decimals = $container.data('currency-decimals');
                currencyName = $container.data('currency-name');
            } else {
                // Get from dropdown option
                const $option = $('#seventh-trad-currency-choice option[value="' + currency + '"]');
                symbol = $option.data('symbol');
                decimals = $option.data('decimals');
                currencyName = $option.text() || currency;
            }

            // Update currency display in form
            $('#seventh-trad-currency-display-text').text(currencyName);

            // Hide currency selector
            $('#seventh-trad-currency-selector').hide();

            // Show form
            self.form.show();

            // Load PayPal SDK with selected currency
            self.loadPayPalSDK(currency);

            // Update min/max for selected currency
            self.updateMinMaxForCurrency(currency);

            // Store currency info for amount field
            $('#seventh-trad-amount').data('decimals', decimals);
            $('#seventh-trad-currency-symbol').text(symbol);
        },

        /**
         * Load PayPal SDK with specified currency
         */
        loadPayPalSDK: function(currency) {
            const self = this;

            if (self.paypalSDKLoaded) {
                return;
            }


            const clientId = seventhTradData.paypal_client_id;
            if (!clientId) {
                $('#seventh-trad-paypal-button-container').html('<div class="seventh-trad-error">PayPal is not configured. Please contact the administrator.</div>');
                return;
            }

            const sdkUrl = 'https://www.paypal.com/sdk/js?client-id=' + encodeURIComponent(clientId)
                         + '&currency=' + encodeURIComponent(currency)
                         + '&disable-funding=paylater';

            const script = document.createElement('script');
            script.src = sdkUrl;
            script.async = true;
            script.onload = function() {
                self.paypalSDKLoaded = true;
                self.initSubmitButton();
            };
            script.onerror = function() {
                $('#seventh-trad-paypal-button-container').html('<div class="seventh-trad-error">Failed to load PayPal. Please refresh the page.</div>');
            };

            document.head.appendChild(script);
        },

        /**
         * Initialize submit button and PayPal
         */
        initSubmitButton: function() {
            const self = this;

            // Check if PayPal SDK is available
            if (typeof paypal === 'undefined') {
                return;
            }


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

                    // Trigger browser's native form validation
                    const form = self.form[0];
                    if (!form.checkValidity()) {
                        form.reportValidity(); // Shows browser validation messages

                        // Also show custom error since reportValidity doesn't always work in iframe context
                        const firstInvalid = form.querySelector(':invalid');
                        if (firstInvalid) {
                            const fieldLabel = $('label[for="' + firstInvalid.id + '"]').text().trim().replace('*', '').trim();
                            self.showError('Please fill out required field: ' + fieldLabel);
                        }

                        return actions.reject();
                    }

                    // Additional custom validation
                    if (!self.validateForm()) {
                        return actions.reject();
                    }


                    // Get reCAPTCHA token ONCE before payment (cache for later use after payment)
                    return self.getReCaptchaToken().then(function(token) {
                        self.cachedRecaptchaToken = token || '';
                        return actions.resolve();
                    }).catch(function(err) {
                        console.error('reCAPTCHA error:', err);
                        // Allow payment to proceed even if reCAPTCHA fails (token will be empty)
                        self.cachedRecaptchaToken = '';
                        return actions.resolve();
                    });
                },
                createOrder: function(data, actions) {

                    // Get form data
                    const amount = $('#seventh-trad-amount').val();
                    const currency = self.selectedCurrency;
                    const itemDetails = self.getItemDetails();
                    const email = $('#seventh-trad-email').val();
                    const firstName = $('#seventh-trad-first-name').val();
                    const lastName = $('#seventh-trad-last-name').val();
                    const contributorType = $('#seventh-trad-contributor-type').val();

                    // Cache form data NOW (before PayPal popup opens) so it's available when saveContribution runs later
                    self.cachedFormData = {
                        member_name: firstName.trim() + ' ' + lastName.trim(),
                        member_email: email,
                        phone: $('#seventh-trad-phone').val(),
                        contributor_type: contributorType,
                        amount: amount,
                        custom_notes: $('#seventh-trad-notes').val()
                    };

                    // Cache group-specific fields if this is a group contribution
                    if (contributorType === 'group') {
                        const meetingDay = $('#seventh-trad-meeting-day').val();
                        const isManualEntry = $('#other-meeting-field').is(':visible');

                        self.cachedFormData.meeting_day = meetingDay;
                        self.cachedFormData.group_id = $('#seventh-trad-group-id').val();

                        if (isManualEntry) {
                            self.cachedFormData.meeting_name = $('#seventh-trad-other-meeting').val();
                            self.cachedFormData.meeting_id = '';
                        } else {
                            const $selectedMeeting = $('#seventh-trad-meeting option:selected');
                            self.cachedFormData.meeting_id = $('#seventh-trad-meeting').val();
                            self.cachedFormData.meeting_name = $selectedMeeting.text();
                        }
                    }


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
                                sku: itemDetails.sku,
                                name: itemDetails.name,
                                description: itemDetails.description,
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

                    // Show loading
                    self.showLoading();

                    // Capture the order
                    return actions.order.capture().then(function(details) {

                        // Save contribution to database using cached reCAPTCHA token from onClick
                        self.saveContribution(details, self.cachedRecaptchaToken);
                    });
                },
                onCancel: function(data) {
                    self.showError('Payment was cancelled. Please try again if you wish to contribute.');
                },
                onError: function(err) {

                    // Show detailed error for debugging
                    let errorMessage = 'An error occurred with PayPal.';
                    if (err && err.message) {
                        errorMessage += ' Error: ' + err.message;
                    }

                    // Check if it's a currency issue
                    const currency = self.selectedCurrency;
                    if (currency !== 'USD') {
                        errorMessage += ' NOTE: Sandbox test cards may only work with USD. Try USD or test in live mode with real transactions.';
                    }

                    self.showError(errorMessage);
                }
            }).render('#seventh-trad-paypal-button-container');
        },

        /**
         * Get item details for PayPal (ID, name, and description/memo)
         */
        getItemDetails: function() {
            const contributorType = $('#seventh-trad-contributor-type').val();
            let itemId = '';
            let itemName = '';

            if (contributorType === 'group') {
                itemId = '7TH-GROUP';
                const meetingDay = $('#seventh-trad-meeting-day option:selected').text();
                const isManualEntry = $('#other-meeting-field').is(':visible');

                // Abbreviate day name (Monday -> Mon, Tuesday -> Tue, etc.)
                const dayAbbrev = meetingDay.substring(0, 3);

                if (isManualEntry) {
                    const meetingName = $('#seventh-trad-other-meeting').val();
                    const meetingTime = $('#seventh-trad-meeting-time').val() || '';
                    itemName = dayAbbrev + ' ' + meetingTime + ' ' + meetingName;
                } else {
                    // Meeting dropdown already has format "TIME - MEETING NAME"
                    const meetingLabel = $('#seventh-trad-meeting option:selected').text();
                    itemName = dayAbbrev + ' ' + meetingLabel;
                }
            } else {
                itemId = '7TH-MEMBER';
                itemName = 'Individual Contribution';
            }

            // Build description/memo field
            const description = this.buildMemo();

            return {
                sku: itemId,
                name: itemName.substring(0, 127), // PayPal limit
                description: description.substring(0, 127) // PayPal limit
            };
        },

        /**
         * Build memo field with notes, phone, and group number
         */
        buildMemo: function() {
            const parts = [];

            // Add notes if provided
            const notes = $('#seventh-trad-notes').val();
            if (notes && notes.trim()) {
                parts.push(notes.trim());
            }

            // Add phone number
            const phone = $('#seventh-trad-phone').val();
            if (phone && phone.trim()) {
                parts.push('Phone: ' + phone.trim());
            }

            // Add group number if this is a group contribution
            const contributorType = $('#seventh-trad-contributor-type').val();
            if (contributorType === 'group') {
                const groupId = $('#seventh-trad-group-id').val();
                if (groupId && groupId.trim()) {
                    parts.push('Group ID: ' + groupId.trim());
                }
            }

            return parts.join(' | ');
        },

        /**
         * Update min/max amounts for selected currency
         */
        updateMinMaxForCurrency: function(currency) {
            const self = this;

            // If no min/max configured in settings, skip
            if (!seventhTradData.minAmount && !seventhTradData.maxAmount) {
                return;
            }

            // If USD, use the settings directly
            if (currency === 'USD') {
                self.applyMinMax(seventhTradData.minAmount, seventhTradData.maxAmount, currency);
                return;
            }

            // For other currencies, fetch exchange rate and convert
            $.ajax({
                url: seventhTradData.ajax_url,
                type: 'GET',
                data: {
                    action: 'seventh_trad_get_exchange_rate',
                    currency: currency
                },
                success: function(response) {
                    if (response.success && response.data.rate) {
                        const rate = parseFloat(response.data.rate);
                        const roundingMethod = seventhTradData.roundingMethod || 'smart';

                        // Convert and round
                        let convertedMin = null;
                        let convertedMax = null;

                        if (seventhTradData.minAmount) {
                            convertedMin = parseFloat(seventhTradData.minAmount) * rate;
                            convertedMin = self.roundAmount(convertedMin, currency, roundingMethod, 'up');
                        }

                        if (seventhTradData.maxAmount) {
                            convertedMax = parseFloat(seventhTradData.maxAmount) * rate;
                            convertedMax = self.roundAmount(convertedMax, currency, roundingMethod, 'down');
                        }

                        self.applyMinMax(convertedMin, convertedMax, currency);
                    }
                },
                error: function() {
                    // Gracefully degrade - don't enforce min/max if we can't convert
                }
            });
        },

        /**
         * Apply min/max to form
         */
        applyMinMax: function(min, max, currency) {
            const $amountField = $('#seventh-trad-amount');

            // Store for validation
            $amountField.data('min-amount', min);
            $amountField.data('max-amount', max);
            $amountField.data('currency', currency);

            // Update HTML5 validation
            if (min) {
                $amountField.attr('min', min);
            } else {
                $amountField.removeAttr('min');
            }

            if (max) {
                $amountField.attr('max', max);
            } else {
                $amountField.removeAttr('max');
            }
        },

        /**
         * Round amount based on method
         */
        roundAmount: function(amount, currency, method, direction) {
            if (method === 'simple') {
                // Get currency decimals from amount field
                const decimals = parseInt($('#seventh-trad-amount').data('decimals')) || 2;
                return parseFloat(amount.toFixed(decimals));
            }

            // Smart rounding
            switch (currency) {
                case 'JPY':
                case 'KRW':
                    // Round to nearest 50
                    return direction === 'up'
                        ? Math.ceil(amount / 50) * 50
                        : Math.floor(amount / 50) * 50;

                case 'INR':
                case 'THB':
                    // Round to nearest 5
                    return direction === 'up'
                        ? Math.ceil(amount / 5) * 5
                        : Math.floor(amount / 5) * 5;

                case 'VND':
                    // Round to nearest 1000
                    return direction === 'up'
                        ? Math.ceil(amount / 1000) * 1000
                        : Math.floor(amount / 1000) * 1000;

                case 'CLP':
                case 'IDR':
                    // Round to nearest 100
                    return direction === 'up'
                        ? Math.ceil(amount / 100) * 100
                        : Math.floor(amount / 100) * 100;

                default:
                    // Most currencies - round to whole number
                    return direction === 'up' ? Math.ceil(amount) : Math.floor(amount);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SeventhTrad.init();
    });

})(jQuery);
