<?php
header('Content-type: application/json;');

class TransactionHandler {
    public function handleTransaction($hash) {
        if (empty($hash)) {
            $this->respondError(400, 'تمامی پارامترهای اجباری وارد نشده اند.');
        }
        
        $str = str_replace(['https://www.tronscan.org/#/transaction/','https://tronscan.org/#/transaction/','tronscan.org/#/transaction/','?lang=en'],['','','',''],$hash);
        $transaction = json_decode(file_get_contents('https://apilist.tronscanapi.com/api/transaction-info?hash='.$str), true);
        $contractRet = $transaction['contractRet'];
        $timestamp = $transaction['timestamp']/1000;
        
        if($contractRet == NULL){
            $this->respondError(455, 'هش تراکنش اشتباه میباشد');
        }

        if($transaction['contractData']['to_address'] == NULL){
            $result = [
                'ContractRet' => $contractRet,
                'Amount' => $this->con($transaction['trc20TransferInfo'][0]['amount_str']/pow(10, $transaction['trc20TransferInfo'][0]['decimals'])),
                'From' => $transaction['contractData']['owner_address'],
                'To' => $transaction['trc20TransferInfo'][0]['to_address'],
                'Date' => date('Y/m/d', $timestamp),
                'Time' => date('H:i:s', $timestamp),
                'hash' => $str,
                'TokenInfo' => [
                    'tokenType' => 'TRC20',
                    'contract_address' => $transaction['contractData']['contract_address'],
                    'icon_url' => $transaction['trc20TransferInfo'][0]['icon_url'],
                    'symbol' => $transaction['trc20TransferInfo'][0]['symbol'],
                    'name' => $transaction['trc20TransferInfo'][0]['name'],
                    'decimals' => $transaction['trc20TransferInfo'][0]['decimals'],
                ],
            ];
            $this->respondSuccess(200, $result);
        }

        if($transaction['contractData']['asset_name'] == NULL){
            $result = [
                'ContractRet' => $contractRet,
                'Amount' => $this->con($transaction['contractData']['amount']/1000000),
                'From' => $transaction['contractData']['owner_address'],
                'To' => $transaction['contractData']['to_address'],
                'Date' => date('Y/m/d', $timestamp),
                'Time' => date('H:i:s', $timestamp),
                'hash' => $str,
                'TokenInfo' => [
                    'tokenType' => 'COIN',
                    'icon_url' => 'https://static.tronscan.org/production/logo/trx.png',
                    'symbol' => 'TRX',
                    'name' => 'TRON',
                    'decimals' => 6,
                ],
            ];
            $this->respondSuccess(200, $result);
        } else {
            $result = [
                'ContractRet' => $contractRet,
                'Amount' => $this->con($transaction['contractData']['amount']/pow(10, $transaction['contractData']['tokenInfo']['tokenDecimal'])),
                'From' => $transaction['contractData']['owner_address'],
                'To' => $transaction['contractData']['to_address'],
                'Date' => date('Y/m/d', $timestamp),
                'Time' => date('H:i:s', $timestamp),
                'hash' => $str,
                'TokenInfo' => [
                    'tokenType' => 'TRC10',
                    'tokenId' => $transaction['contractData']['tokenInfo']['tokenId'],
                    'icon_url' => $transaction['contractData']['tokenInfo']['tokenLogo'],
                    'symbol' => $transaction['contractData']['tokenInfo']['tokenAbbr'],
                    'name' => $transaction['contractData']['tokenInfo']['tokenName'],
                    'decimals' => $transaction['contractData']['tokenInfo']['tokenDecimal'],
                ],
            ];
            $this->respondSuccess(200, $result);
        }
    }

    private function respondSuccess($status, $result) {
        echo json_encode(['status' => $status, 'dev' => ajcode, 'result' => $result], 448);
        exit();
    }

    private function respondError($status, $message) {
        echo json_encode(['status' => $status, 'dev' => ajcode, 'message' => $message], 448);
        exit();
    }
    
    private function con($value) {
        if (strpos($value, 'E') !== false) {
            $nn = $value;
            $p = explode("E-", $nn);
            $ss = $p[1] + 1;
            $sss = "$ss"."f";
            $value = sprintf("%.$sss", $value);
        }
        return $value;
    }
}

$handler = new TransactionHandler();
$handler->handleTransaction($_GET['hash']);
