<?php

use Illuminate\Support\Fluent;
use Orwallet\FoxtrotSdk\Facade\Foxtrot;

describe("Execute happy path", function () {
    it("should make a successful deposit request response", function () {
        $response = new Fluent([
            "code" => "P0001",
            "message" => "payment successful!|Success",
            "orderNo" => "10014017255896596019",
            "merNo" => "100140",
            "billNo" => "2474193466",
            "amount" => "10.00",
            "currency" => "1",
        ]);

        Foxtrot::shouldReceive("validateDeposit->requestDeposit")->once()->andReturn($response);

        expect(Foxtrot::validateDeposit()->requestDeposit()->toArray())->toEqual($response->toArray());
    });

    it("should make a success refund request response", function () {
        $response = new Fluent([
            "refundStatus" => "S0004",
            "orderNo" => "10014017255896596019",
            "merNo" => "100140",
            "amount" => "10.00",
            "signature" => "62fc20db3900f4ff389f58a2c6323995",
        ]);

        Foxtrot::shouldReceive("validateRefund->requestRefund")->once()->andReturn($response);

        expect(Foxtrot::validateRefund()->requestRefund()->toArray())->toEqual($response->toArray());
    });

    it("should be verify the signature correctly", function () {
        Foxtrot::setVault([
            "md5_key" => "md5testkey",
            "merchant_id" => "merchantidtestkey",
            "api_url" => "http://foxtrot.test/mock/api",
        ]);

        request()->replace(["md5Info" => md5(
            "merchantidtestkey"
                . "testtxnnumber123456"
                . "USD"
                . "100.00"
                . "http://example.com"
                . "md5testkey",
        )]);

        expect(Foxtrot::verifySignature(
            "testtxnnumber123456",
            "USD",
            "100.00",
            "http://example.com"
        ))->toBeTrue();
    });
});

describe("Execute sad path", function () {
    it("should throw an exception if vault is not set", function () {
        Foxtrot::requestDeposit();
    })->throws(Exception::class);

    it("should throw an exception if signature is not valid", function () {
        Foxtrot::setVault([
            "md5_key" => "md5testkey",
            "merchant_id" => "merchantidtestkey",
            "api_url" => "http://foxtrot.test/mock/api",
        ]);

        request()->replace(["md5Info" => "md5infohash"]);

        Foxtrot::verifySignature(
            "testtxnnumber123456",
            "USD",
            "100.00",
            "http://example.com"
        );
    })->throws(Exception::class);
});
