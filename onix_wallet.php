<?php
namespace orcphphelpers;

/**
 * Based on EasyBitcoin-PHP
 * A simple class for making calls to Bitcoin's API using PHP.
 * https://github.com/aceat64/EasyBitcoin-PHP
 *
 * ====================
 * The MIT License (MIT)
 * Copyright (c) 2013 Andrew LeCody
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * ====================
 */
class onix_wallet
{
    // Configuration options
    protected $username;
    protected $password;
    protected $proto;
    protected $host;
    protected $port;
    protected $url;
    protected $CACertificate;
    protected $response_timeout = 10;
    
    // Information and debugging
    public    $status;
    public    $error;
    public    $raw_response;
    public    $response;
    protected $id = 0;
    
    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int    $port
     * @param string $proto
     * @param string $url
     */
    public function __construct($username, $password, $host = 'localhost', $port = 5889, $url = null)
    {
        $this->username   = $username;
        $this->password   = $password;
        $this->host       = $host;
        $this->port       = $port;
        $this->url        = $url;
        
        // Set some defaults
        $this->proto         = 'http';
        $this->CACertificate = null;
    }
    
    /**
     * @param string|null $certificate
     */
    public function setSSL($certificate = null)
    {
        $this->proto         = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }
    
    /**
     * @return number
     */
    public function getbalance()
    {
        return $this->call("getbalance", array());
    }
    
    public function getaddressbalance($address)
    {
        $res = $this->call("getaddressbalance", array($address));
        
        if( $this->error ) return 0;
        
        if( $res["balance"] == 0 ) return 0;
        return round($res["balance"] / 100000000, 8);
    }
    
    /**
     * @return array
     */
    public function getinfo()
    {
        $blockchaininfo = $this->call("getblockchaininfo", array());
        
        if( $this->error ) return $this->error;
        
        unset($blockchaininfo["softforks"]);
        unset($blockchaininfo["bip9_softforks"]);
        
        $networkinfo = $this->call("getnetworkinfo",    array());
        
        if( $this->error ) return $this->error;
        
        unset($networkinfo["networks"]);
        unset($networkinfo["localaddresses"]);
        
        $walletinfo = $this->call("getwalletinfo",     array());
        
        if( $this->error ) return $this->error;
        
        return array_merge($blockchaininfo, $networkinfo, $walletinfo);
    }
    
    /**
     * @return array
     */
    public function getpeerinfo()
    {
        return $this->call("getpeerinfo", array());
    }
    
    /**
     * @return string Y-m-d H:i:s
     */
    public function getlastsynctime()
    {
        $value = "";
        $peers = $this->getpeerinfo();
        if( empty($peers) ) return "";
        
        foreach($peers as $peer)
            if($peer["lastrecv"] > $value)
                $value = $peer["lastrecv"];
        
        if( ! empty($value) ) $value = date("Y-m-d H:i:s", $value);
        
        return $value;
    }
    
    /**
     * @return string
     */
    public function getnewaddress()
    {
        return $this->call("getnewaddress", array());
    }
    
    /**
     * @param string $block_hash
     *
     * @return array [transactions:array, lastblock:string]
     */
    public function listsinceblock($block_hash = "")
    {
        if( empty($block_hash) )
            return $this->call("listsinceblock", array());
        
        return $this->call("listsinceblock", array($block_hash));
    }
    
    /**
     * @param string $label
     * @param int    $limit
     * @param int    $offset
     * 
     * @return array
     */
    public function listtransactions($label = "*", $limit = 10, $offset = 0)
    {
        return $this->call("listtransactions", array($label, $limit, $offset));
    }
    
    /**
     * @return int
     */
    public function getblockcount()
    {
        return $this->call("getblockcount", array());
    }
    
    /**
     * @return array
     */
    public function gettransaction($txid)
    {
        return $this->call("gettransaction", array($txid));
    }
    
    /**
     * @param $address
     *
     * @return array
     */
    public function validateaddress($address)
    {
        $res1 = $this->call("validateaddress", array($address));
        if( empty($res1) ) return array("isvalid" => false);
        
        $res2 = $this->call("getaddressinfo", array($address));
        if( empty($res2) ) return array("isvalid" => false);
        
        $res1["ismine"] = $res2["ismine"];
        
        return $res1;
    }
    
    /**
     * @param number $fee
     *
     * @return bool
     */
    public function settxfee($fee)
    {
        return $this->call("settxfee", array($fee + 0));
    }
    
    /**
     * @param string $address
     * @param number $amount
     * @param bool   $substractfeefromamount
     * 
     * @return string TXID
     */
    public function sendtoaddress($address, $amount, $substractfeefromamount = true)
    {
        if( $substractfeefromamount )
        {
            $data = array(
                $address,        # address
                $amount + 0,     # amount
                "",              # comment
                "",              # comment_to
                true,            # substractfeefromamount
            #   true,            # replaceable
            #   10,              # conf_target
            #   "CONSERVATIVE",  # estimate_mode
            #   false,           # avoid_reuse
            #   "",              # senderaddress
            #   true             # changeToSender
            );
        }
        else
        {
            $data = array(
                $address,        # address
                $amount + 0,     # amount
            #   "",              # comment
            #   "",              # comment_to
            #   true,            # substractfeefromamount
            #   true,            # replaceable
            #   10,              # conf_target
            #   "CONSERVATIVE",  # estimate_mode
            #   false,           # avoid_reuse
            #   "",              # senderaddress
            #   true             # changeToSender
            );
        }
        
        return $this->call("sendtoaddress", $data);
    }
    
