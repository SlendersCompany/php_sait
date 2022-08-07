<?php


namespace yexeed\promocodes;


use onebone\economyapi\EconomyAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Promocodes extends PluginBase
{
    const PREFIX = '§a(§bПромокоды§a)§r ';
    /** @var Config */
    private $config, $users;
    public function onEnable()
    {
        $d = $this->getDataFolder();
        if(!is_dir($d)){
            mkdir($d);
        }
        $this->config = new Config($d . "config.yml", Config::YAML, [
            "promocodes" => [
                'testEmpty' => [
                    'money' => 0,
                    'items' => []
                ],
                'testMoney' => [
                    'money' => 150,
                    'items' => []
                ],
                'testItems' => [
                    'money' => 0,
                    'items' => [
                        ['id' => 1, 'meta' => 0, 'count' => 15, 'name' => 'топ камень', 'lore' => 'описание у предмета'],
                        ['id' => 2, 'meta' => 0, 'count' => 32, 'name' => 'топ трава', 'lore' => 'описание у предмета']
                    ]
                ]
            ]
        ]);

        $this->users = new Config($d . "users.json", Config::JSON);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if(!$sender instanceof Player){
            $sender->sendMessage(self::PREFIX ."§cЗайдите в игру");
            return true;
        }
        if(mb_strtolower($command->getName()) === "promo"){
            if(!isset($args[0]) or empty(trim(implode(" ", $args)))){
                $sender->sendMessage(self::PREFIX . "§eИспользование: §f/promo <промокод> ,в промокодах может содержаться : Ресурсы или деньги , проверить свой баланс /money");
                return true;
            }
            $n = mb_strtolower($sender->getName());
            $promoCode = trim(implode(" ", $args));
            if($this->config->getNested("promocodes.$promoCode", null) === null){
                $sender->sendMessage(self::PREFIX . "§eПромокод §f'$promoCode' §cне найден");
                return true;
            }
            if($this->users->exists($n) && in_array($promoCode, $this->users->get($n))){
                $sender->sendMessage(self::PREFIX . "§eВы уже ранее активировали этот промокод");
                return true;
            }
            $data = $this->config->getNested("promocodes.$promoCode");
            if($data['money'] > 0){
                EconomyAPI::getInstance()->addMoney($sender, $data['money']);
            }
            if(!empty($data['items'])){
                foreach ($data['items'] as $item){
                    if(!isset($item['id'])){
                        continue;
                    }
                    $itemObj = new Item($item['id'], $item['meta'] ?? 0, $item['count'] ?? 1);
                    if(isset($item['name'])){
                        $itemObj->setCustomName($item['name']);
                    }
                    if(isset($data['lore'])){
                        $lore = $data['lore'];
                        $lore = explode("\n", $lore);
                        $itemObj->setLore($lore);
                    }
                    if(!$sender->getInventory()->canAddItem($itemObj)) {
                        $sender->getLevel()->dropItem($sender, $itemObj);
                    }else {
                        $sender->getInventory()->addItem($itemObj);
                    }
                }
            }
            $old = $this->users->exists($n) ? $this->users->get($n) : [];
            $old[] = $promoCode;
            $this->users->set($n, $old);
            $this->users->save(true);
            $sender->sendMessage(self::PREFIX . "§aВы успешно активировали промокод!");
            return true;
        }
        return false;
    }
}
