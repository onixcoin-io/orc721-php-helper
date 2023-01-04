
# ORC721 PHP helper

This script allows you to interact with a ORC721 smart contract to mint and transfer NFTs.

## Configuration

Before using the script, copy the `config_sample.php` file as `config.php` and edit it:

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
        "DEFAULT_GAS_PRICE"      => 0.00000001,
        "DEFAULT_GAS_LIMIT"      => 250000,
    );

- `NETWORK_NAME` is always `ONIX`.
- `WALLET_DRIVER` is always `orc721_wallet`.
- `RPC_*` params are the ones you've set on your `.onix/onix.conf` file.
- `WALLET_PASSPHRASE` should be empty or the passphrase used to encrypt the wallet.
- `MAIN_WALLET_ADDRESS` is the Onix wallet address that owns the smart contract (the one used to deploy it).
- `TOKEN_CONTRACT_ADDRESS` is the address of the smart contract.
- `DEFAULT_GAS_PRICE` and `DEFAULT_GAS_LIMIT` can be modified if you want to experiment with gas spending.

## Methods

To mint a token, run:

    php helper.php mint tokenid uri
   
- `tokenid` must be numeric.
- `uri` must be an IPFS uri (E.G. `ipfs://QmemvfSKVNWxWdSHgNrWgdyMqzCGYqyet6DnfLNEbusKip`)
  or a fully qualified URL (E.G. `https://domain.tld/something.png`).
- The result will be the transaction id.

To transfer a token, run:

    php helper.php transfer from_addr to_addr tokenid
   
- `from_addr` must be the Onix wallet address that owns the token (usually, the minter).
- `to_addr` is the destination Onix wallet address.
- `tokenid` is the numeric token to transfer.
- The result will be the transaction id.

To get the URI of a given token id, run:

    php helper.php uri tokenid
   
To get the address that owns a given tokenid, run:

    php helper.php ownerof tokenid
   
## Help

Run `php helper.php` without params to show usage help.
