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

// list of private keys
$keys = [
    'RA4Z276Mvpoms3ofrZMmdpMiyNRgRfSy1FzpU3Qq3Jxo3NKnVq3Z',
    'RF15YsTgPrBHbnzf2H6bwt8wtNvftxapcLvUBLVhzzUWvKf4Qdpx'
];

// example UTXO, from `./friulcoin listunspents 0`
$inputs = array(
    array(
        'txid' => 'f07b6d7e2d18c23697b8b77b8da531345340044b559d9774e1456d53e4ff9d70',
        'vout' => 1,
        'address' => 'FP2rYTVc7N945cZNNvfGjUCxC8mLnNcoHM',
        'scriptPubKey' => "76a914bcb2acee3ec764ac2f4ab042a76956f538915acb88ac",
        'amount' => BitcoinLib::toSatoshi(1.89990000),
    )
);

// 'user input'
$sendTo = 'FUEtWp3zbU4RgWAVzTRNoes2d1hozsffsu';
$sendAmount = BitcoinLib::toSatoshi(0.1);

$changeAddress = 'FP2rYTVc7N945cZNNvfGjUCxC8mLnNcoHM';
$fee = BitcoinLib::toSatoshi(0.0001);

// calculate total of input(s)
$inputsTotal = array_sum(array_column($inputs, 'amount'));

// calculate remaining change
$change = $inputsTotal - $sendAmount - $fee;

// Set up outputs here.
$outputs = [
    $sendTo => $sendAmount
];

// if there's change then we need another output
if ($change > 0) {
    $outputs[$changeAddress] = $change;
}

var_dump($inputsTotal, $fee, $change, $outputs);

// import private keys
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, $keys);

// Create raw transaction
$raw_transaction = RawTransaction::create($inputs, $outputs);

$sign = RawTransaction::sign($wallet, $raw_transaction, json_encode($inputs));
print_r($sign); echo "\n";

// Get the transaction hash from the raw transaction
$txid = RawTransaction::txid_from_raw($sign['hex']);
print_r($txid); echo "\n";
