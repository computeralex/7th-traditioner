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
        gateToken: null,
        paypalClientId: null,
        selectedCurrency: null,
        paypalSDKLoaded: false,
        paypalSDKMode: null,
        paypalSDKCurrency: null,
        paypalSDKFunding: null,
        contributionType: null,
        pendingFormCurrency: null,
        recaptchaScriptId: 'seventh-trad-recaptcha-sdk',
        recaptchaLoadPromise: null,
        cachedPlanId: null,
        paypalButtonInstance: null,

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
            this.initContributionTypeSelector();
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

            // Contributor type gates when payment loads (not which funding methods appear)
            $('#seventh-trad-contributor-type').on('change', function() {
                const type = $(this).val();
                const $groupFields = $('#group-fields');
                const afterLayout = function() {
                    self.handleContributorTypePaymentUpdate();
                };

                if (type === 'group') {
                    if (!$groupFields.is(':visible')) {
                        $groupFields.slideDown(400, afterLayout);
                    } else {
                        afterLayout();
                    }
                    $('#seventh-trad-meeting-day').prop('required', true);
                    $('#seventh-trad-meeting').prop('required', true);
                } else if (type === 'individual') {
                    if ($groupFields.is(':visible')) {
                        $groupFields.slideUp(400, afterLayout);
                    } else {
                        afterLayout();
                    }
                    $('#seventh-trad-meeting-day').prop('required', false);
                    $('#seventh-trad-meeting').prop('required', false);
                } else {
                    if ($groupFields.is(':visible')) {
                        $groupFields.slideUp(400, afterLayout);
                    } else {
                        afterLayout();
                    }
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
         * Load Google reCAPTCHA v3 (dynamically, like PayPal SDK).
         * WP-enqueued scripts are often stripped by page caches on Elementor pages.
         */
        loadReCaptchaSDK: function() {
            const self = this;
            const siteKey = seventhTradData.recaptcha_site_key;

            if (!siteKey) {
                return Promise.resolve();
            }

            if (typeof grecaptcha !== 'undefined') {
                return new Promise(function(resolve) {
                    grecaptcha.ready(resolve);
                });
            }

            if (self.recaptchaLoadPromise) {
                return self.recaptchaLoadPromise;
            }

            self.recaptchaLoadPromise = new Promise(function(resolve, reject) {
                const finish = function() {
                    if (typeof grecaptcha === 'undefined') {
                        reject(new Error('reCAPTCHA failed to initialize'));
                        return;
                    }
                    grecaptcha.ready(resolve);
                };

                const existing = document.getElementById(self.recaptchaScriptId);
                if (existing) {
                    existing.addEventListener('load', finish);
                    existing.addEventListener('error', function() {
                        reject(new Error('Failed to load reCAPTCHA'));
                    });
                    return;
                }

                const script = document.createElement('script');
                script.id = self.recaptchaScriptId;
                script.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(siteKey);
                script.async = true;
                script.onload = finish;
                script.onerror = function() {
                    reject(new Error('Failed to load reCAPTCHA'));
                };
                document.head.appendChild(script);
            }).catch(function(err) {
                self.recaptchaLoadPromise = null;
                throw err;
            });

            return self.recaptchaLoadPromise;
        },

        /**
         * Initialize reCAPTCHA v3
         */
        initReCaptcha: function() {
            if (!seventhTradData.recaptcha_site_key) {
                return;
            }
        },

        /**
         * Get reCAPTCHA token
         */
        getReCaptchaToken: function() {
            const self = this;
            const siteKey = seventhTradData.recaptcha_site_key;

            if (!siteKey) {
                return Promise.resolve(null);
            }

            return self.loadReCaptchaSDK().then(function() {
                return grecaptcha.execute(siteKey, { action: 'seventh_trad_contribution' });
            });
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
         * Whether monthly recurring is selected
         */
        isMonthlyContribution: function() {
            return seventhTradData.recurringEnabled && this.contributionType === 'monthly';
        },

        /**
         * Append gate token to payment-related AJAX payloads when reCAPTCHA is enabled.
         */
        appendGateData: function(data) {
            if (this.gateToken) {
                data.gate_token = this.gateToken;
            }
            return data;
        },

        /**
         * Resolve PayPal client ID (issued after gate, or public when reCAPTCHA is off).
         */
        getPayPalClientId: function() {
            return this.paypalClientId || seventhTradData.paypal_client_id || '';
        },

        /**
         * Cache form data before PayPal popup
         */
        cacheFormData: function() {
            const firstName = $('#seventh-trad-first-name').val();
            const lastName = $('#seventh-trad-last-name').val();
            const contributorType = $('#seventh-trad-contributor-type').val();

            this.cachedFormData = {
                member_name: firstName.trim() + ' ' + lastName.trim(),
                member_email: $('#seventh-trad-email').val(),
                phone: $('#seventh-trad-phone').val(),
                contributor_type: contributorType,
                amount: $('#seventh-trad-amount').val(),
                custom_notes: $('#seventh-trad-notes').val()
            };

            if (contributorType === 'group') {
                const isManualEntry = $('#other-meeting-field').is(':visible');

                this.cachedFormData.meeting_day = $('#seventh-trad-meeting-day').val();
                this.cachedFormData.group_id = $('#seventh-trad-group-id').val();

                if (isManualEntry) {
                    this.cachedFormData.meeting_name = $('#seventh-trad-other-meeting').val();
                    this.cachedFormData.meeting_id = '';
                } else {
                    this.cachedFormData.meeting_id = $('#seventh-trad-meeting').val();
                    this.cachedFormData.meeting_name = $('#seventh-trad-meeting option:selected').text();
                }
            }
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
            const formData = self.appendGateData({
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
            });

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
         * Save monthly subscription to database
         */
        saveSubscription: function(data) {
            const self = this;

            if (!self.cachedFormData || !self.cachedPlanId) {
                self.hideLoading();
                self.showError('Subscription data was lost. Please try again.');
                return;
            }

            const formData = self.appendGateData({
                action: 'seventh_trad_save_subscription',
                nonce: seventhTradData.nonce,
                subscription_id: data.subscriptionID,
                plan_id: self.cachedPlanId,
                paypal_status: 'ACTIVE',
                member_name: self.cachedFormData.member_name,
                member_email: self.cachedFormData.member_email,
                phone: self.cachedFormData.phone,
                contributor_type: self.cachedFormData.contributor_type,
                amount: self.cachedFormData.amount,
                currency: self.selectedCurrency,
                custom_notes: self.cachedFormData.custom_notes
            });

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
                        self.showSuccess(response.data.message || seventhTradData.strings.monthly_success);
                    } else {
                        self.showError(response.data.message || seventhTradData.strings.error);
                    }
                },
                error: function() {
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
         * Initialize one-time vs monthly choice (locked after selection, like currency).
         */
        initContributionTypeSelector: function() {
            const self = this;

            $('.seventh-trad-contribution-type-btn').on('click', function() {
                const type = $(this).data('type');
                if (type === 'one-time' || type === 'monthly') {
                    self.selectContributionType(type);
                }
            });

            $('#seventh-trad-contribution-start-over').on('click', function() {
                window.location.reload();
            });
        },

        /**
         * Commit to one-time or monthly before the rest of the form proceeds.
         */
        selectContributionType: function(type) {
            const self = this;

            self.contributionType = type;
            $('#seventh-trad-contribution-type-selector').hide();

            if (type === 'monthly') {
                $('#seventh-trad-monthly-hidden').val('1');
                $('#seventh-trad-contribution-type-display-text').text(
                    seventhTradData.strings.contribution_type_monthly
                );
            } else {
                $('#seventh-trad-monthly-hidden').val('');
                $('#seventh-trad-contribution-type-display-text').text(
                    seventhTradData.strings.contribution_type_onetime
                );
            }

            self.finishFormSetup();
        },

        /**
         * Show the form after currency and contribution type are decided.
         */
        finishFormSetup: function() {
            const self = this;
            const pending = self.pendingFormCurrency;

            if (!pending) {
                return;
            }

            self.form.show();
            $('#seventh-trad-contribution-type-locked').show();
            self.clearPayPalSection();
            self.updateMinMaxForCurrency(pending.currency);
            $('#seventh-trad-amount').data('decimals', pending.decimals);
            $('#seventh-trad-currency-symbol').text(pending.symbol);
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

            // Handle "Try Again" button for reCAPTCHA verification failure
            $('#seventh-trad-recaptcha-retry').on('click', function() {
                // Reload the page to start fresh
                window.location.reload();
            });

            // Single currency: skip picker and load the form directly
            if (seventhTradData.singleCurrencyMode && seventhTradData.autoCurrency) {
                self.selectCurrency(seventhTradData.autoCurrency);
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

            let symbol, decimals, currencyName;

            if (seventhTradData.singleCurrencyMode) {
                symbol = seventhTradData.currencySymbol;
                decimals = seventhTradData.currencyDecimals;
                currencyName = seventhTradData.currencyName || currency;
            } else {
                const $option = $('#seventh-trad-currency-choice option[value="' + currency + '"]');
                symbol = $option.data('symbol');
                decimals = $option.data('decimals');
                currencyName = $option.text() || currency;
                $('#seventh-trad-currency-display-text').text(currencyName);
            }

            // Verify reCAPTCHA before showing form
            self.verifyRecaptchaGate(currency, symbol, decimals, currencyName);
        },

        /**
         * Verify reCAPTCHA gate before showing form
         */
        verifyRecaptchaGate: function(currency, symbol, decimals, currencyName) {
            const self = this;

            // If reCAPTCHA is not configured, skip verification
            if (!seventhTradData.recaptcha_site_key) {
                self.showFormAfterVerification(currency, symbol, decimals, currencyName);
                return;
            }

            // Hide currency selector
            $('#seventh-trad-currency-selector').hide();

            // Show loading spinner
            $('#seventh-trad-recaptcha-loading').show();

            // Get reCAPTCHA token
            self.getReCaptchaToken().then(function(token) {
                // Send token to server for verification
                $.ajax({
                    url: seventhTradData.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'seventh_trad_verify_recaptcha_gate',
                        nonce: seventhTradData.nonce,
                        recaptcha_token: token
                    },
                    success: function(response) {
                        // Hide loading
                        $('#seventh-trad-recaptcha-loading').hide();

                        if (response.success) {
                            self.gateToken = response.data.gate_token || null;
                            self.paypalClientId = response.data.paypal_client_id || null;
                            // Verification passed - show form
                            self.showFormAfterVerification(currency, symbol, decimals, currencyName);
                        } else {
                            // Verification failed - show error
                            $('#seventh-trad-recaptcha-error').show();
                            $('#seventh-trad-recaptcha-error-message').text(
                                response.data.message || 'Verification failed. Please try again.'
                            );
                        }
                    },
                    error: function() {
                        // Hide loading
                        $('#seventh-trad-recaptcha-loading').hide();

                        // Show error
                        $('#seventh-trad-recaptcha-error').show();
                        $('#seventh-trad-recaptcha-error-message').text(
                            'Network error. Please try again.'
                        );
                    }
                });
            }).catch(function(err) {
                console.error('reCAPTCHA error:', err);

                // Hide loading
                $('#seventh-trad-recaptcha-loading').hide();

                // Show error
                $('#seventh-trad-recaptcha-error').show();
                $('#seventh-trad-recaptcha-error-message').text(
                    'reCAPTCHA error. Please try again.'
                );
            });
        },

        /**
         * Show form after reCAPTCHA verification passes
         */
        showFormAfterVerification: function(currency, symbol, decimals, currencyName) {
            const self = this;

            self.pendingFormCurrency = {
                currency: currency,
                symbol: symbol,
                decimals: decimals,
                currencyName: currencyName
            };

            if (seventhTradData.recurringEnabled) {
                $('#seventh-trad-contribution-type-selector').show();
                return;
            }

            self.contributionType = 'one-time';
            self.finishFormSetup();
        },

        /**
         * Close and discard the current PayPal button instance.
         */
        destroyPayPalButton: function() {
            if (this.paypalButtonInstance) {
                try {
                    this.paypalButtonInstance.close();
                } catch (e) {
                    // Ignore if already closed
                }
                this.paypalButtonInstance = null;
            }
            $('#seventh-trad-paypal-button-container').empty();
        },

        /**
         * Whether the contributor has chosen individual or group.
         */
        isContributorTypeSelected: function() {
            const type = $('#seventh-trad-contributor-type').val();
            return type === 'individual' || type === 'group';
        },

        /**
         * PayPal funding sources to disable for the current form state.
         * One-time (individual or group): PayPal + debit/credit card.
         * Monthly recurring: PayPal only (PayPal account required).
         */
        getDisabledFundingSources: function() {
            if (this.isMonthlyContribution()) {
                return 'paylater,card';
            }

            return 'paylater';
        },

        /**
         * Whether the loaded SDK still matches the current form state.
         */
        canReusePayPalSDK: function() {
            return this.paypalSDKLoaded
                && this.isPayPalSDKReady()
                && this.paypalSDKMode === this.getPayPalSDKType()
                && this.paypalSDKCurrency === this.selectedCurrency
                && this.paypalSDKFunding === this.getDisabledFundingSources();
        },

        /**
         * Re-render buttons after layout shift without reloading the SDK.
         */
        rerenderPayPalButtonsAfterLayout: function() {
            const self = this;

            if (!self.selectedCurrency || !self.isContributorTypeSelected()) {
                self.clearPayPalSection();
                return;
            }

            if (!self.canReusePayPalSDK()) {
                self.schedulePayPalLoad();
                return;
            }

            self.hidePayPalPlaceholder();
            self.cachedPlanId = null;
            self.destroyPayPalButton();

            window.setTimeout(function() {
                if (self.canReusePayPalSDK() && self.isContributorTypeSelected()) {
                    self.initSubmitButton();
                } else {
                    self.schedulePayPalLoad();
                }
            }, 50);
        },

        /**
         * Load or refresh payment UI after contributor type changes.
         */
        handleContributorTypePaymentUpdate: function() {
            const self = this;

            if (!self.selectedCurrency || !self.isContributorTypeSelected()) {
                self.clearPayPalSection();
                return;
            }

            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    if (self.canReusePayPalSDK()) {
                        self.rerenderPayPalButtonsAfterLayout();
                    } else {
                        self.schedulePayPalLoad();
                    }
                });
            });
        },

        /**
         * Show placeholder until contributor type is selected.
         */
        showPayPalPlaceholder: function() {
            const message = seventhTradData.strings.select_contributor_for_payment
                || 'Select how you are contributing above to continue to payment.';
            $('#seventh-trad-paypal-placeholder').text(message).show();
        },

        /**
         * Hide payment placeholder once buttons are loading or rendered.
         */
        hidePayPalPlaceholder: function() {
            $('#seventh-trad-paypal-placeholder').hide();
        },

        /**
         * Reset payment section when contributor type is not yet chosen.
         */
        clearPayPalSection: function() {
            this.unloadPayPalSDK();
            this.showPayPalPlaceholder();
        },

        /**
         * Load or reload PayPal after layout settles.
         */
        schedulePayPalLoad: function() {
            const self = this;

            if (!self.selectedCurrency || !self.isContributorTypeSelected()) {
                self.clearPayPalSection();
                return;
            }

            self.hidePayPalPlaceholder();

            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    self.reloadPayPalButtons();
                });
            });
        },

        /**
         * Resolve which PayPal SDK mode is needed for the current form state.
         */
        getPayPalSDKType: function() {
            return this.isMonthlyContribution() ? 'subscription' : 'capture';
        },

        /**
         * Remove leftover PayPal/zoid DOM nodes before reloading the SDK.
         */
        cleanupPayPalArtifacts: function() {
            this.destroyPayPalButton();

            document.querySelectorAll('[id*="zoid-paypal"], iframe[src*="paypal.com"]').forEach(function(el) {
                el.remove();
            });

            const $container = $('#seventh-trad-paypal-button-container');
            if ($container.length) {
                $container.replaceWith('<div id="seventh-trad-paypal-button-container"></div>');
            }
        },

        /**
         * Whether the PayPal SDK is ready to render buttons.
         */
        isPayPalSDKReady: function() {
            return !!(window.paypal && typeof window.paypal.Buttons === 'function');
        },

        /**
         * Remove any PayPal SDK script and globals (required before swapping intent).
         */
        unloadPayPalSDK: function() {
            const scriptIds = [
                'seventh-trad-paypal-sdk',
                'seventh-trad-paypal-capture-sdk',
                'seventh-trad-paypal-subscription-sdk'
            ];

            this.cleanupPayPalArtifacts();

            scriptIds.forEach(function(id) {
                const el = document.getElementById(id);
                if (el) {
                    el.remove();
                }
            });

            window.paypal = null;
            window.paypal_capture = null;
            window.paypal_subscription = null;
            delete window.paypal;
            delete window.paypal_capture;
            delete window.paypal_subscription;

            this.paypalSDKLoaded = false;
            this.paypalSDKMode = null;
            this.paypalSDKCurrency = null;
            this.paypalSDKFunding = null;
        },

        /**
         * Reload PayPal buttons when monthly toggle or layout changes.
         */
        reloadPayPalButtons: function() {
            const self = this;

            if (!self.selectedCurrency || !self.isContributorTypeSelected()) {
                self.clearPayPalSection();
                return;
            }

            const type = self.getPayPalSDKType();
            const fundingKey = self.getDisabledFundingSources();

            self.cachedPlanId = null;
            self.destroyPayPalButton();
            self.hidePayPalPlaceholder();

            if (self.canReusePayPalSDK()) {
                self.initSubmitButton();
                return;
            }

            self.loadPayPalSDKForMode(self.selectedCurrency, type).then(function() {
                self.initSubmitButton();
            }).catch(function(err) {
                console.error('PayPal reload error:', err);
                self.showPayPalLoadError(err);
            });
        },

        /**
         * Load one PayPal SDK for the given currency and mode.
         *
         * @param {string} currency Currency code
         * @param {string} type 'capture' or 'subscription'
         * @return {Promise<object>}
         */
        loadPayPalSDKForMode: function(currency, type) {
            const self = this;
            const isSubscription = type === 'subscription';
            const scriptId = 'seventh-trad-paypal-sdk';
            const fundingKey = self.getDisabledFundingSources();

            if (self.paypalSDKLoaded
                && self.paypalSDKMode === type
                && self.paypalSDKCurrency === currency
                && self.paypalSDKFunding === fundingKey
                && self.isPayPalSDKReady()) {
                return Promise.resolve(window.paypal);
            }

            const needsSdkSwap = self.paypalSDKLoaded
                && (self.paypalSDKMode !== type
                    || self.paypalSDKCurrency !== currency
                    || self.paypalSDKFunding !== fundingKey);

            const loadScript = function(resolve, reject) {
                const waitForPayPal = function(attempts) {
                    if (self.isPayPalSDKReady()) {
                        self.paypalSDKLoaded = true;
                        self.paypalSDKMode = type;
                        self.paypalSDKCurrency = currency;
                        self.paypalSDKFunding = fundingKey;
                        resolve(window.paypal);
                    } else if (attempts > 0) {
                        setTimeout(function() { waitForPayPal(attempts - 1); }, 50);
                    } else {
                        reject(new Error('PayPal SDK failed to initialize'));
                    }
                };

                const clientId = self.getPayPalClientId();
                if (!clientId) {
                    reject(new Error('PayPal is not configured'));
                    return;
                }

                let sdkUrl = 'https://www.paypal.com/sdk/js?client-id=' + encodeURIComponent(clientId)
                             + '&currency=' + encodeURIComponent(currency)
                             + '&disable-funding=' + encodeURIComponent(fundingKey);

                if (isSubscription) {
                    sdkUrl += '&intent=subscription&vault=true';
                }

                const existing = document.getElementById(scriptId);
                if (existing) {
                    existing.remove();
                }

                const script = document.createElement('script');
                script.id = scriptId;
                script.src = sdkUrl;
                script.async = true;
                script.setAttribute('data-sdk-mode', type);
                script.setAttribute('data-sdk-currency', currency);
                script.setAttribute('data-sdk-funding', fundingKey);
                script.onload = function() { waitForPayPal(60); };
                script.onerror = function() {
                    reject(new Error('Failed to load PayPal'));
                };
                document.head.appendChild(script);
            };

            if (needsSdkSwap) {
                self.unloadPayPalSDK();
                return new Promise(function(resolve, reject) {
                    window.setTimeout(function() {
                        loadScript(resolve, reject);
                    }, 250);
                });
            }

            return new Promise(loadScript);
        },

        /**
         * Show a user-facing PayPal load failure message.
         */
        showPayPalLoadError: function(err) {
            const message = err && err.message === 'PayPal is not configured'
                ? 'PayPal is not configured. Please contact the administrator.'
                : 'Failed to load PayPal. Please refresh the page.';
            $('#seventh-trad-paypal-button-container').html('<div class="seventh-trad-error">' + message + '</div>');
        },

        /**
         * Load PayPal SDK for the selected currency and current form mode.
         */
        loadPayPalSDK: function(currency) {
            const self = this;
            const type = self.getPayPalSDKType();

            self.loadPayPalSDKForMode(currency, type).then(function() {
                self.initSubmitButton();
            }).catch(function(err) {
                console.error('PayPal load error:', err);
                self.showPayPalLoadError(err);
            });
        },

        /**
         * Create a monthly subscription plan server-side, then subscribe
         */
        createMonthlySubscription: function(actions) {
            const self = this;
            const amount = $('#seventh-trad-amount').val();
            const currency = self.selectedCurrency;

            self.cacheFormData();

            return $.ajax({
                url: seventhTradData.ajax_url,
                type: 'POST',
                data: self.appendGateData({
                    action: 'seventh_trad_create_subscription_plan',
                    nonce: seventhTradData.nonce,
                    amount: amount,
                    currency: currency
                })
            }).then(function(response) {
                if (!response.success || !response.data.plan_id) {
                    const message = response.data && response.data.message
                        ? response.data.message
                        : 'Failed to create subscription plan.';
                    self.showError(message);
                    throw new Error(message);
                }

                self.cachedPlanId = response.data.plan_id;

                return actions.subscription.create({
                    plan_id: response.data.plan_id
                });
            });
        },

        /**
         * Initialize submit button and PayPal
         */
        initSubmitButton: function() {
            const self = this;
            const paypalApi = window.paypal;

            if (!paypalApi || !paypalApi.Buttons) {
                return;
            }

            if (self.paypalButtonInstance) {
                self.destroyPayPalButton();
            } else {
                $('#seventh-trad-paypal-button-container').empty();
            }

            const buttonConfig = {
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'rect',
                    label: 'paypal'
                },

                onClick: function(data, actions) {
                    const form = self.form[0];
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        const firstInvalid = form.querySelector(':invalid');
                        if (firstInvalid) {
                            const fieldLabel = $('label[for="' + firstInvalid.id + '"]').text().trim().replace('*', '').trim();
                            self.showError('Please fill out required field: ' + fieldLabel);
                        }
                        return actions.reject();
                    }

                    if (!self.validateForm()) {
                        return actions.reject();
                    }

                    return actions.resolve();
                },

                onCancel: function() {
                    self.showError('Payment was cancelled. Please try again if you wish to contribute.');
                },

                onError: function(err) {
                    let errorMessage = 'An error occurred with PayPal.';
                    if (err && err.message) {
                        errorMessage += ' Error: ' + err.message;
                    }
                    self.showError(errorMessage);
                }
            };

            if (self.isMonthlyContribution()) {
                buttonConfig.createSubscription = function(data, actions) {
                    return self.createMonthlySubscription(actions);
                };
                buttonConfig.onApprove = function(data) {
                    self.showLoading();
                    self.saveSubscription(data);
                };
            } else {
                buttonConfig.createOrder = function(data, actions) {
                    const amount = $('#seventh-trad-amount').val();
                    const currency = self.selectedCurrency;
                    const itemDetails = self.getItemDetails();
                    const email = $('#seventh-trad-email').val();
                    const firstName = $('#seventh-trad-first-name').val();
                    const lastName = $('#seventh-trad-last-name').val();

                    self.cacheFormData();

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

                    return actions.order.create(orderData);
                };
                buttonConfig.onApprove = function(data, actions) {
                    self.showLoading();
                    return actions.order.capture().then(function(details) {
                        self.saveContribution(details, null);
                    });
                };
            }

            const renderResult = paypalApi.Buttons(buttonConfig).render('#seventh-trad-paypal-button-container');
            if (renderResult && typeof renderResult.then === 'function') {
                renderResult.then(function(instance) {
                    self.paypalButtonInstance = instance;
                }).catch(function(err) {
                    console.error('PayPal button render error:', err);
                });
            }
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
