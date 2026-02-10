<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compatibility class for Subscriptions.
 */
class Flitt_WC_Subscriptions_Compat
{
    const META_NAME_FLITT_RECTOKEN = 'flitt_token';

    /**
     * @var Flitt_WC_Payment_Gateway
     */
    private $paymentGateway;

    public function __construct($paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
        add_filter('flitt_wc_gateway_flitt_payment_params', [$this, 'subscriptionsPaymentParams'], 10, 2);
        add_filter('flitt_wc_gateway_process_payment_complete', [$this, 'subscriptionsProcessPaymentComplete'], 10, 2);
        add_action('woocommerce_scheduled_subscription_payment_' . $this->paymentGateway->id, [$this, 'scheduled_subscription_payment'], 10, 2);
        add_action('flitt_wc_gateway_receive_valid_callback', [$this, 'saveToken'], 10, 2);
    }

    public function subscriptionsPaymentParams($params, $order)
    {
        if ($this->has_subscription($order)) {
            $params['required_rectoken'] = 'Y';
            if ((int)$order->get_total() === 0) {
                $order->add_order_note(__('Payment free trial verification', 'flitt-payment-gateway-for-woocommerce'));
                $params['verification'] = 'Y';
                $params['amount'] = 1;
            }
        }

        return $params;
    }

    public function subscriptionsProcessPaymentComplete($resultData, $order)
    {
        global $woocommerce;

        if ($this->has_subscription($order)) {
            if (get_current_user_id() === 0) {
                wc_add_notice(__('You must be logged in.', 'flitt-payment-gateway-for-woocommerce'), 'error');
                return [
                    'result' => 'fail',
                    'redirect' => $woocommerce->cart->get_checkout_url()
                ];
            }
        }

        return $resultData;
    }

    /**
     * Is $order a subscription?
     * @param WC_Order $order
     * @return boolean
     */
    public function has_subscription($order)
    {
        return (function_exists('wcs_order_contains_subscription') && (wcs_order_contains_subscription($order) || wcs_is_subscription($order) || wcs_order_contains_renewal($order)));
    }

    /**
     * @param $requestBody
     * @param $order
     */
    public function saveToken($requestBody, $order)
    {
        if (!empty($requestBody['rectoken']) && $this->has_subscription($order)) {
            $userID = $order->get_user_id();

            $metaValue = [
                'token' => $requestBody['rectoken'],
                'payment_id' => $this->paymentGateway->id
            ];

            if ($this->isTokenAlreadySaved($requestBody['rectoken'], $userID)) {
                update_user_meta($userID, self::META_NAME_FLITT_RECTOKEN, $metaValue);
            } else add_user_meta($userID, self::META_NAME_FLITT_RECTOKEN, $metaValue);
        }
    }

    /**
     * @param $token
     * @param $userID
     * @return bool
     */
    private function isTokenAlreadySaved($token, $userID)
    {
        $userTokens = get_user_meta($userID, self::META_NAME_FLITT_RECTOKEN);

        return array_search($token, array_column($userTokens, 'token'), true);
    }


    /**
     * scheduled_subscription_payment function.
     *
     * @param $amount_to_charge float The amount to charge.
     * @param $renewal_order WC_Order A WC_Order object created to record the renewal payment.
     */
    public function scheduled_subscription_payment($amount_to_charge, $renewal_order)
    {
        if ($amount_to_charge === 0) {
            $renewal_order->payment_complete();
        }

        try {
            $amount = (int)round($amount_to_charge * 100);
            $customerId = $renewal_order->get_customer_id();

            if (!$customerId)
                throw new Exception(__('Customer not found.', 'flitt-payment-gateway-for-woocommerce'));

            $token = get_user_meta($customerId, self::META_NAME_FLITT_RECTOKEN);

            if (is_null($token))
                throw new Exception("Token not found.");

            if ($token[0]['payment_id'] !== $this->paymentGateway->id) //checkToken
                throw new Exception("Token expired, or token not found.");

            $order_desc = sprintf(
                /* translators: Flitt order number */
                __('Recurring payment for: %s', 'flitt-payment-gateway-for-woocommerce'),
                $renewal_order->get_order_number()
            );

            $subscriptionPayment = $this->paymentGateway->recurring([
                'order_id' => $this->paymentGateway->createFlittOrderID($renewal_order),
                'amount' => $amount,
                'rectoken' => $token[0]['token'],
                'sender_email' => $renewal_order->get_billing_email(),
                'currency' => get_woocommerce_currency(),
                'order_desc' => $order_desc,
            ]);

            if ($subscriptionPayment->order_status === 'approved') {
                $renewal_order->update_status('completed');
                $renewal_order->payment_complete($subscriptionPayment->payment_id);
                $renewal_order->add_order_note("Flitt subscription payment successful.<br/>Flitt ID: $subscriptionPayment->payment_id");
            } else {
                throw new Exception("Transaction ERROR: order $subscriptionPayment->order_status<br/>Flitt ID: $subscriptionPayment->payment_id");
            }
        } catch (Exception $e) {
            $message = sprintf(
                  /* translators: error message */
                esc_html__('Subscription payment failed. Reason: %s', 'flitt-payment-gateway-for-woocommerce'),
                $e->getMessage()
            );
            $renewal_order->update_status('failed', $message);
        }
    }
}
