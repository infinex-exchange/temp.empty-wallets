<?php

class EmptyWallets {
    private $log;
    private $amqp;
    private $pdo;
    
    function __construct($log, $amqp, $pdo) {
        $this -> log = $log;
        $this -> amqp = $amqp;
        $this -> pdo = $pdo;
        
        $this -> log -> debug('Initialized empty wallets worker');
    }
    
    public function start() {
        $th = $this;
        
        return $this -> amqp -> sub(
            'mail',
            function($body) use($th) {
                return $th -> newMail($body);
            }
        ) -> then(
            function() use($th) {
                $th -> log -> info('Started mail queue consumer');
            }
        ) -> catch(
            function($e) use($th) {
                $th -> log -> error('Failed to start mail queue consumer: '.((string) $e));
                throw $e;
            }
        );
    }
    
    public function bind($amqp) {
        $th = $this;
        
        $amqp -> sub(
            'registerUser',
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