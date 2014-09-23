<?php

use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\RawTransaction;

require_once(__DIR__. '/../vendor/autoload.php');

// Load a 128 bit key, and convert this to extended key format.
$master = BIP32::master_key('41414141414141414141414141414141414141');
$def = "0'";

echo "\nMaster key\n m           : {$master[0]} \n";
// Define what derivation you wish to calculate.

$key = BIP32::build_key($master, $def);		// Build the extended key

// Display private extended key and the address that's derived from it.
echo "Generated key: note that all depth=1 keys are hardened. \n {$key[1]}        : {$key[0]}\n";
echo "             : ".BIP32::key_to_address($key[0])."\n";

// Convert the extended private key to the public key, and display the 
// address that's derived from it.
$pub = BIP32::extended_private_to_public($key);
echo "Public key\n {$pub[1]}        : {$pub[0]}\n";
echo "             : ".BIP32::key_to_address($pub[0])."\n";

$nextpub = BIP32::build_key($pub, '0');
echo  "Child key\n";
echo " {$nextpub[1]}      : {$nextpub[0]}\n";

/////////////////////////////
// Parameters for creation..
// Set up inputs here
$inputs = array(
    array(
        'txid' => '6737e1355be0566c583eecd48bf8a5e1fcdf2d9f51cc7be82d4393ac9555611c',
        'vout' => 0
    )
);
// Set up outputs here.
$outputs = array( '12jYGamrswiVAenpDKRJwytRya7PuFKbWv' => "0.00015");

////////////////////////////
// Parameters for signing.
// Create JSON inputs parameter
// - These can come from bitcoind, or just knowledge of the txid/vout/scriptPubKey,
//   and redeemScript if needed.
$json_inputs = json_encode(
    array(
        array(
            'txid' => '6737e1355be0566c583eecd48bf8a5e1fcdf2d9f51cc7be82d4393ac9555611c',
            'vout' => 0,
            // OP_DUP OP_HASH160 push14bytes       PkHash      OP_EQUALVERIFY OP_CHECKSIG
            'scriptPubKey' => '76a914'.'1303b2ac55afee338764702d762849cc91e5bf76'.'88ac')
    )
);
// Private Key
$wallet = array();
BIP32::bip32_keys_to_wallet($wallet, array($key), '00');

// Create raw transaction
$raw_transaction = RawTransaction::create($inputs, $outputs);

// Sign the transaction
$sign = RawTransaction::sign($wallet, $raw_transaction, $json_inputs);
print_r($sign);echo "\n";

