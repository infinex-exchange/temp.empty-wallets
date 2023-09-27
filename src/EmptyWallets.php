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
            'registerUser',
            function($body) use($th) {
                return $th -> registerUser($body);
            },
            'empty_wallets',
            true
        ) -> then(
            function() use($th) {
                $th -> log -> info('Started empty wallets worker');
            }
        ) -> catch(
            function($e) use($th) {
                $th -> log -> error('Failed to start empty wallets worker: '.((string) $e));
                throw $e;
            }
        );
    }
    
    public function stop() {
        $th = $this;
        
        return $this -> amqp -> unsub('empty_wallets') -> then(
            function() use ($th) {
                $th -> log -> info('Stopped empty wallets worker');
            }
        ) -> catch(
            function($e) use($th) {
                $th -> log -> error('Failed to stop empty wallets worker: '.((string) $e));
            }
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