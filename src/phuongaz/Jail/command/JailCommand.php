<?php

namespace phuongaz\Jail\command;

use phuongaz\Jail\Jail;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use phuongaz\Jail\form\JailForm;
use pocketmine\Server;

class JailCommand extends Command
{
    public function __construct()
    {
        parent::__construct("jail", "Jail command");
        $this->setPermission('jail.command');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) :bool
    {
        if(!$this->testPermission($sender)) return false;
        if($sender instanceof Player)
        {
            if(isset($args[0])){
                if($args[0] == 'list')
                {
                    $form = new JailForm();
                    $form = $form->getListJailForm();
                    $sender->sendForm($form);
                    return true;
                }
                if($args[0] == 'set')
                {
                    Jail::getInstance()->setJailPos($sender);
                    return true;
                }
                if(isset($args[1])){
                    $target = Server::getInstance()->getPlayerExact($args[0]);
                    if($target !== null)
                    {
                        if(!is_numeric($args[1])){
                            $sender->sendMessage("Time is integer");
                            return false;
                        }
                        Jail::getInstance()->setJail($target, $args[1]);
                        $sender->sendMessage("Jail successfully");
                        return true;
                    }
                    $sender->sendMessage("Player not found!");
                    return false;
                }
            }
            $sender->sendMessage("/jail list|set <player> <min>");
        }
        return true;
    }
}