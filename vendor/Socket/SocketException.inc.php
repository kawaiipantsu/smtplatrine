<?PHP
namespace Socket;

class SocketException extends \Exception {
    const CANT_LISTEN = 1;
    const CANT_BIND = 2;
    const CANT_ACCEPT = 3;
    const CANT_READ = 4;
    const CANT_WRITE = 5;
    const CANT_CONNECT = 6;
    const CANT_SEND = 7;
    const CANT_CLOSE = 8;
    const CANT_FORK = 9;
    const CANT_SET_OPTION = 10;
    const CANT_GET_OPTION = 11;
    const CANT_CREATE_SOCKET = 12;
    const CANT_SET_NONBLOCKING = 13;
    const CANT_SET_BLOCKING = 14;
    const CANT_SET_TIMEOUT = 15;
    const CANT_GET_TIMEOUT = 16;
    const CANT_GET_PEER_NAME = 17;
    const CANT_GET_SOCKET_NAME = 18;
    const CANT_GET_ERROR = 19;
    const CANT_GET_HOST_BY_NAME = 20;
    const CANT_GET_HOST_BY_ADDR = 21;
    const CANT_GET_PROTOCOL_BY_NAME = 22;
    const CANT_GET_SERV_BY_PORT = 23;
    const CANT_GET_SERV_BY_NAME = 24;
    const CANT_GET_PROTOCOL_BY_NUMBER = 25;
    const CANT_GET_ADDR_INFO = 26;
    const CANT_GET_NAME_INFO = 27;
    const CANT_GET_IP = 28;
    const CANT_GET_HOST = 29;
    const CANT_GET_PORT = 30;
    const CANT_GET_PROTOCOL = 31;
    const CANT_GET_SERV = 32;
    const CANT_GET_ADDR = 33;
    const CANT_GET_NAME = 34;
    const CANT_GET_FAMILY = 35;
    const CANT_GET_TYPE = 36;
    const CANT_GET_SOCK = 37;
    const CANT_GET_SOCKOPT = 38;
    const CANT_SET_SOCKOPT = 39;
    const CANT_GET_SOCKOPT_LEVEL = 40;
    const CANT_SET_SOCKOPT_LEVEL = 41;
    const CANT_GET_SOCKOPT_NAME = 42;
    const CANT_SET_SOCKOPT_NAME = 43;
    const CANT_GET_SOCKOPT_VALUE = 44;
    const CANT_SET_SOCKOPT_VALUE = 45;
    const CANT_GET_SOCKOPT_SIZE = 46;

    // Constructor
    public function __construct( $code, $params = false ) {
        // Enable logging
        $logger = new \Controller\Logger();
        if ( $params ) {
            $args = array( $this->messages[ $code ], $params );
            $message = call_user_func_array('sprintf', $args );
        } else {
            $message = $this->messages[ $code ];
        }
        $logger->logErrorMessage( "FATAL ".$message." (".$code.")" );
        parent::__construct( $message, $code );
    }

    // Human readable messages
    public $messages = array(
        self::CANT_CREATE_SOCKET => 'Can\'t create socket: "%s"',
        self::CANT_BIND => 'Can\'t bind socket: "%s"',
        self::CANT_LISTEN => 'Can\'t listen: "%s"',
        self::CANT_ACCEPT => 'Can\'t accept connections: "%s"',
        self::CANT_READ => 'Can\'t read from socket: "%s"',
        self::CANT_WRITE => 'Can\'t write to socket: "%s"'
    );

}