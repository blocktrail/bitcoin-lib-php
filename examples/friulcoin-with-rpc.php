<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\Jsonrpcclient;
use BitWasp\BitcoinLib\RawTransaction;

/*
-- FRIULCOIN --
PUBKEY_ADDRESS = 35,
SCRIPT_ADDRESS = 5,

var_dump(dechex(35), dechex(5)); // "23|05"

-- FRIULCOIN TESTNET --
PUBKEY_ADDRESS_TEST = 111,
SCRIPT_ADDRESS_TEST = 196,

var_dump(dechex(111), dechex(196)); // "6f|c4"
 */

require_once(__DIR__. '/../vendor/autoload.php');

// set the magic bytes for friulcoin
BitcoinLib::setMagicByteDefaults("23|05");

// setup RPC info
$rpcHost = '127.0.0.1';
$rpcUser = 'friulcoinrpc';
$rpcPassword = '6Wk1SYL7JmPYoUeWjYRSdqij4xrM5rGBvC4kbJipLVJK';
$rpcPort = 8333;

$rpc = new Jsonrpcclient(['url' => "http://{$rpcUser}:{$rpcPassword}@{$rpcHost}:{$rpcPort}"]);

if ($rpc->getinfo() == null) {
    throw new \Exception("Can't connect to bitcoind");
}

// list of private keys in WIF format (from wallet)
$privateKeyWIF1 = 'RA4Z276Mvpoms3ofrZMmdpMiyNRgRfSy1FzpU3Qq3Jxo3NKnVq3Z';
$privateKeyWIF2 = 'RF15YsTgPrBHbnzf2H6bwt8wtNvftxapcLvUBLVhzzUWvKf4Qdpx';

// convert WIF -> private key
$privateKey1 = BitcoinLib::WIF_to_private_key($privateKeyWIF1);
$privateKey2 = BitcoinLib::WIF_to_private_key($privateKeyWIF2);

// convert private key -> public key
$publicKey1 = BitcoinLib::private_key_to_public_key($privateKey1['key'], $privateKey1['is_compressed']);
$publicKey2 = BitcoinLib::private_key_to_public_key($privateKey2['key'], $privateKey2['is_compressed']);

// get addresses for our public keys
$address1 = BitcoinLib::public_key_to_address($publicKey1);
$address2 = BitcoinLib::public_key_to_address($publicKey2);

// get unspent outputs for addresses
$unspents1 = $rpc->listunspent(0, 9999999, [$address1]);
$unspents2 = $rpc->listunspent(0, 9999999, [$address2]);

// var_dump($unspents1);
// var_dump($unspents2);

// check if we have unspents for $address1 then we send from $address1 -> $address2
//  otherwise we sent from $address2 -> $address1
if (count($unspents1) > 0) {
    $fromPrivateKeyWIF = $privateKeyWIF1;
    $fromUnspents = $unspents1;
    $changeAddress = $address1;

    $toAddress = $address2;
} else {
    $fromPrivateKeyWIF = $privateKeyWIF2;
    $fromUnspents = $unspents2;
    $changeAddress = $address2;

    $toAddress = $address1;
}

$toAmount = BitcoinLib::toSatoshi(0.1);

$fee = BitcoinLib::toSatoshi(0.0001);

$inputs = [];
foreach ($fromUnspents as $unspent) {
    $inputs[] = [
        'txid' => $unspent['txid'],
        'vout' => $unspent['vout'],
        'scriptPubKey' => $unspent['scriptPubKey'],
        'amount' => BitcoinLib::toSatoshi($unspent['amount']),
    ];
}

// calculate total of input(s)
$inputsTotal = array_sum(array_column($inputs, 'amount'));

// calculate remaining change
$change = $inputsTotal - $toAmount - $fee;

// Set up outputs here.
$outputs = [
    $toAddress => $toAmount
];

// if there's change then we need another output
if ($change > 0) {
    $outputs[$changeAddress] = $change;
}

// var_dump($inputsTotal, $fee, $change, $outputs);

// import private keys
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, [$fromPrivateKeyWIF]);

// Create raw transaction
$raw_transaction = RawTransaction::create($inputs, $outputs);

$sign = RawTransaction::sign($wallet, $raw_transaction, json_encode($inputs));
echo "signInfo: "; print_r($sign); echo "\n";

// Get the transaction hash from the raw transaction
$txid = RawTransaction::txid_from_raw($sign['hex']);
echo "txId: "; print_r($txid); echo "\n";

do {
    $sendit = readline("do you want to send it? (say YES or NO) ");
    if (strtolower($sendit) === "yes") {
        if ($rpc->sendrawtransaction($sign['hex'])) {
            echo "SEND!";
            exit;
        }
    } else if (strtolower($sendit) === "no") {
        exit;
    }
} while(true);
