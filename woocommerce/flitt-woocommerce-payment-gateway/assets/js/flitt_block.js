const flitt_settings = window.wc.wcSettings.getSetting( 'flitt_data', {} );
const flitt_label = flitt_settings.title || window.wp.i18n.__( 'Pay with Visa/Mastercard via Flitt', 'flitt-payment-gateway-for-woocommerce');
const flitt_icons = flitt_settings.icons || [];

const flitt_el = window.wp.element.createElement;

const FlittLabel = () => {
    return flitt_el(
        'span',
        { style: { display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } },
        flitt_el('span', null, flitt_label),
        flitt_el(
            'span',
            { style: { display: 'inline-flex', alignItems: 'center', gap: '6px' } },
            ...flitt_icons.map((src, i) =>
                flitt_el('img', {
                    key: i,
                    src: src,
                    alt: flitt_label,
                    style: { height: '24px', width: 'auto' },
                })
            )
        )
    );
};

const FlittContent = () => {
    return window.wp.htmlEntities.decodeEntities(
        flitt_settings.description || window.wp.i18n.__('Pay securely by Credit or Debit Card or Internet Banking through flitt.com service.', 'flitt-payment-gateway-for-woocommerce')
    );
};
const Flitt_Block_Gateway = {
    name: 'flitt',
    label: Object( window.wp.element.createElement )( FlittLabel, null ),
    content: Object( window.wp.element.createElement )( FlittContent, null ),
    edit: Object( window.wp.element.createElement )( FlittContent, null ),
    canMakePayment: () => true,
    ariaLabel: flitt_label,
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Flitt_Block_Gateway);
