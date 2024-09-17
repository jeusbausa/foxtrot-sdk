<?php

namespace Orwallet\FoxtrotSdk;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Orwallet\FoxtrotSdk\Constants;
use Orwallet\FoxtrotSdk\Enums\Currency;

class Foxtrot
{
    protected Collection $vault;
    private array $headers;
    private array $payload;

    public function __construct(array $vault)
    {
        $this->headers = [];
        $this->vault = collect();

        $this->setVault($vault);
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function validateDeposit(array $payload)
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

        $this->setPayload($payload);

        return $this;
    }

    public function setPayload(array $payload)
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
            "currency" => $payload->get("currency"),
            "cardNum" => $payload->get("card_number", ""),
            "month" => $payload->get("month"),
            "cvv2" => $payload->get("cvv2"),
            "returnURL" => $payload->get("return_url"),
            "language" => Constants::LANGUAGE,
            "billNo" => $payload->get("bill_no"),
            "ip" => $payload->get("ip"),
            "md5Info" => md5(
                $this->vault->get("merchant_id")
                    . $payload->get("billNo")
                    . $this->getCurrencyNumber($payload->get("currency"))
                    . $payload->get("amount")
                    . $payload->get("return_url")
                    . $this->vault->get("md5_key")
            ),
            "notifyUrl" => $payload->get("notify_url"),
        ];

        return $this;
    }

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

    public function setRefundPayload(array $payload)
    {
        $payload = collect($payload);

        $this->payload = [
            "json" => [
                "merNo" => $this->vault->get("mer_no"),
                "terminalNo" =>  $this->vault->get("terminal_no"),
                "encryption" =>  $payload->get("hash"),
                "refundOrders" => [
                    [
                        "currency" => $payload->get("currency"),
                        "orderNo" => $payload->get("order_no"),
                        "refundAmount" => $payload->get("refund_amount"),
                        "refundReason" => $payload->get("refund_reason"),
                        "tradeAmount" => $payload->get("trade_amount"),
                        "tradeNo" => $payload->get("trade_no"),
                    ],
                ],
            ]
        ];
    }

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

    public function request()
    {
        return $this->http()->post($this->vault->get("api_url") . "/carespay/pay", $this->payload);
    }

    public function requestRefund()
    {
        return $this->http()->post($this->vault->get("api_url") . "/refund", $this->payload);
    }

    private function http()
    {
        return Http::withHeaders($this->headers)->asForm();
    }
}
