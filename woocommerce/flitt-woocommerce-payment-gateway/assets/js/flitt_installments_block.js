const flitt_installments_settings = window.wc.wcSettings.getSetting('flitt_installments_data', {});
const flitt_installments_label = flitt_installments_settings.title || window.wp.i18n.__('TBC Buy Now Pay Later', 'flitt-payment-gateway-for-woocommerce');
const flitt_installments_icons = flitt_installments_settings.icons || [];

const flitt_installments_el = window.wp.element.createElement;

const FlittInstallmentsLabel = () => {
    return flitt_installments_el(
        'span',
        { style: { display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } },
        flitt_installments_el('span', null, flitt_installments_label),
        ...flitt_installments_icons.map((src, i) =>
            flitt_installments_el('img', {
                key: i,
                src: src,
                alt: flitt_installments_label,
                style: { height: '24px', width: 'auto' },
            })
        )
    );
};

const FlittInstallmentsContent = () => {
    return window.wp.htmlEntities.decodeEntities(flitt_installments_settings.description || '');
};

const Flitt_Installments_Block_Gateway = {
    name: 'flitt_installments',
    label: Object(window.wp.element.createElement)(FlittInstallmentsLabel, null),
    content: Object(window.wp.element.createElement)(FlittInstallmentsContent, null),
    edit: Object(window.wp.element.createElement)(FlittInstallmentsContent, null),
    canMakePayment: () => true,
    ariaLabel: flitt_installments_label,
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Flitt_Installments_Block_Gateway);
