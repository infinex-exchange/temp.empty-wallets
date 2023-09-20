<?php

use Infinex\AMQP\RPCException;

class EmptyWallets {
    private $log;
    private $pdo;
    
    function __construct($log, $pdo) {
        $this -> log = $log;
        $this -> pdo = $pdo;
        
        $this -> log -> debug('Initialized empty wallets manager');
    }
    
    public function bind($amqp) {
        $th = $this;
        
        $amqp -> sub(
            'register_user',
            function($body) use($th) {
                return $th -> registerUser($body);
            },
            'empty_wallets'
        );
    }
    
    public function registerUser($body) {
        $task = array(
            ':uid' => $body['uid']
        );
        
        $sql = 'INSERT INTO wallet_balances (
            uid,
            assetid,
            total,
            locked
        )
        SELECT :uid,
               assetid,
               0,
               0
        FROM assets';
        
        $q = $this -> pdo -> prepare($sql);
        $q -> execute($task);
        
        $this -> log -> debug('Created empty wallets for uid = '.$body['uid']);
    }
}

?>