    /**
     * @param $list = [
     *     ["address" => 1.5],
     *     ["address" => 1.4],
     *     ["address" => 1.8],
     * ]
     * 
     * @return string
     */
    public function sendmany($list)
    {
        return $this->call("sendmany", array("", (object) $list));
    }
    
    public function keypoolrefill()
    {
        return $this->call("keypoolrefill", array());
    }
    
    /**
     * @param number $index
     *
     * @return string
     */
    public function getblockhash($index)
    {
        return $this->call("getblockhash", array($index));
    }
    
    /**
     * Moves received coins to the system deposit address.
     * By default it does nothing.
     * Extended by any coin that requires this before notifying the user.
     *
     * @param string $from_addr
     * @param string $to_addr
     * @param number $amount
     * 
     * @return string
     * 
     * @noinspection PhpUnusedParameterInspection
     */
    public function move($from_addr, $to_addr, $amount)
    {
        $this->status       = 200;
        $this->error        = "";
        $this->raw_response = "true";
        $this->response     = true;
        
        return "";
    }
    
    /**
     * @param string $hash
     *
     * @return array
     */
    public function getblock($hash)
    {
        return $this->call("getblock", array($hash));
    }
    
    /**
     * @param string $passphrase
     * @param number $unlock_seconds
     *
     * @return mixed
     */
    public function walletpassphrase($passphrase, $unlock_seconds)
    {
        return $this->call("walletpassphrase", array($passphrase, $unlock_seconds));
    }
    
    
    
    /**
     * @param string $address
     * @param string $hexdata
     * @param string $sender_address
     * @param number $gas_limit
     * @param number $onix_to_send
     * 
     * @return false|mixed
     */
    public function call_contract($address, $hexdata, $sender_address = "", $gas_limit = 0, $onix_to_send = 0)
    {
        $params = array($address, $hexdata);
        if( ! empty($sender_address) ) $params[] = $sender_address;
        if( ! empty($gas_limit) )      $params[] = $gas_limit + 0;
        if( ! empty($onix_to_send) )   $params[] = $onix_to_send;
        
        return $this->call("callcontract", $params);
    }
    
    /**
     * @param string $address
     * @param string $hexdata
     * @param number $onix_to_send
     * @param number $gas_limit
     * @param number $gas_price
     * @param string $sender_addr
     * @param bool   $broadcast
     * @param bool   $change_to_sender
     * 
     * @return false|mixed
     */
    public function send_to_contract(
        $address, 
        $hexdata, 
        $onix_to_send = 0, 
        $gas_limit = 0, 
        $gas_price = 0, 
        $sender_addr = "", 
        $broadcast = true, 
        $change_to_sender = true
    ) {
        $params = array($address, $hexdata, $onix_to_send);
        if( ! empty($gas_limit) )    $params[] = $gas_limit + 0;
        if( ! empty($gas_price) )    $params[] = $gas_price + 0;
        if( ! empty($sender_addr) )  $params[] = $sender_addr;
        if( $broadcast )             $params[] = $broadcast;
        if( $change_to_sender )      $params[] = $change_to_sender;
        
        return $this->call("sendtocontract", $params);
    }
    
    public function fromhexaddress($hexaddress)
    {
        return $this->call("fromhexaddress", array($hexaddress) );
    }
    
    
    
    protected function call($method, $params)
    {
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;
        
        // If no parameters are passed, this will be an empty array
        $params = array_values($params);
        
        // The ID should be unique for each call
        $this->id++;
        
        // Build the request, it's ok that params might have any empty array
        $request = json_encode(
            array(
                'method' => $method,
                'params' => $params,
                'id'     => $this->id,
            )
        );
        
        // Build the cURL session
        $curl    = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request,
            CURLOPT_CONNECTTIMEOUT => $this->response_timeout,
        );
        
        if( ini_get('open_basedir') ) unset($options[CURLOPT_FOLLOWLOCATION]);
        
        if( $this->proto == 'https' )
        {
            // If the CA Certificate was specified we change CURL to look for it
            if( ! empty($this->CACertificate) )
            {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            }
            else
            {
                // If not we need to assume the SSL cannot be verified
                // so we set this flag to FALSE to allow the connection
                $options[CURLOPT_SSL_VERIFYPEER] = false;
            }
        }
        curl_setopt_array($curl, $options);
        
        // Execute the request and decode to an array
        $this->raw_response = curl_exec($curl);
        $this->response     = json_decode($this->raw_response, true);
        
        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);
        curl_close($curl);
        if( ! empty($curl_error) )
        {
            $this->error = $curl_error;
        }
        
        if( $this->response['error'] )
        {
            // If bitcoind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        }
        elseif( $this->status != 200 )
        {
            // If bitcoind didn't return a nice error message, we need to make our own
            switch( $this->status )
            {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }
        
        if( $this->error ) return false;
        
        if( is_array($this->response['result']) )
        {
            if( $this->response['result']['executionResult'] )
            {
                if( $this->response['result']['executionResult']["exceptedMessage"] )
                {
                    $this->error = $this->response['result']['executionResult']["exceptedMessage"];
                    return false;
                }
            }
        }
        
        return $this->response['result'];
    }
}
