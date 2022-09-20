<?php
namespace orcphphelpers;

use orcphphelpers\BitcoinECDSA;

/*
;===================================================================================;
; Driver params template - Paste on the coin details editor and customize as needed ;
;===================================================================================;
; Note: DO NOT SET NETWORK WITHDRAWAL FEES TO TOKENS IN THE MANAGER - They use gas  ;
;===================================================================================;

NETWORK_NAME           = "ONIX"
WALLET_DRIVER          = "orc721_wallet"
RPC_HOST               = "x.x.x.x"
RPC_USER               = "xxxxxxx"
RPC_PASS               = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
RPC_PORT               = 5889
WALLET_PASSPHRASE      = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
MAIN_WALLET_ADDRESS    = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
TOKEN_CONTRACT_ADDRESS = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
TOKEN_SYMBOL           = "SYMBOL"
DEFAULT_GAS_PRICE      = 0.00000001
DEFAULT_GAS_LIMIT      = 250000
MIN_BALANCE_FOR_FEES   = 0.01
*/

class orc721_wallet
{
    protected $RPC_HOST               = "x.x.x.x";
    protected $RPC_USER               = "xxxxxxx";
    protected $RPC_PASS               = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
    protected $RPC_PORT               = 5889;
    protected $WALLET_PASSPHRASE      = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
    protected $MAIN_WALLET_ADDRESS    = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
    protected $TOKEN_CONTRACT_ADDRESS = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
    protected $TOKEN_SYMBOL           = "SYMBOL";
    protected $DEFAULT_GAS_PRICE      = 0.00000001;
    protected $DEFAULT_GAS_LIMIT      = 250000;
    protected $MIN_BALANCE_FOR_FEES   = 0.01;
    
    # NOTE: INTERNALS
    
    /**
     * @var BitcoinECDSA
     */
    protected $bitcoinECDSA;
    
    /**
     * @var onix_wallet
     */
    protected $wallet = null;
    
    public $error = "";
    
    protected $function_addresses = array(
        "ownerOf"          => "6352211e",
        "tokenURI"         => "c87b56dd",
        "mintWithTokenURI" => "50bb4e7f",
        "setTokenURI"      => "162094c4",
        "transferFrom"     => "23b872dd",
    );
    
    public function __construct($params)
    {
        foreach($params as $key => $val) $this->{$key} = $val;
        
        $this->wallet  = new onix_wallet(
            $this->RPC_USER,
            $this->RPC_PASS,
            $this->RPC_HOST,
            $this->RPC_PORT
        );
        
        $this->bitcoinECDSA = new BitcoinECDSA();
    }
    
    /**
     * @param int $token_id
     * 
     * @return mixed
     */
    public function ownerOf($token_id)
    {
        $this->error = "";
        
        $hexdata = implode("", array(
            $this->function_addresses[__FUNCTION__],
            $this->_to32bytesNumber(dechex($token_id)),
        ));
        
        $res = $this->wallet->call_contract(
            $this->TOKEN_CONTRACT_ADDRESS,
            $hexdata,
            $this->MAIN_WALLET_ADDRESS,
            $this->DEFAULT_GAS_LIMIT
        );
        
        if( $this->wallet->error )
        {
            $this->error = $this->wallet->error;
            return false;
        }
        
        $hex = ltrim($res["executionResult"]["output"], "0");
        $res = $this->wallet->fromhexaddress($hex);
        if( $this->wallet->error )
        {
            $this->error = $this->wallet->error;
            return false;
        }
        
        return $res;
    }
    
    /**
     * @param int $token_id
     * 
     * @return string
     */
    public function tokenURI($token_id)
    {
        $this->error = "";
        
        $hexdata = implode("", array(
            $this->function_addresses[__FUNCTION__],
            $this->_to32bytesNumber(dechex($token_id)),
        ));
        
        $res = $this->wallet->call_contract(
            $this->TOKEN_CONTRACT_ADDRESS,
            $hexdata,
            $this->MAIN_WALLET_ADDRESS,
            $this->DEFAULT_GAS_LIMIT
        );
        
        if( $this->wallet->error )
        {
            $this->error = $this->wallet->error;
            return false;
        }
        
        $output = $res["executionResult"]["output"];
        $output = substr($output, 128);
        $output = rtrim($output, "0");
        if( strlen($output) % 2 != 0 ) $output .= "0";
        return hex2bin($output);
    }
    
    /**
     * @param int $token_id
     * @param string $uri
     * 
     * @return string TXid
     */
    public function mintWithTokenURI($token_id, $uri)
    {
        $this->error = "";
        
        $balance = $this->wallet->getaddressbalance($this->MAIN_WALLET_ADDRESS);
        if( $this->wallet->error )
        {
            $this->error = "Balance checking error: " . $this->wallet->error;
            return false;
        }
        
        if( $this->MIN_BALANCE_FOR_FEES > 0 && $balance < $this->MIN_BALANCE_FOR_FEES )
        {
            $this->error = "Address balance is below minimum: $balance. Required: $this->MIN_BALANCE_FOR_FEES";
            return false;
        }
        
        if( ! empty($this->WALLET_PASSPHRASE) )
        {
            $this->wallet->walletpassphrase($this->WALLET_PASSPHRASE, 100);
            if( $this->wallet->error )
            {
                $this->error = $this->wallet->error;
                return false;
            }
        }
        
        $hexdata = implode("", array(
            $this->function_addresses[__FUNCTION__],
            $this->_to32bytesNumber($this->_addressToHash160($this->MAIN_WALLET_ADDRESS)),
            $this->_to32bytesNumber(dechex($token_id)),
            $this->_to32bytesNumber(dechex(32*3)),
            $this->_to32bytesString($uri),
        ));
        
        $res = $this->wallet->send_to_contract(
            $this->TOKEN_CONTRACT_ADDRESS,
            $hexdata,
            0,
            $this->DEFAULT_GAS_LIMIT,
            $this->DEFAULT_GAS_PRICE,
            $this->MAIN_WALLET_ADDRESS
        );
        
        if( $this->wallet->error )
        {
            $this->error = $this->wallet->error;
            return false;
        }
        
        return $res["txid"];
    }
    
