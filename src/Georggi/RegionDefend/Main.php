<?php
namespace Georggi\RegionDefend;

use pocketmine\math\Vector3; 
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\IPlayer;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\inventory\BaseInventory;
use pocketmine\Server;

class Main extends PluginBase implements Listener {
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        if(!file_exists($this->getDataFolder() . "regions.dat")) {
            @mkdir($this->getDataFolder());
            file_put_contents($this->getDataFolder() . "regions.dat",yaml_emit(array()));
        }
        if(!file_exists($this->getDataFolder() . "config.yml")) {
            @mkdir($this->getDataFolder());
            file_put_contents($this->getDataFolder() . "config.yml",$this->getResource("config.yml"));
        }
        $this->regions = array();
        $this->regiondata = yaml_parse(file_get_contents($this->getDataFolder() . "regions.dat"));
        foreach($this->regiondata as $data) {
            $region = new region($data,$this);
        }
    } 
    public function onCommand(CommandSender $p,Command $cmd,$label,array $args) {
        if(!($p instanceof Player)) {
            $p->sendMessage(Color::RED . "Command must be used in-game.");
            return true;
        }
        if(!isset($args[0])) {
            return false;
        }
        $n = strtolower($p->getName());
        $action = strtolower($args[0]);
        switch($action) {
            case "pos1":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.pos1")) {
                    $this->pos1[$n] = new Vector3(round($p->getX()),round($p->getY()),round($p->getZ()));
                    $o = "Position 1 set to: (" . $this->pos1[$n]->getX() . "," . $this->pos1[$n]->getY() . "," . $this->pos1[$n]->getZ() . ")";
                } else {
                    $o = "You do not have permission to use this subcommand.";
                }
                break;
            case "pos2":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.pos2")) {
                    $this->pos2[$n] = new Vector3(round($p->getX()),round($p->getY()),round($p->getZ()));
                    $o = "Position 2 set to: (" . $this->pos2[$n]->getX() . "," . $this->pos2[$n]->getY() . "," . $this->pos2[$n]->getZ() . ")";
                } else {
                    $o = "You do not have permission to use this subcommand.";
                }
                break;
            case "claim":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.create")) {
                    if(isset($args[1])) {
                        if(isset($this->pos1[$n]) && isset($this->pos2[$n])) {
                            if(!isset($this->regions[strtolower($args[1])])) {
                                if($p->hasPermission("regiondefend.regionsize.150000")){
                                    $MaxRegionSize = 150000;    
                                }else if($p->hasPermission("regiondefend.regionsize.100000")){
                                    $MaxRegionSize = 100000;    
                                }else if($p->hasPermission("regiondefend.regionsize.50000")){
                                    $MaxRegionSize = 50000;    
                                }else if($p->hasPermission("regiondefend.regionsize.30000")){
                                    $MaxRegionSize = 30000;    
                                }else{
                                    $MaxRegionSize = 30000;
                                }
                                if($p->hasPermission("regiondefend.regionnumber.10")){
                                    $MaxRegionNumber = 10;    
                                }else if($p->hasPermission("regiondefend.regionnumber.7")){
                                    $MaxRegionNumber = 7;    
                                }else if($p->hasPermission("regiondefend.regionnumber.4")){
                                    $MaxRegionNumber = 4;    
                                }else if($p->hasPermission("regiondefend.regionnumber.2")){
                                    $MaxRegionNumber = 2;    
                                }else{
                                    $MaxRegionNumber = 2;
                                }
                                $number = 0;
                                foreach($this->regions as $region){
                                    $owners = $region->getOwners();
                                    if(in_array($p->getName(), $owners)){
                                        $number = $number+1;
                                    }
                                }
                                $distance = $this->pos1[$n]->distanceSquared($this->pos2[$n]);
                                $Intersection;
                                if(($MaxRegionSize > $distance && $MaxRegionNumber > $number) || $p-> hasPermission("regiondefend.regionsize.infinity")){
                                    if($this->pos1[$n]->getX() >= $this->pos2[$n]->getX()){
                                        for($CoordX = $this->pos1[$n]->getX(); $CoordX <= $this->pos2[$n]->getX(); $CoordX++){
                                            if($this->pos1[$n]->getY() >= $this->pos2[$n]->getY()){
                                                for($CoordY = $this->pos1[$n]->getY(); $CoordY <= $this->pos2[$n]->getY(); $CoordY++){
                                                    if($this->pos1[$n]->getZ() >= $this->pos2[$n]->getZ()){
                                                        for($CoordZ = $this->pos1[$n]->getZ(); $CoordZ <= $this->pos2[$n]->getZ(); $CoordZ++){
                                                            $NRPos = new Vector3($CoordX, $CoordY, $CoordZ);
                                                            foreach($this->regions as $region){
                                                                if($region->contains($NRPos)){
                                                                    $Intersection = true;
                                                                    break(4);
                                                                }else{
                                                                    $Intersection = false;
                                                                    break(4);
                                                                }
                                                            }    
                                                        }
                                                    }else if($this->pos2[$n]->getZ() >= $this->pos1[$n]->getZ()){
                                                        for($CoordZ = $this->pos2[$n]->getZ(); $CoordZ <= $this->pos1[$n]->getZ(); $CoordZ++){
                                                            $NRPos = new Vector3($CoordX, $CoordY, $CoordZ);
                                                            foreach($this->regions as $region){
                                                                if($region->contains($NRPos)){
                                                                    $Intersection = true;
                                                                    break(4);
                                                                }else{
                                                                    $Intersection = false;
                                                                    break(4);
                                                                }
                                                            }    
                                                        } 
                                                    }
                                                }
                                            }else if($this->pos2[$n]->getY() > $this->pos1[$n]->getY()){
                                                for($CoordY = $this->pos2[$n]->getY(); $CoordY <= $this->pos1[$n]->getY(); $CoordY++){
                                                    if($this->pos1[$n]->getZ() >= $this->pos2[$n]->getZ()){
                                                        for($CoordZ = $this->pos1[$n]->getZ(); $CoordZ <= $this->pos2[$n]->getZ(); $CoordZ++){
                                                            $NRPos = new Vector3($CoordX, $CoordY, $CoordZ);
                                                            foreach($this->regions as $region){
                                                                if($region->contains($NRPos)){
                                                                    $Intersection = true;
                                                                    break(4);
                                                                }else{
                                                                    $Intersection = false;
                                                                    break(4);
                                                                }
                                                            }    
                                                        }
                                                    }else if($this->pos2[$n]->getZ() >= $this->pos1[$n]->getZ()){
                                                        for($CoordZ = $this->pos2[$n]->getZ(); $CoordZ <= $this->pos1[$n]->getZ(); $CoordZ++){
                                                            $NRPos = new Vector3($CoordX, $CoordY, $CoordZ);
                                                            foreach($this->regions as $region){
                                                                if($region->contains($NRPos)){
                                                                    $Intersection = true;
                                                                    break(4);
                                                                }else{
                                                                    $Intersection = false;
                                                                    break(4);
                                                                }
                                                            }    
                                                        } 
                                                    }
                                                }
                                            }
                                        }
                                    }else if($this->pos2[$n]->getX() > $this->pos1[$n]->getX()){
                                        for($CoordX = $this->pos2[$n]->getX(); $CoordX <= $this->pos1[$n]->getX(); $CoordX++){
                                            if($this->pos1[$n]->getY() >= $this->pos2[$n]->getY()){
                                                for($CoordY = $this->pos1[$n]->getY(); $CoordY <= $this->pos2[$n]->getY(); $CoordY++){
                                                    if($this->pos1[$n]->getZ() >= $this->pos2[$n]->getZ()){
                                                        for($CoordZ = $this->pos1[$n]->getZ(); $CoordZ <= $this->pos2[$n]->getZ(); $CoordZ++){
                                                            $NRPos = new Vector3($CoordX, $CoordY, $CoordZ);
                                                            foreach($this->regions as $region){
                                                                if($region->contains($NRPos)){
                                                                    $Intersection = true;
                                                                    break(4);
                                                                }else{
                                                                    $Intersection = false;
                                                                    break(4);
                                                                }
                                                            }    
                                                        }
                                                    }else if($this->pos2[$n]->getZ() >= $this->pos1[$n]->getZ()){
                                                        for($CoordZ = $this->pos2[$n]->getZ(); $CoordZ <= $this->pos1[$n]->getZ(); $CoordZ++){
                                                            $NRPos = new Vector3($CoordX, $CoordY, $CoordZ);
                                                            foreach($this->regions as $region){
                                                                if($region->contains($NRPos)){
                                                                    $Intersection = true;
                                                                    break(4);
                                                                }else{
                                                                    $Intersection = false;
                                                                    break(4);
                                                                }
                                                            }    
                                                        } 
                                                    }
                                                }
                                            }else if($this->pos2[$n]->getY() > $this->pos1[$n]->getY()){
                                                for($CoordY = $this->pos2[$n]->getY(); $CoordY <= $this->pos1[$n]->getY(); $CoordY++){
                                                    if($this->pos1[$n]->getZ() >= $this->pos2[$n]->getZ()){
                                                        for($CoordZ = $this->pos1[$n]->getZ(); $CoordZ <= $this->pos2[$n]->getZ(); $CoordZ++){
                                                            $NRPos = new Vector3($CoordX, $CoordY, $CoordZ);
                                                            foreach($this->regions as $region){
                                                                if($region->contains($NRPos)){
                                                                    if(in_array($region->getOwners(), $n)){
                                                                        $Intersection = true;
                                                                        break(4);
                                                                    }else{
                                                                        $Intersection = false;
                                                                        break(4);
                                                                    }   
                                                                }
                                                            }    
                                                        }
                                                    }else if($this->pos2[$n]->getZ() >= $this->pos1[$n]->getZ()){
                                                        for($CoordZ = $this->pos2[$n]->getZ(); $CoordZ <= $this->pos1[$n]->getZ(); $CoordZ++){
                                                            $NRPos = new Vector3($CoordX, $CoordY, $CoordZ);
                                                            foreach($this->regions as $region){
                                                                if($region->contains($NRPos)){
                                                                    $Intersection = true;
                                                                    break(4);
                                                                }else{
                                                                    $Intersection = false;
                                                                    break(4);
                                                                }   
                                                            }    
                                                        } 
                                                    }
                                                }
                                            }    
                                        }
                                    }
                                    if($Intersection === false){
                                        $region = new region(array("owners" => array($p->getName()),"name" => strtolower($args[1]),"flags" => array("edit" => true,"god" => false,"chest" => true), "members" => array($p->getName()),"pos1" => array($this->pos1[$n]->getX(),$this->pos1[$n]->getY(),$this->pos1[$n]->getZ()),"pos2" => array($this->pos2[$n]->getX(),$this->pos2[$n]->getY(),$this->pos2[$n]->getZ())),$this);
                                        $this->saveregions();
                                        unset($this->pos1[$n]);
                                        unset($this->pos2[$n]);
                                        $o = "Region created!";
                                        }else{
                                            $o = "Your region intersects with another region!";
                                        }
                                } else {
                                    if($MaxRegionSize > $distance){
                                        $o = "You can't create that big region.\nYour size $distance. Max size $MaxRegionSize.";
                                    } elseif($MaxRegionNumber <= $number){
                                        $o = "You reached limit for regions.\nYour number of regions $number. Max number $MaxRegionNumber.";
                                    } else {
                                        $o = "An error occured, try to contact administrator please.";
                                    }
                                }
                            } else {
                                $o = "An region with that name already exists.";
                            }
                        } else {
                            $o = "Please select both positions first.";
                        }
                    } else {
                        $o = "Please specify a name for this region.";
                    }
                } else {
                    $o = "You do not have permission to use this subcommand.";
                }
                break;
            case "list":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.list")) {
                    $o = "regions:";
                    foreach($this->regions as $region) {
                        $o = $o . " " . $region->getName() . ";";
                    }
                }
                break;
            case "flag":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.delete")) {
                    if(isset($args[1])) {
                        if(isset($this->regions[strtolower($args[1])])){
                            $nickname = $p->getName();
                            $region = $this->regions[strtolower($args[1])];
                            if(in_array($nickname, $region->getOwners()) || ($p->hasPermission("regiondefend.edit.others") ) ){   
                                if(isset($args[2])) {
                                    if(isset($region->flags[strtolower($args[2])])) {
                                        $flag = strtolower($args[2]);
                                        if(isset($args[3]) && strtolower($args[3]) == ("on" || "off" || "true" || "false")) {
                                            $mode = strtolower($args[3]);
                                            if($mode == ("true" || "on")) {
                                                $mode = true;
                                            } else {
                                                $mode = false;
                                            }
                                            $region->setFlag($flag,$mode);
                                        } else {
                                            $region->toggleFlag($flag);
                                        }
                                        if($region->getFlag($flag)) {
                                            $status = "on";
                                        } else {
                                            $status = "off";
                                        }
                                        $o = "Flag " . $flag . " set to " . $status . " for region " . $region->getName() . "!";
                                    } else {
                                        $o = "Flag not found. (Flags: edit, god, chest)";
                                    }
                                } else {
                                    $o = "Please specify a flag. (Flags: edit, god, chest)";
                                }
                            } else {
                                $o = "Region doesn't exist.";
                            }
                        } else {
                            $o = "Please specify the region you would like to flag.";
                        }
                    } else {
                        $o = "You do not have permission to use this subcommand.";
                    }
                }
                break;
            case "delete":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.delete")) {
                    if(isset($args[1])) {
                        if(isset($this->regions[strtolower($args[1])])) {
                            $nickname = $p->getName();
                            $region = $this->regions[strtolower($args[1])];
                            if(in_array($nickname, $region->getOwners()) || ($p->hasPermission("regiondefend.edit.others"))){
                                $region->delete();
                                $o = "Region deleted!";
                            } else { 
                                $o = "You can't edit this region";
                            }
                        } else {
                            $o = "Region does not exist.";
                        }
                    } else {
                        $o = "Please specify an region to delete.";
                    }
                } else {
                    $o = "You do not have permission to use this subcommand.";
                }
                break;
            case "addmember":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.addmember")) {
                    if(isset($args[1])) {
                        if(isset($this->regions[strtolower($args[1])])) {
                        $region = $this->regions[strtolower($args[1])];
                        $nickname = $p->GetName();
                        $Owners = $region->getOwners();
                            if(in_array($nickname, $Owners) || ($p->hasPermission("regiondefend.edit.others"))){
                                if(isset($args[2])) {
                                    $NewMember = $args[2];
                                    $region->addMember($NewMember);
                                    $o = "You have successfully added $NewMember as a member. ";
                                } else {
                                    $o = "Please enter a nickname.";
                                }
                            } else {
                                 $o = "You are not owner of this region.";
                            }
                        } else {
                            $o = "Region doesn't exist.";
                        }
                    } else {
                        $o = "Please specify region you would like to add member.";
                    }
                } else {
                    $o = "You do not have permission to use this subcommand.";
                }
                break;
            case "addowner":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.addowner")) {
                    if(isset($args[1])) {
                        if(isset($this->regions[strtolower($args[1])])) {
                            $region = $this->regions[strtolower($args[1])];
                            $nickname = $p->GetName();
                            if(in_array($nickname, $region->getOwners()) || ($p->hasPermission("regiondefend.edit.others"))){
                                if(isset($args[2])) {
                                    $NewOwner = $args[2];
                                    $region->addOwner($NewOwner);
                                    $o = "You have successfully added $NewOwner as a owner. ";
                                } else {
                                    $o = "Please enter a nickname.";
                                }
                            } else {
                                $o = "You are not owner of this region.";
                            }
                        } else {
                            $o = "Region doesn't exist.";
                        } 
                    } else {
                        $o = "Please specify region you would like to add member.";
                    }
                } else {
                    $o = "You do not have permission to use this subcommand.";
                }
                break;
            case"removeowner":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.addowner")) {
                    if(isset($args[1])) {
                        if(isset($this->regions[strtolower($args[1])])) {
                            $region = $this->regions[strtolower($args[1])];
                            $nickname = $p->GetName();
                            if(in_array($nickname, $region->getOwners()) || ($p->hasPermission("regiondefend.edit.others"))){
                                if(isset($args[2])){
                                    $Owner_To_Remove = $args[2];
                                    $Owners = $region->getOwners();
                                    if(in_array($Owner_To_Remove, $Owners)){
                                        $region->removeOwner($Owner_To_Remove);
                                        $o = "Player $Owner_To_Remove removed from owners succesfully.";
                                    } else {
                                        $o = "Player is not an owner of this region.";
                                    }
                                } else {
                                    $o = "Specify a player to remove";
                                }
                            } else {
                                $o = "You are not owner of this region.";
                            }
                        } else {
                            $o = "Region doesn't exist.";
                        }
                    } else {
                        $o = "Please specify a region to remove owner in.";
                    }
                } else {
                    $o = "You don't have permission to use this subcommand.";
                }
                break;
            case"removemember":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.addowner")){
                    if(isset($args[1])) {
                        if(isset($this->regions[strtolower($args[1])])) {
                            $region = $this->regions[strtolower($args[1])];
                            $nickname = $p->GetName();
                            if(in_array($nickname, $region->getOwners()) || ($p->hasPermission("regiondefend.edit.others"))){
                                if(isset($args[2])){
                                    $Member_To_Remove = $args[2];
                                    $Members = $region->getMembers();
                                    if(in_array($Member_To_Remove, $Members)){
                                        $region->removeMember($Member_To_Remove);
                                        $o = "Player $Member_To_Remove removed from owners succesfully.";
                                    } else {
                                        $o = "Player is not an member of this region.";
                                    }
                                } else {
                                    $o = "Specify a player to remove";
                                }
                            } else {
                                $o = "You are not owner of this region.";
                            }
                        } else {
                            $o = "Region doesn't exist.";
                        }
                    } else {
                        $o = "Please specify a region to remove owner in.";
                    }
                } else {
                    $o = "You don't have permission to use this subcommand.";
                }
                break;     
            case "info":
                $pos = new Vector3($p->getX(),$p->getY(),$p->getZ());
                if(isset($args[1])){
                    if(isset($this->regions[strtolower($args[1])])){
                        $region = $this->regions[strtolower($args[1])];
                        $members = $region->GetMembers();
                        $owners = $region->GetOwners();
                        $flag_chest = $region->getFlag("chest");
                        $flag_god = $region->getFlag("god");
                        $flag_edit = $region->getFlag("edit");
                        $pos1 = $region->getPos1();
                        $pos2 = $region->getPos2();
                        $name = $region->getName();
                        $o = ("Region name: $name, pos1: $pos1, pos2: $pos2\n Flags: chest = $flag_chest, god = $flag_god, edit = $flag_edit.");
                        $p->sendMessage("Members: ");
                        foreach($members as $member){
                            $p->sendMessage($member);
                        }
                        $p->sendMessage("Owners: ");
                        foreach($owners as $owner){
                            $p->sendMessage($owner);
                        }
                    } else {
                        $o = "Region doesn't exist.";
                    }   
                } else {
                    $o = "Specify a region.";
                }
                break;
           /* case "wand":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.wand")){
                    $wand = ITEM::WOODEN_AXE;
                    $p->getInventory()->AddItem($wand);              
                }
                break;*/
                //Actually crashes the server if I remember correctly
            default:
                return false;
                break;
        }
        $p->sendMessage($o);
        return true;
    }
    /**
    * @param PlayerInteractEvent $event
    *
    * @ignoreCancelled true
    */
    public function onPlayerInteractEvent(PlayerInteractEvent $event) {
        if($event->getPlayer() instanceof Player) {
            $p = $event->getPlayer();
            $block_main = $event->GetBlock()->GetID();
            $block_notmain = $event->GetBlock();
            $cancel = false;
            $pos = new Vector3($block_notmain->getX(),$block_notmain->getY(),$block_notmain->getZ());
            $blocklist = array(54, 61, 62, 245, 324, 355);
            $itemlist = array(325, 259, 351);
            $wand = 271;
            $item_main = $event->getItem()->getID();
            $nickname = $p->getName();
            $blacklist = 0;
            $n = strtolower($p->getName());
            if($item_main === $wand) {
                $this->pos2[$n] = new Vector3(round($block_notmain->getX()),round($block_notmain->getY()),round($block_notmain->getZ()));
                $o = "Position 2 set to: (" . $this->pos2[$n]->getX() . "," . $this->pos2[$n]->getY() . "," . $this->pos2[$n]->getZ() . ")";
            } else {
                $o = "You don't have permission to use this subcommand.";
            }
            foreach($this->regions as $region) {
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.access"))){      
                    $array_members = $region->getMembers();
                    $array_owners = $region->getOwners();
                    if($region->contains($pos) && $region->getFlag("chest")){
                        if(in_array($block_main, $blocklist) && !(in_array($nickname, $array_members) || in_array($nickname, $array_owners))){
                            $cancel = true;
                            $o = "You can't interact with this block here";
                            $p->sendMessage($o); 
                        }
                        if(in_array($item_main, $itemlist) && !(in_array($nickname, $array_members) || in_array($nickname, $array_owners))){
                            $cancel = true; 
                            $o = "You can't interact using this item here";
                            $p->sendMessage($o); 
                        }
                    }
                }        
            }
            if($cancel) {
                $event->setCancelled();
            }
        }
    }
    /**
    * @param EntityDamageEvent $event
    *
    * @ignoreCancelled true
    */
    public function onHurt(EntityDamageEvent $event) {
        if($event->getEntity() instanceof Player) {
            $p = $event->getEntity();
            $cancel = false;
            $pos = new Vector3($p->getX(),$p->getY(),$p->getZ());
            foreach($this->regions as $region) {
                if($region->contains($pos) && $region->getFlag("god")) {
                $cancel = true;
                }
            }
            if($cancel) {
                $event->setCancelled();
            }
        }
    }
    /**
    * @param BlockBreakEvent $event
    *
    * @ignoreCancelled true
    */
    public function onBlockBreak(BlockBreakEvent $event) {
        $b = $event->getBlock();
        $p = $event->getPlayer();
        $nickname = $p->getName();
        $cancel = false;
        $pos = new Vector3($b->x,$b->y,$b->z);
        foreach($this->regions as $region) {
            $array_members = $region->getMembers();
            $array_owners = $region->getOwners();
            if($region->contains($pos) && $region->getFlag("edit") && !(($p->hasPermission("regiondefend")) || ($p->hasPermission("regiondefend.access")))) {
                if( !(in_array($nickname, $array_members) || in_array($nickname, $array_members) ) ) {
                    $cancel = true;
                    $o = "§4You can't break blocks hear.";
                    $p->sendMessage($o);
                }
            }
        }
        if($cancel) {
            $event->setCancelled();
        }
    }
    /**
    * @param BlockPlaceEvent $event
    *
    * @ignoreCancelled true
    */
    public function onBlockPlace(BlockPlaceEvent $event) {
        $b = $event->getBlock();
        $p = $event->getPlayer();
        $nickname = $p->getName();
        $cancel = false;
        $pos = new Vector3($b->x,$b->y,$b->z);
        foreach($this->regions as $region) {
            $array_members = $region->getMembers();
            $array_owners = $region->getOwners();
            if($region->contains($pos) && $region->getFlag("edit") && !($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.access"))){
                if(!(in_array($nickname, $array_members) || in_array($nickname, $array_members) ) ) {
                    $cancel = true;
                    $o = "§4You can't place blocks hear.";
                    $p->sendMessage($o);
                }
            }   
        }
        if($cancel) {
            $event->setCancelled();
        }
    }
    public function saveregions() {
        file_put_contents($this->getDataFolder() . "regions.dat",yaml_emit($this->regiondata));
    }
}

