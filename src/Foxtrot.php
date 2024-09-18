<?php

namespace Orwallet\FoxtrotSdk;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Orwallet\FoxtrotSdk\Constants;
use Orwallet\FoxtrotSdk\Enums\Currency;
use Orwallet\FoxtrotSdk\Exception\FoxtrotFailedResponseException;

class Foxtrot
{
    protected Collection $vault;
    private array $headers;
    private array $payload;

    public function __construct()
    {
        $this->headers = [];
        $this->vault = collect();
    }

    /**
     * Set custom http headers
     *
     * @param array  $headers
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Validate deposit request
     *
     * @param array  $headers
     * @return self
     */
    public function validateDeposit(array $payload): self
    {
        Validator::make($payload, [
            "address" => "nullable|string",
            "state" => "nullable|string",
            "city" => "nullable|string",
            "country" => "nullable|string",
            "phone" => "nullable|string",
            "zip_code" => "nullable|string",
            "first_name" => "nullable|string",
            "last_name" => "nullable|string",
            "email" => "nullable|string",
            "shipping_first_name" => "nullable|string",
            "shipping_last_name" => "nullable|string",
            "shipping_email" => "nullable|string",
            "amount" =>  "required|string",
            "currency" => "required|string",
            "card_number" => "required|string",
            "month" => "required|string",
            "cvv2" => "required|string",
            "return_url" => "required|string",
            "bill_no" => "required|string",
            "ip" => "required|ip",
            "notify_url" => "required|url",
        ])->validate();

        $this->setDepositPayload($payload);

        return $this;
    }

    /**
     * Validate refund request
     *
     * @param array $payload
     * @return self
     */
    public function validateRefund(array $payload): self
    {
        Validator::make($payload, [
            "merchant_id" => "required|string",
            "order_no" => "required|string",
            "amount" => "required|string",
            "signature" => "required|string",
            "redirect_url" => "required|string",
            "remark" => "required|string",
        ])->validate();

        $this->setRefundPayload($payload);

        return $this;
    }

    /**
     * Set deposit payload in constructor
     *
     * @param array $payload
     * @return self
     */
    private function setDepositPayload(array $payload): self
    {
        $payload = collect($payload);

        $this->payload = [
            "address" => $payload->get("address", ""),
            "state" => $payload->get("state", ""),
            "city" => $payload->get("city", ""),
            "country" => $payload->get("country", ""),
            "phone" => $payload->get("phone", ""),
            "zipCode" => $payload->get("zip"),
            "firstName" => $payload->get("first_name", ""),
            "lastName" => $payload->get("last_name", ""),
            "email" => $payload->get("email", ""),
            "shippingFirstName" => $payload->get("first_name", ""),
            "shippingLastName" => $payload->get("last_name", ""),
            "shippingEmail" => $payload->get("email", ""),
            "amount" =>  $payload->get("amount"),
            "productInfo" => Constants::PRODUCT_INFO,
            "merNo" => $this->vault->get("merchant_id"),
            "currency" => $this->getCurrencyNumber($payload->get("currency")),
            "cardNum" => $payload->get("card_number", ""),
            "month" => $payload->get("month"),
            "cvv2" => $payload->get("cvv2"),
            "returnURL" => $payload->get("return_url"),
            "language" => Constants::LANGUAGE,
            "billNo" => $payload->get("bill_no"),
            "ip" => $payload->get("ip"),
            "md5Info" => md5(
                $this->vault->get("merchant_id")
                    . $payload->get("bill_no")
                    . $this->getCurrencyNumber($payload->get("currency"))
                    . $payload->get("amount")
                    . $payload->get("return_url")
                    . $this->vault->get("md5_key")
            ),
            "notifyUrl" => $payload->get("notify_url"),
        ];

        return $this;
    }

