<?php
/**
 * ORC721 helper
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
require __DIR__ . "/BitcoinECDSA.php";
require __DIR__ . "/onix_wallet.php";
require __DIR__ . "/orc721_wallet.php";

if( ! is_file(__DIR__ . "/config.php") )
{
    die(
        "\n" .
        "Configuration file not found.\n" .
        "Please copy config_sample.php as config.php and edit it to suit your needs.\n" .
        "\n"
    );
}
include __DIR__ . "/config.php";

if( ! function_exists("gmp_init") )
{
    die(
        "\n" .
        "GMP functions unavailable.\n" .
        "Please install the php-gmp module using your package manager as root.\n" .
        "\n"
    );
}

if( ! function_exists("curl_init") )
{
    die(
        "\n" .
        "PHP Curl unavailable.\n" .
        "Please install the php-curl module using your package manager as root.\n" .
        "\n"
    );
}

$wallet = new orc721_wallet($wallet_params);

if( $argv[1] == "mint" )
{
    if( empty($argv[2]) ) die("\nMissing tokenid\n\n");
    if( ! is_numeric($argv[2]) ) die("\nTokenid must be numeric\n\n");
    if( ! is_string($argv[3]) ) die("\nMissing token URI\n\n");
    
    $txid = $wallet->mintWithTokenURI($argv[2], $argv[3]);
    if( $wallet->error ) echo "\n{$wallet->error}\n\n";
    else                 echo "\n{$txid}\n\n";
    exit;
}

if( $argv[1] == "transfer" )
{
    if( empty($argv[2]) ) die("\nMissing «from» address\n\n");
    if( empty($argv[3]) ) die("\nMissing «to» address\n\n");
    if( empty($argv[4]) ) die("\nMissing tokenid\n\n");
    if( ! is_numeric($argv[4]) ) die("\nTokenid must be numeric\n\n");
    
    $txid = $wallet->transferFrom($argv[2], $argv[3], $argv[4]);
    if( $wallet->error ) echo "\n{$wallet->error}\n\n";
    else                 echo "\n$txid\n\n";
    exit;
}

if( $argv[1] == "uri" )
{
    if( empty($argv[2]) ) die("\nMissing tokenid\n\n");
    if( ! is_numeric($argv[2]) ) die("\nTokenid must be numeric\n\n");
    
    $res = $wallet->tokenURI($argv[2]);
    if( $wallet->error ) echo "\n{$wallet->error}\n\n";
    else                 echo "\n$res\n\n";
    exit;
}

if( $argv[1] == "ownerof" )
{
    if( empty($argv[2]) ) die("\nMissing tokenid\n\n");
    if( ! is_numeric($argv[2]) ) die("\nTokenid must be numeric\n\n");
    
    $res = $wallet->ownerOf($argv[2]);
    if( $wallet->error ) echo "\n{$wallet->error}\n\n";
    else                 echo "\n$res\n\n";
    exit;
}

if( ! empty($argv[1]) ) echo "\n{$argv[1]} is not a valid command.\n\n";

echo "\nUsage:\n\n" .
     "Minting a token:\n" .
     "    php helper.php mint tokenid 'ipfs://token_asset_ipfs_cid'\n\n" .
     "Transferring a token:\n" .
     "    php helper.php transfer from_addr to_addr tokenid\n\n" .
     "Getting a token URI:\n" .
     "    php helper.php uri tokenid\n\n" .
     "Getting a token owner address:\n" .
     "    php helper.php ownerof tokenid\n\n"
     ;
