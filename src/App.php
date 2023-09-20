<?php

require __DIR__.'/EmptyWallets.php';

class App extends Infinex\App\Daemon {
    private $pdo;
    private $ew;
    
    function __construct() {
        parent::__construct('temp.empty-wallets');
        
        $this -> pdo = new Infinex\Database\PDO($this -> loop, $this -> log);
        $this -> pdo -> start();
        
        $this -> ew = new EmptyWallets($this -> log, $this -> pdo);
        
        $th = $this;
        $this -> amqp -> on('connect', function() use($th) {
            $th -> ew -> bind($th -> amqp);
        });
    }
}

?>