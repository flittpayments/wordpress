const flitt_settings = window.wc.wcSettings.getSetting( 'flitt_data', {} );
const flitt_label = flitt_settings.title || window.wp.i18n.__( 'Pay with Visa/Mastercard via Flitt', 'flitt-payment-gateway-for-woocommerce');
const FlittContent = () => {
    return window.wp.htmlEntities.decodeEntities(
        flitt_settings.description || window.wp.i18n.__('Pay securely by Credit or Debit Card or Internet Banking through flitt.com service.', 'flitt-payment-gateway-for-woocommerce')
    );
};
const Flitt_Block_Gateway = {
    name: 'flitt',
    label: flitt_label,
    content: Object( window.wp.element.createElement )( FlittContent, null ),
    edit: Object( window.wp.element.createElement )( FlittContent, null ),
    canMakePayment: () => true,
    ariaLabel: flitt_label,
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Flitt_Block_Gateway);
