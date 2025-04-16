/**
 * This function initializes the shipping form validation.
 *
 * @param {HTMLFormElement} form - The form element to be validated.
 * @param {Component} wire - The Livewire component instance.
 * @returns {Object} - Returns an object with the form validation methods and properties.
 */
const DotdigitalShippingForm = (form, wire) => {

    /**
     * This function checks if the selected shipping address is valid.
     *
     * @param {HTMLFormElement} form - The form element to be validated.
     * @returns {Promise<boolean>} - Returns a promise that resolves to a boolean indicating the validity of the form.
     */

    return Object.assign({},
        hyva.formValidation(form),
        {
            init() {
                /**
                 * Check validity when requested by the server
                 */
                wire.on('validate.phone_number', () => {
                    this.$nextTick(() => this.checkSelectedShippingAddressValidity(this).finally(() => {
                        this.$el.classList.remove('dd-hidden')
                        this.$el.classList.remove('dd-loading')
                    }))
                });

                /**
                 * Trigger update event when shipping address is changed.
                 * @see Hyva\CheckoutDotdigitalgroupSms\Magewire\ShippingForm::update
                 */
                Magewire.on('shipping_address_activated', (event) => {
                    this.$wire.emit('address_list_updated')
                })

                /**
                 * This step will add a new listener to the navigation to validate and submit the shipping form
                 * when the user navigates to the next step.
                 */
                hyvaCheckout.navigation.addTask(() => this.validate(), {stackPosition: 500})
                hyvaCheckout.navigation.addTask(() => this.shippingFormSubmit(), {stackPosition: 600})

                /**
                 * Run initial shipping phone validation check
                 */
                window.addEventListener('checkout:init:shipping', (event) => wire.emit('update_details'), {once: true})
            },

            /**
             * This function checks if the selected shipping address is valid.
             *
             * @param {HTMLFormElement} form - The form element to be validated.
             * @returns {Promise<boolean>} - Returns a promise that resolves to a boolean indicating the validity of the form.
             */
            async checkSelectedShippingAddressValidity(form) {
                try {
                    await form.validate()
                } catch (e) {
                    wire.updateValidity(false)
                    await wire.refresh()
                    return false
                }
                wire.updateValidity(true)
                await wire.refresh()
            },

            /**
             * This method will submit the shipping form.
             *
             * @returns {Promise<boolean>} - Returns a promise that resolves to a boolean indicating the success of the form submission.
             */
            async shippingFormSubmit() {
                const payload = this.$dotdigitalFormDataCollection(this.$el)
                try {
                    await this.validate()
                } catch (e) {
                    return false
                }

                await wire.shippingFormSubmit(payload)
                await wire.refresh()
            }

        })
}

/**
 * Register the component on Alpine.js directly on the alpine:init event.
 *
 * @returns {any}
 */
export function  DotdigitalShippingFormComponent() {
    /**
     * Register the component on Alpine.js directly on the alpine:init event.
     *
     * @var {Object} $wire - The Livewire component instance.
     * @var {Element} $el - The DOM element.
     */
    return  DotdigitalShippingForm(this.$el, this.$wire)
}
