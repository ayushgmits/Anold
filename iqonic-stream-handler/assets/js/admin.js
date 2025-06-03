    jQuery(function ($) {

        $('#enable_coins').change(function() {
            if ($(this).is(':checked')) {
                $('.coins-settings').show();
            } else {
                $('.coins-settings').hide();
            }
        }).change();

        class StreamitUnlockManager {
            constructor() {
                console.log("✅ StreamitUnlockManager initialized");
                this.initialize();
            }

            initialize() {
                this.unlock_time_vali();
                // this.ish_coins_vals();
                this.setupDateTimePicker();
                this.addEventHandlers();
                this.startTimer(); // Start countdown timer
                this.allstates();
                this.validateFieldsOnLoad();
            }

            setupDateTimePicker() {
                if (typeof $.fn.flatpickr === "undefined") {
                    console.error("🚨 Flatpickr not found.");
                    return;
                }

                if ($("#unlock_time").length) {
                    console.log("✅ #unlock_time field found, initializing timepicker.");
                    $("#unlock_time").timepicker({
                        timeFormat: 'hh:mm p', // 12-hour format with AM/PM
                        interval: 30, // Interval in minutes
                        minTime: '12:00am', // Start time
                        maxTime: '11:30pm', // End time
                        dynamic: false,
                        dropdown: true,
                        scrollbar: true
                    });
                } else {
                    console.warn("⚠️ #unlock_time field NOT found.");
                }
                
            }

            addEventHandlers() {
                $(document.body).on("change", "#custom_subscription_level", this.toggleCheckbox.bind(this));
                $(document.body).on("change", "#custom_coins_level", this.toggleCheckbox.bind(this));

                // Apply correct state based on metadata when the page loads
                this.handleInitialCheckboxState();
            }

            toggleCheckbox(e) {
                const currentCheckbox = $(e.currentTarget);
                const isChecked = currentCheckbox.prop("checked");

                console.log(`🔄 Checkbox ${currentCheckbox.attr("id")} changed: ${isChecked}`);

                $("#custom_subscription_level, #custom_coins_level")
                    .not(currentCheckbox)
                    .prop("disabled", isChecked);
            }

            handleInitialCheckboxState() {
                const subChecked = $("#custom_subscription_level").prop("checked");
                const coinsChecked = $("#custom_coins_level").prop("checked");

                if (subChecked) {
                    $("#custom_coins_level").prop("disabled", true);
                }
                if (coinsChecked) {
                    $("#custom_subscription_level").prop("disabled", true);
                }
            }

            startTimer() {
                this.updateUnlockTimer(); 
                setInterval(() => this.updateUnlockTimer(), 60000); // Refresh every 1 minute
            }

            updateUnlockTimer() {
                $("#unlock_timer").load(window.location.href + " #unlock_timer"); // Reload only the unlock timer
            }
        
            

            updateTimeDisplay(remainingMinutes) {
                const unlockTimer = $("#unlock_timer");

                if (unlockTimer.length) {
                    const hours = Math.floor(remainingMinutes / 60);
                    const minutes = remainingMinutes % 60;
                    unlockTimer.val(`${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}`);
                    console.log(`⏰ Timer updated: ${unlockTimer.val()}`);
                }
            }

            saveUpdatedTime(minutes) {
                console.log(`🔄 Saving updated time: ${minutes} minutes left.`);
                $.ajax({
                    url: iqonic_stream_handler.ajax_url,
                    type: "POST",
                    data: {
                        action: "update_unlock_time",
                        post_id: iqonic_stream_handler.post_id,
                        minutes_left: minutes,
                        nonce: iqonic_stream_handler.nonce
                    },
                    success: function (response) {
                        console.log("✅ Time updated: " + minutes + " minutes remaining");
                    },
                    error: function (error) {
                        console.error("🚨 Error updating time:", error);
                    }
                });
            }
            
            allstates() {
                let countryDropdown = document.getElementById("country");
                let stateDropdown = document.getElementById("state");
            
                // Ensure state dropdown is populated when the page reloads
                let selectedCountry = countryDropdown.value;
                let selectedState = stateDropdown.dataset.selectedState;
            
                if (selectedCountry) {
                    // Make an initial fetch request if country is selected
                    fetchStates(selectedCountry, selectedState);
                }
            
                // When the country changes, update the state dropdown
                countryDropdown.addEventListener("change", function() {
                    let selectedCountry = countryDropdown.value;
                    stateDropdown.innerHTML = `<option value="">${ishPluginData.loading_text}</option>`; // Add loading option
            
                    if (!selectedCountry) {
                        // If no country is selected, reset the state dropdown
                        stateDropdown.innerHTML = `<option value="">${ishPluginData.select_state_text}</option>`;
                        return;
                    }
            
                    // Fetch new states based on the selected country
                    fetchStates(selectedCountry, selectedState);
                });
            
                // Fetch states for a given country
                function fetchStates(country, selectedState) {
                    fetch(ishPluginData.ajax_url, {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({
                            action: "get_states",
                            security: ishPluginData.nonce,
                            country: country
                        }).toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        let states = data.data && Array.isArray(data.data.states) ? data.data.states : [];
                        stateDropdown.innerHTML = `<option value="">${ishPluginData.select_state_text}</option>`; // Reset state dropdown
            
                        // Populate the state dropdown
                        if (states.length > 0) {
                            states.forEach(state => {
                                let option = document.createElement("option");
                                option.value = state;
                                option.textContent = state;
                                stateDropdown.appendChild(option);
                            });
            
                            // Re-select the previously selected state if it's still in the new list
                            if (selectedState && states.includes(selectedState)) {
                                stateDropdown.value = selectedState;
                            }
                        } else {
                            stateDropdown.innerHTML = `<option value="">${ishPluginData.select_state_text}</option>`; // No states available
                        }
                    })
                    .catch(error => console.error("🚨 Error fetching states:", error));
                }
            }

            
             ish_coins_vals() {
                let alreadyLocked = false;
                let isReloading = false;
                let alertShown = false;
                
                const validateCoinsField = () => {
                    const coins = $('#ish_coins').val();
                    const isValid = coins && parseInt(coins) > 0;
                
                    if (!isValid) {
                        if (!alertShown) {
                            alertShown = true;
                            alert('The "Coins" field is required and must be a positive number.');
                        }
                        if (!alreadyLocked) {
                            alreadyLocked = true;
                            wp.data.dispatch('core/editor').lockPostSaving('coins-validation-error');
                        }
                        if (!isReloading) {
                            isReloading = true;
                            setTimeout(() => location.reload(), 100); // Ensure page reloads after alert
                        }
                        return false; // Validation failed
                    } else {
                        if (alreadyLocked) {
                            alreadyLocked = false;
                            wp.data.dispatch('core/editor').unlockPostSaving('coins-validation-error');
                        }
                        alertShown = false; // Reset alert flag when validation passes
                        return true; // Validation passed
                    }
                };
                
                // Listen to post save and stop if validation fails
                wp.data.subscribe(() => {
                    if (isReloading) return; // Avoid triggering during reload
                
                    const isSaving = wp.data.select('core/editor').isSavingPost();
                    const isAutosaving = wp.data.select('core/editor').isAutosavingPost();
                
                    if (isSaving && !isAutosaving) {
                        const isValid = validateCoinsField();
                
                        if (!isValid) {
                            console.log('Post save blocked due to coins validation.');
                            wp.data.dispatch('core/editor').lockPostSaving('coins-validation-error');
                        }
                    }
                });
                
                // Validate on blur for immediate feedback
                $('#ish_coins').on('blur', validateCoinsField);
                
        }


    unlock_time_vali() {
        console.log('Validation script loaded for Classic Editor');
    
        const waitForButton = setInterval(() => {
            const publishButton = document.querySelector('#publish'); // Classic Editor Button
    
            if (publishButton) {
                clearInterval(waitForButton); // Stop checking once button is found
                console.log('Publish button found!');
    
                const isFreeField = document.querySelector('#ish_is_free');
                const adsCountField = document.querySelector('#ish_ads_count');
                const coinsField = document.querySelector('#ish_coins');
                const unlockTimeField = document.querySelector('#unlock_time');
                // ✅ Check if ALL fields exist, if not → Enable publish button
                    if (!isFreeField || !adsCountField || !coinsField || !unlockTimeField) {
                        publishButton.disabled = false;
                        return;
                    }
                function validateFields() {
                    console.log('Validating fields...');
                    let isValid = false;
    
                    if (isFreeField && isFreeField.checked) {
                        if (!unlockTimeField || unlockTimeField.value.trim() === '') {
                            isValid = false;
                            if (!document.querySelector('#unlock-time-inline-warning')) {
                                const warning = document.createElement('span');
                                warning.id = 'unlock-time-inline-warning';
                                warning.style.color = '#d63638';
                                warning.style.fontWeight = 'bold';
                                warning.style.marginLeft = '10px';
                                warning.innerText = '⚠️ Unlock time is required when "Is Free" is checked.';
                                unlockTimeField.parentNode.appendChild(warning);
                            }
                        } else {
                            isValid = true;
                            const warning = document.querySelector('#unlock-time-inline-warning');
                            if (warning) {
                                warning.remove();
                            }
                        }
                    } else {
                        const warning = document.querySelector('#unlock-time-inline-warning');
                        if (warning) {
                            warning.remove();
                        }
    
                        if (
                            (adsCountField && adsCountField.value.trim() !== '') ||
                            (coinsField && coinsField.value.trim() !== '' && parseInt(coinsField.value, 10) >= 0) ||
                            (unlockTimeField && unlockTimeField.value.trim() !== '')
                        ) {
                            isValid = true;
                        }
                    }
    
                    publishButton.disabled = !isValid;
                }
    
                // Run validation on page load and on input change
                validateFields();
                document.addEventListener('input', validateFields);
            } else {
                // console.log('Still waiting for the publish button...');
            }
        }, 1000); // Check every 1 second
    }
    
    
 validateFieldsOnLoad() {
        console.log('Validation script loaded for Classic Editor');

        const waitForButton = setInterval(() => {
            const publishButton = document.querySelector('#publish');

            if (publishButton) {
                clearInterval(waitForButton);
                const isFreeField = document.querySelector('#ish_is_free');
                const adsCountField = document.querySelector('#ish_ads_count');
                const coinsField = document.querySelector('#ish_coins');
                const unlockTimeField = document.querySelector('#unlock_time');

                // ✅ If none of the fields exist, keep the publish button enabled
                if (!isFreeField && !adsCountField && !coinsField && !unlockTimeField) {
                    publishButton.disabled = false;
                    return;
                }

                const validateFields = () => {
                    let isValid = false; // Default: Invalid unless proven valid

                    // Remove previous warning
                    const warning = document.querySelector('#unlock-time-inline-warning');
                    if (warning) {
                        warning.remove();
                    }

                    if (isFreeField && isFreeField.checked) {
                        // "Is Free" is checked → Unlock Time is required
                        if (!unlockTimeField || unlockTimeField.value.trim() === '') {
                            isValid = false;
                            if (unlockTimeField) {
                                const warning = document.createElement('span');
                                warning.id = 'unlock-time-inline-warning';
                                warning.style.color = '#d63638';
                                warning.style.fontWeight = 'bold';
                                warning.style.marginLeft = '10px';
                                warning.innerText = '⚠️ Unlock time is required when "Is Free" is checked.';
                                unlockTimeField.parentNode.appendChild(warning);
                            }
                        } else {
                            isValid = true;
                        }
                    } else {
                        // If "Is Free" is not checked, at least one field should be filled
                        if (
                            (adsCountField && adsCountField.value.trim() !== '') ||
                            (coinsField && coinsField.value.trim() !== '' && parseInt(coinsField.value, 10) >= 0) ||
                            (unlockTimeField && unlockTimeField.value.trim() !== '')
                        ) {
                            isValid = true;
                        }
                    }

                    // ✅ Enable publish button only if validation passes
                    publishButton.disabled = !isValid;
                };

                // Run validation on page load and on input change
                validateFields();
                document.addEventListener('input', validateFields);
            }
        }, 1000);
    }

    
    
}
document.addEventListener('DOMContentLoaded', () => new EpisodeValidator());

        new StreamitUnlockManager();
    });

    
    // document.addEventListener('DOMContentLoaded', function () {
    //     console.log('Validation script loaded for Classic Editor');
    
    //     const waitForButton = setInterval(() => {
    //         const publishButton = document.querySelector('#publish'); // Classic Editor Button
    
    //         if (publishButton) {
    //             clearInterval(waitForButton); // Stop checking once button is found
    //             console.log('Publish button found!');
    
    //             const isFreeField = document.querySelector('#ish_is_free');
    //             const adsCountField = document.querySelector('#ish_ads_count');
    //             const coinsField = document.querySelector('#ish_coins');
    //             const unlockTimeField = document.querySelector('#unlock_time');
    
    //             function validateFields() {
    //             console.log('Validating fields...');
    //             let isValid = true; // Default: allow publishing
            
    //             // Check if fields exist before applying validation
    //             if (isFreeField || adsCountField || coinsField || unlockTimeField) {
    //                 isValid = false; // Default to invalid if any field exists
            
    //                 // Remove previous warnings
    //                 const existingWarning = document.querySelector('#unlock-time-inline-warning');
    //                 if (existingWarning) existingWarning.remove();
            
    //                 if (isFreeField && isFreeField.checked) {
    //                     // "Is Free" is checked → Unlock Time is required
    //                     if (!unlockTimeField || unlockTimeField.value.trim() === '') {
    //                         isValid = false;
    //                         if (!document.querySelector('#unlock-time-inline-warning')) {
    //                             const warning = document.createElement('span');
    //                             warning.id = 'unlock-time-inline-warning';
    //                             warning.style.color = '#d63638';
    //                             warning.style.fontWeight = 'bold';
    //                             warning.style.marginLeft = '10px';
    //                             warning.innerText = '⚠️ Unlock time is required when "Is Free" is checked.';
    //                             unlockTimeField.parentNode.appendChild(warning);
    //                         }
    //                     } else {
    //                         isValid = true;
    //                     }
    //                 } else {
    //                     // If "Is Free" is not checked, at least one field should be filled
    //                     if (
    //                         (adsCountField && adsCountField.value.trim() !== '') ||
    //                         (coinsField && coinsField.value.trim() !== '' && parseInt(coinsField.value, 10) >= 0) ||
    //                         (unlockTimeField && unlockTimeField.value.trim() !== '')
    //                     ) {
    //                         isValid = true;
    //                     }
    //                 }
    //             }
            
    //             // ✅ Only disable if validation fails and fields exist
    //             publishButton.disabled = (isValid === false);
    //         }
    
    //             // Run validation on page load and on input change
    //             validateFields();
    //             document.addEventListener('input', validateFields);
    //         } else {
    //             // console.log('Still waiting for the publish button...');
    //         }
    //     }, 1000); // Check every 1 second
    // });
    