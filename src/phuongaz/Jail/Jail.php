<?php

namespace phuongaz\Jail;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Config;
use phuongaz\Jail\command\JailCommand;
use pocketmine\level\Position;

Class Jail extends  PluginBase implements Listener
{
    use SingletonTrait;

    public static array $jail_cached = [];
    private Config $config;

    public function onLoad() :void
    {
        self::setInstance($this);
    }

    public function onEnable() :void
    {
        Server::getInstance()->getCommandMap()->register("LOCMJail", new JailCommand());
        Server::getInstance()->getPluginManager()->registerEvents($this, $this);
        $this->config = new Config($this->getDataFolder()."setting.yml", Config::YAML);
    }

    public function onMove(PlayerMoveEvent $event) :void
    {
        $player = $event->getPlayer();
        if(isset(self::$jail_cached[$player->getLowerCaseName()])){
            $event->setCancelled();
        }
    }

    public function onJoin(PlayerJoinEvent $event) :void
    {
        $player = $event->getPlayer();
        if(isset(self::$jail_cached[$player->getLowerCaseName()]))
        {
            $this->getScheduler()->scheduleRepeatingTask(
                new JailTask($player, self::$jail_cached[$player->getLowerCaseName()]), 20);
        }
    }

    public function setJail(Player $player, int $min) :void
    {
        $lowername = $player->getLowerCaseName();
        if(isset(self::$jail_cached[$lowername])){
            $min += self::$jail_cached[$lowername];
        }
        self::$jail_cached[$lowername] = $min;
        $player->teleport($this->getJailPos());
        $this->getScheduler()->scheduleRepeatingTask(new JailTask($player, $min), 20);
    }

    public function unJail(Player $player) :bool
    {
        if(isset(self::$jail_cached[$player->getLowerCaseName()])){
            unset(self::$jail_cached[$player->getLowerCaseName()]);
            $hub = Server::getInstance()->getDefaultLevel()->getSafeSpawn();
            $player->teleport($hub);
            return true;
        }
        return false;
    }

    public function setJailPos(Player $player) :void
    {
        $pos = $player->asPosition();
        $name = $pos->getLevel()->getName();
        $x = $pos->getX();
        $y = $pos->getY();
        $z = $pos->getZ();
        $this->config->set("jail_position", ["world" => $name, "x" => $x, "y" => $y, "z" => $z]);
        $this->config->save();
    }

    public function getJailPos() :Position
    {
        $db = $this->config->get('jail_position');
        if(!$db)
        {
            return Server::getInstance()->getDefaultLevel()->getSafeSpawn();
        }
        $x = $db["x"];
        $y = $db["y"];
        $z = $db["z"];
        $level = Server::getInstance()->getLevelByName($db["world"]);
        return new Position($x, $y, $z, $level);
    }

    public function getJailList() :array
    {
        return self::$jail_cached;
    }
}

class JailTask extends Task{

    private int $time;
    private Player $player;

    public function __construct(Player $player, int $min){
        $this->player = $player;
        $this->time = $min * 60;
    }

    public function onRun(int $currentTick) :void
    {
        if($this->player !== null)
        {
            if(!isset(Jail::$jail_cached[$this->player->getLowerCaseName()]))
            {
                $this->getHandler()->cancel();
                $this->player->sendPopup("§l§aBạn đã được tự do!");
            }else
            {
                if($this->time == 0)
                {
                    Jail::getInstance()->unJail($this->player);
                    $this->getHandler()->cancel();
                }
                $this->player->sendPopup("§l§fBạn còn§c ".$this->time. " §fgiây để thoát khỏi tù");
                --$this->time;
                if($this->time % 2 == 0) Jail::$jail_cached[$this->player->getLowerCaseName()] = $this->time / 60;
            }
        }else
        {
            $this->getHandler()->cancel();
        }
    }
}