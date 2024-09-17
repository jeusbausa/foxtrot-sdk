<?php

namespace Orwallet\FoxtrotSdk;

class Constants
{
    public const PRODUCT_INFO = "PayStage";
    public const LANGUAGE = "EN";

    /* Code status */
    public const SUCCESS = "P0001";
    public const FAILED = "P0002";
    public const PENDING = "P0004";

    /* Refund status code */
    public const REFUND_SUCCESSFUL = "S0004";
    public const REFUND_FAILED = "S0005";
    public const REFUNDED = "C0029";
    public const REFUND_PENDING = "S0006";
    public const SIGNATURE_ERROR = "C0014";

    /* Trade status code */
    public const PAYMENT_SUCCESS = "S0001";
    public const PAYMENT_FAILED = "S0002";
    public const PAYMENT_PENDING = "S0003";
    public const CHARGEBACK = "S0007";
}
