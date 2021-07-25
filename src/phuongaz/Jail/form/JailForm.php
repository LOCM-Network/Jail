<?php

namespace phuongaz\Jail\form;

use pocketmine\Player;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use phuongaz\Jail\Jail;
use pocketmine\Server;

class JailForm
{
    public function getListJailForm() :SimpleForm
    {
        $list = Jail::getInstance()->getJailList();
        $form = new SimpleForm(function(Player $player, ?int $data) use ($list)
        {
            if(is_null($data)) return;
            $form = $this->getJailForm(array_keys($list)[$data]);
            $player->sendForm($form);
        });
        $form->setTitle("Jail List");
        if(count($list) > 0)
        {
            foreach($list as $name => $time)
            {
                $form->addButton($name . "\nTime left " . round($time, 2) . " min");
            }
        }
        return $form;
    }

    public function getJailForm(string $name) :CustomForm
    {
        $form = new CustomForm(function(Player $player, ?array $data) use ($name)
        {
            if(is_null($data)) return;
            if($data[1])
            {
                $target = Server::getInstance()->getPlayerExact($name);
                if($target !== null)
                {
                    $status = Jail::getInstance()->unJail($target);
                    if($status)
                    {
                        $player->sendMessage("You has been un jail (".$name.")");
                        return;
                    }
                }
                $player->sendMessage("Target has been offline!");
            }
        });
        $form->setTitle($name . " info");
        $form->addLabel("Time left: ". round(Jail::getInstance()->getJailList()[$name], 2). " min");
        $form->addToggle("Un jail " . $name);

        return $form;
    }
}