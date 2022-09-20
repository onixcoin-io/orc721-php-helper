<?php
require __DIR__ . "/BitcoinECDSA.php";
require __DIR__ . "/onix_wallet.php";
require __DIR__ . "/orc721_wallet.php";

use orcphphelpers\onix_wallet;
use orcphphelpers\orc721_wallet;

$wallet_params = array(
    "NETWORK_NAME"           => "ONIX",   
    "WALLET_DRIVER"          => "orc721_wallet",   
    "RPC_HOST"               => "127.0.0.1",   
    "RPC_USER"               => "onixrpc",   
    "RPC_PASS"               => "onixpass",   
    "RPC_PORT"               => 5889,   
    "WALLET_PASSPHRASE"      => "walletpass",   
    "MAIN_WALLET_ADDRESS"    => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",   
    "TOKEN_CONTRACT_ADDRESS" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",   
    "TOKEN_SYMBOL"           => "TOKENSYMBOL",   
    "DEFAULT_GAS_PRICE"      => 0.00000001,   
    "DEFAULT_GAS_LIMIT"      => 250000,   
    "MIN_BALANCE_FOR_FEES"   => 0.01,   
);

$onixwallet = new onix_wallet(
    $wallet_parms["RPC_USER"],
    $wallet_parms["RPC_PASS"],
    $wallet_parms["RPC_HOST"],
    $wallet_parms["RPC_PORT"]
);

echo "Checking onix balance...\n";
$res = $onixwallet->getbalance();
if( $onixwallet->error ) echo "{$onixwallet->error}\n";
else                     echo "> $res\n";
echo "\n";

$aocwallet = new orc721_wallet($wallet_parms);
$number    = 256256;

echo "Owner of token #$number:\n";
$res = $aocwallet->ownerOf($number);
if( $aocwallet->error ) echo "> {$aocwallet->error}\n";
else                    echo "> $res\n";
echo "\n";

echo "URI of token #$number:\n";
$res = $aocwallet->tokenURI($number);
if( $aocwallet->error ) echo "> {$aocwallet->error}\n";
else                    echo "> $res\n";
echo "\n";

echo "Minting token:\n";
$res = $aocwallet->mintWithTokenURI($number, "ipfs://xxxxxxxxxxxxxxxxxxxxxxxxx");
if( $aocwallet->error ) echo "> {$aocwallet->error}\n";
else                    echo "> $res\n";
echo "\n";

echo "Changing token URI:\n";
$res = $aocwallet->setTokenURI($number, "ipfs://yyyyyyyyyyyyyyyyyyyyyyyyy");
if( $aocwallet->error ) echo "> {$aocwallet->error}\n";
else                    echo "> $res\n";
echo "\n";

echo "Transferring token:\n";
$res = $aocwallet->transferFrom("", "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", 1);
if( $aocwallet->error ) echo "> {$aocwallet->error}\n";
else                    echo "> $res\n";
echo "\n";