    /**
     * @param int    $token_id
     * @param string $uri
     * 
     * @return string TXID
     */
    public function setTokenURI($token_id, $uri)
    {
        $this->error = "";
        
        $balance = $this->wallet->getaddressbalance($this->MAIN_WALLET_ADDRESS);
        
        if( $this->wallet->error )
        {
            $this->error = "Balance checking error: " . $this->wallet->error;
            return false;
        }
        
        if( $this->MIN_BALANCE_FOR_FEES > 0 && $balance < $this->MIN_BALANCE_FOR_FEES )
        {
            $this->error = "Address balance is below minimum: $balance. Required: $this->MIN_BALANCE_FOR_FEES";
            return false;
        }
        
        if( ! empty($this->WALLET_PASSPHRASE) )
        {
            $this->wallet->walletpassphrase($this->WALLET_PASSPHRASE, 100);
            if( $this->wallet->error )
            {
                $this->error = $this->wallet->error;
                return false;
            }
        }
        
        $hexdata = implode("", array(
            $this->function_addresses[__FUNCTION__],
            $this->_to32bytesNumber(dechex($token_id)),
            $this->_to32bytesNumber(dechex(32*2)),
            $this->_to32bytesString($uri),
        ));
        
        $res = $this->wallet->send_to_contract(
            $this->TOKEN_CONTRACT_ADDRESS,
            $hexdata,
            0,
            $this->DEFAULT_GAS_LIMIT,
            $this->DEFAULT_GAS_PRICE,
            $this->MAIN_WALLET_ADDRESS
        );
        
        if( $this->wallet->error )
        {
            $this->error = $this->wallet->error;
            return false;
        }
        
        return $res["txid"];
    }
    
    /**
     * @param string $address_from leave empty for transferring out from the system (minter) wallet
     * @param string $address_to
     * @param int    $token_id
     * 
     * @return mixed
     */
    public function transferFrom($address_from, $address_to, $token_id)
    {
        $this->error = "";
        
        if( empty($address_from) ) $address_from = $this->MAIN_WALLET_ADDRESS;
        
        $balance = $this->wallet->getaddressbalance($address_from);
        
        if( $this->wallet->error )
        {
            $this->error = "Balance checking error: " . $this->wallet->error;
            return false;
        }
        
        if($this->MIN_BALANCE_FOR_FEES > 0 && $balance < $this->MIN_BALANCE_FOR_FEES )
        {
            $this->error = "Address balance is below minimum: $balance. Required: $this->MIN_BALANCE_FOR_FEES";
            return false;
        }
        
        if( ! empty($this->WALLET_PASSPHRASE) )
        {
            $this->wallet->walletpassphrase($this->WALLET_PASSPHRASE, 100);
            if( $this->wallet->error )
            {
                $this->error = $this->wallet->error;
                return false;
            }
        }
        
        $hexdata = implode("", array(
            $this->function_addresses[__FUNCTION__],
            $this->_to32bytesNumber($this->_addressToHash160($address_from)),
            $this->_to32bytesNumber($this->_addressToHash160($address_to)),
            $this->_to32bytesNumber(dechex($token_id)),
        ));
        
        $res = $this->wallet->send_to_contract(
            $this->TOKEN_CONTRACT_ADDRESS,
            $hexdata,
            0,
            $this->DEFAULT_GAS_LIMIT,
            $this->DEFAULT_GAS_PRICE,
            $this->MAIN_WALLET_ADDRESS
        );
        
        if( $this->wallet->error )
        {
            $this->error = $this->wallet->error;
            return false;
        }
        
        return $res["txid"];
    }
    
    public function get_transaction($txid)
    {
        $tx = $this->wallet->gettransaction($txid);
        
        if( $this->wallet->error )
        {
            $this->error = $this->wallet->error;
            return false;
        }
        
        return $tx;
    }
    
    protected function _to32bytesNumber($arg)
    {
        return str_pad($arg, 64, "0", STR_PAD_LEFT);
    }
    
    protected function _to32bytesString($string)
    {
        $strlen = strlen($string);
        $hexlen = $this->_to32bytesNumber(dechex($strlen));
        $hexstr = bin2hex($string);
        
        if( strlen($hexstr) <= 64 ) return $hexlen . str_pad($hexstr, 64, "0", STR_PAD_RIGHT);
        
        $parts = explode("\n", wordwrap($hexstr, 64, "\n", true));
        foreach($parts as $key => $val) $parts[$key] = str_pad($val, 64, "0", STR_PAD_RIGHT);
        return $hexlen . implode("", $parts);
    }
    
    protected function _addressToHash160($address)
    {
        return bin2hex($this->bitcoinECDSA->addressToHash160($address));
    }
}
