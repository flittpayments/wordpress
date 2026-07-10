const flitt_opb_settings = window.wc.wcSettings.getSetting('flitt_opb_data', {});
const flitt_opb_label = flitt_opb_settings.title || window.wp.i18n.__('Pay By Bank', 'flitt-payment-gateway-for-woocommerce');
const flitt_opb_icons = flitt_opb_settings.icons || [];

const flitt_opb_el = window.wp.element.createElement;

const FlittOpbLabel = () => {
    return flitt_opb_el(
        'span',
        { style: { display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } },
        flitt_opb_el('span', null, flitt_opb_label),
        flitt_opb_el(
            'span',
            { style: { display: 'inline-flex', alignItems: 'center', gap: '6px' } },
            ...flitt_opb_icons.map((src, i) =>
                flitt_opb_el('img', {
                    key: i,
                    src: src,
                    alt: flitt_opb_label,
                    style: { height: '24px', width: 'auto' },
                })
            )
        )
    );
};

const FlittOpbContent = () => {
    return window.wp.htmlEntities.decodeEntities(flitt_opb_settings.description || '');
};

const Flitt_Opb_Block_Gateway = {
    name: 'flitt_opb',
    label: Object(window.wp.element.createElement)(FlittOpbLabel, null),
    content: Object(window.wp.element.createElement)(FlittOpbContent, null),
    edit: Object(window.wp.element.createElement)(FlittOpbContent, null),
    canMakePayment: () => true,
    ariaLabel: flitt_opb_label,
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Flitt_Opb_Block_Gateway);