    /**
     * Set refund payload in constructor
     *
     * @param array $payload
     * @return self
     */
    public function setRefundPayload(array $payload): self
    {
        $payload = collect($payload);

        $this->payload = [
            "merNo" => $this->vault->get("merchant_id"),
            "orderNo" => $payload->get("order_no"),
            "amount" => $payload->get("amount"),
            "signature" =>  md5(
                "amount=" . $payload->get("amount")
                    . "&merNo=" . $this->vault->get("merchant_id")
                    . "&orderNo=" .  $payload->get("order_no")
                    . "key=" . $this->vault->get("md5_key")
            ),
            "returnNotify" => $payload->get("redirect_url"),
            "remark" => $payload->get("remark"),
        ];

        return $this;
    }

    /**
     * Validate and set vault keys
     *
     * @param array $vault
     * @return void
     */
    public function setVault(array $vault): void
    {
        $validator = Validator::make($vault, [
            "merchant_id" => "required|string",
            "md5_key" => "required|string",
            "api_url" => "required|url",
            "payment_gateway" => "required",
        ]);

        $vault = $validator->validated();

        $this->vault = collect($vault);
    }

    /**
     * Makes a deposit request
     *
     * @return object
     */
    public function requestDeposit(): object
    {
        $response =  $this->http()->post($this->vault->get("api_url") . "/carespay/pay", $this->payload);

        $response_object = $response->object();

        throw_if(
            !$response->ok(),
            FoxtrotFailedResponseException::class,
            $this->payload,
            $response,
            $response_object?->message || "Unexpected error occurred, please try again later",
        );

        $is_success = $response_object->code === Constants::SUCCESS
            && $response_object->tradeStatus === Constants::PAYMENT_SUCCESS;

        $is_processing = $response_object->code === Constants::PENDING
            && $response_object->tradeStatus === Constants::PAYMENT_PENDING;

        throw_if(
            $response->ok() && (!$is_success && !$is_processing),
            FoxtrotFailedResponseException::class,
            $this->payload,
            $response,
            "{$response_object->message} ($response_object->code)",
            [],
            ErrorCodes::isFixableViaMerchant($response_object->code),
        );

        return $response_object;
    }

    /**
     * Makes a refund request
     *
     * @return object
     */
    public function requestRefund(): object
    {
        $response = $this->http()->post($this->vault->get("api_url") . "/refund", $this->payload);

        $response_object = $response->object();

        throw_if(
            !$response->ok(),
            FoxtrotFailedResponseException::class,
            $this->payload,
            $response,
            $response_object?->message || "Unexpected error occurred, please try again later",
        );

        throw_if(
            $response->ok() && $response_object->refundStatus === Constants::REFUND_FAILED,
            FoxtrotFailedResponseException::class,
            $this->payload,
            $response,
            "{$response_object->message} ($response_object->refundStatus)",
        );

        return $response_object;
    }

    /**
     * Initialize http request with headers contains form parameters
     *
     * @return PendingRequest
     */
    private function http(): PendingRequest
    {
        return Http::withHeaders($this->headers)->asForm();
    }

    /**
     * Get a currency number based on a currency enums
     * @param Currency $currency
     * @return init
     */
    private function getCurrencyNumber(Currency $currency): int
    {
        return match ($currency) {
            Currency::USD => 1,
            Currency::EUR => 2,
            Currency::GBP => 4,
            Currency::JPY => 6,
            default => throw new Exception("currency is not supported " . $currency->value),
        };
    }

    /**
     * Verify signature based on vault and transaction
     * @param string $transaction_number
     * @param string $currency
     * @param string $credit_amount
     * @param string $redirect_url
     * @return bool
     */
    public function verifySignature(
        string $transaction_number,
        string $currency,
        string $credit_amount,
        string $redirect_url,
    ): bool {
        $request = request();

        $is_valid = $request->input("md5Info") === md5(
            $this->vault->get("merchant_id")
                . $transaction_number
                . $currency
                . number_format($credit_amount, 2, ".", "")
                . $redirect_url
                . $this->vault->get("md5_key")
        );

        throw_if(
            !$is_valid,
            Exception::class,
            "[Foxtrot Care] invalid signature received, transaction number: {$transaction_number}",
        );

        return $is_valid;
    }
}
