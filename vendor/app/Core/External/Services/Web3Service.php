<?php

namespace App\Core\External\Services;

use Carbon\Carbon;
use App\Core\Base\Services\HttpService;
use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils as Web3Utils;
use Web3p\EthereumTx\Transaction;
use phpseclib\Math\BigInteger;

class Web3Service 
{
    protected $web3;
    public $converter;

    public function __construct()
    {
        $this->web3 = new Web3(new HttpProvider(new HttpRequestManager(config('services.ethereum.url'), 60)));
        $this->converter = new \Bezhanov\Ethereum\Converter();
    }

    /**
     * Create eth address
     * 
     * @param string $passphrase
     */
    public function createEthAddress($passphrase = null)
    {
        $path = config('services.ethereum.create_address_script');
        return json_decode(exec($path.' '.$passphrase));
    }

    /**
     * Get balance amount
     * 
     * @param string $address
     * @return string
     */
    public function getBalance($address)
    {
        $balance = '';
        $this->web3->eth->getBalance($address, function ($err, $result) use (&$balance) {
            if (isset($err)) {
                throw new \Exception($err->getMessage());
            }
            $balance = $result->toString();
        });
        return $balance;
    }

    /**
     * Get Transaction count
     * 
     * @param string $address
     * @return string
     */
    public function getTransactionCount($address)
    {
        $count = 0;
        $this->web3->eth->getTransactionCount($address, function ($err, $result) use (&$count) {
            if (isset($err)) {
                throw new \Exception($err->getMessage());
            }
            $count = $result->toString();
        });
        return $count;
    }

    /**
     * Calculate Gas Price
     * 
     * @param string $transferBalance
     * @param string $gasPriceForEtherInGwei
     * @return string
     */
    public function calculateGasPrice($transferBalance, $gasPriceForEtherInGwei)
    {
        if (empty($gasPriceForEtherInGwei)) return '2000000000';
        $balanceInEther = $this->converter->fromWei($transferBalance, 'ether');
        $gasPriceInGwei = bcmul($balanceInEther, $gasPriceForEtherInGwei, 6);
        if (bccomp($gasPriceInGwei, company()->EthereumGasMinimum, 6) < 0) $gasPriceInGwei = company()->EthereumGasMinimum; //20181002 WS Gasminimum
        return $this->converter->toWei($gasPriceInGwei, 'gwei');
    }

    /**
     * Get Transaction count
     * 
     * @param array $options
     */
    public function sendTransaction($options)
    {
        logger("Web3Service - sendTransaction".PHP_EOL, [ 'params' => $options ]);
        $count = $this->getTransactionCount($options['from']);
        $amount = Web3Utils::toHex($options['amount'], true);
        $tx = new Transaction([
            'nonce' => Web3Utils::toHex($count, true),
            'from' => strtolower($options['from']),
            'to' => strtolower($options['to']),
            'gasLimit' => Web3Utils::toHex($options['gasLimit'] ?? '21000', true),
            'gasPrice' =>  Web3Utils::toHex($options['gasPrice'] ?? '2000000000', true), // 10 Gwei
            'value' => $amount,
            'chainId' => config('services.ethereum.chain_id')
        ]);

        $signedTx = $tx->sign($options['privateKey']);
        $serializedTx = $tx->serialize()->toString('hex');
        
        $result;

        $this->web3->eth->sendRawTransaction('0x'.$serializedTx, function ($err, $res) use (&$result) {
            if (isset($err)) {
                throw new \Exception($err->getMessage());
            }
            $result = $res;
        });
        return $result;
    }
}