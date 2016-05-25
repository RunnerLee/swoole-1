<?php
/**
 * Created by PhpStorm.
 * User: janhuang
 * Date: 16/5/19
 * Time: 上午1:22
 * Github: https://www.github.com/janhuang
 * Coding: https://www.coding.net/janhuang
 * SegmentFault: http://segmentfault.com/u/janhuang
 * Blog: http://segmentfault.com/blog/janhuang
 * Gmail: bboyjanhuang@gmail.com
 * WebSite: http://www.janhuang.me
 */

namespace FastD\Swoole\Server\Manage;

use FastD\Packet\Packet;
use FastD\Packet\PacketException;
use FastD\Swoole\Server\Server;
use FastD\Swoole\SwooleInterface;

/**
 * Class Listener
 *
 * @package FastD\Swoole\Server\Listen
 */
class Listener
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var int|string
     */
    protected $port;

    /**
     * @var int
     */
    protected $mode;

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var \swoole_server_port
     */
    protected $server_port;

    /**
     * Listener constructor.
     * @param $host
     * @param $port
     * @param int $mode
     */
    public function __construct($host, $port, $mode = SwooleInterface::SWOOLE_SOCK_UDP)
    {
        $this->host = $host;

        $this->port = $port;

        $this->mode = $mode;
    }

    /**
     * @return \swoole_server_port
     */
    public function getServerPort()
    {
        return $this->server_port;
    }

    /**
     * @param Server $server
     */
    public function setServer(Server $server)
    {
        $this->server = $server;

        $this->server_port = $this->server->getServer()->listen($this->host, $this->port, $this->mode);

        $this->server_port->on('receive', [$this, 'onReceive']);
    }

    /**
     * @param \swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @param string $data
     * @return void
     */
    public function onReceive(\swoole_server $server, int $fd, int $from_id, string $data)
    {
        try {
            $action = $this->getAction($data);
        } catch (PacketException $e) {
            $action = 'status';
        }

        switch ($action) {
            case 'stop':
                $server->send($fd, Packet::encode([
                    'msg' => sprintf('Server[%s] is shutdown...', $this->server->getPid())
                ]), $from_id);
                $this->server->getServer()->shutdown();
                break;
            case 'reload':
                $server->send($fd, Packet::encode([
                    'msg' => sprintf('Server[%s] is reloading...', $this->server->getPid())
                ]), $from_id);
                $this->server->getServer()->reload();
                break;
            case 'status':
            default:
                $server->send($fd, Packet::encode([
                    'state' => $this->server->getServer()->stats(),
                    'connections' => $this->server->getServer()->connections,
                ]), $from_id);
        }
    }

    /**
     * @param $data
     * @return mixed
     * @throws \FastD\Packet\PacketException
     */
    public function getAction($data)
    {
        $data = Packet::decode($data);

        return $data;
    }
}