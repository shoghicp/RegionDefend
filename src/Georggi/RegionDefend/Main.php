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
use pocketmine\event\entity\EntityDamageByEntityEvent;
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
        $this->saveDefaultConfig();
        if(!file_exists($this->getDataFolder() . "regions.dat")) {
            file_put_contents($this->getDataFolder() . "regions.dat",yaml_emit(array()));
        }
        $this->regions = array();
        $this->regiondata = yaml_parse(file_get_contents($this->getDataFolder() . "regions.dat"));
        foreach($this->regiondata as $data) {
            $region = new region($data,$this);
        }
        foreach($this->regions as $region){
            $Flags = $region->getFlags();
            if(in_array("pvp", $Flags)){
                $region->setFlag("pvp", true);
            }
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
        $nickname = $p->getName();
        $n = strtolower($p->getName());
        $action = strtolower($args[0]);
        switch($action) {
            case "pos1":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.pos1"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                $this->pos1[$n] = new Vector3(round($p->getX()),round($p->getY()),round($p->getZ()));
                $MESSAGE_TO_PLAYER = "[RegionDefend] Position 1 set to: (" . $this->pos1[$n]->getX() . "," . $this->pos1[$n]->getY() . "," . $this->pos1[$n]->getZ() . ")";
                break;
            case "pos2":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.pos2"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                $this->pos2[$n] = new Vector3(round($p->getX()),round($p->getY()),round($p->getZ()));
                $MESSAGE_TO_PLAYER = "[RegionDefend] Position 2 set to: (" . $this->pos2[$n]->getX() . "," . $this->pos2[$n]->getY() . "," . $this->pos2[$n]->getZ() . ")";
                break;
            case "claim":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.create"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                if(!isset($args[1])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please specify a name for this region.";
                    break;
                }
                if(!(isset($this->pos1[$n]) && isset($this->pos2[$n]))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please select both positions first.";
                    break;
                }
                if(!isset($this->regions[strtolower($args[1])])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] An region with that name already exists.";
                    break;
                }
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
                    $Owners = $region->getOwners();
                    if(in_array($p->getName(), $MESSAGE_TO_PLAYERwners)){
                        $number = $number+1;
                    }
                }
                $distance = $this->pos1[$n]->distanceSquared($this->pos2[$n]);
                if(($MaxRegionSize < $distance || $MaxRegionNumber <= $number) || !$p->hasPermission("regiondefend.regionsize.infinity")){
                    if($MaxRegionSize < $distance){
                        $MESSAGE_TO_PLAYER = "[RegionDefend] You can't create that big region.\nYour size $distance. Max size $MaxRegionSize.";
                    } elseif($MaxRegionNumber <= $number){
                        $MESSAGE_TO_PLAYER = "[RegionDefend] You reached limit for regions.\nYour number of regions $number. Max number $MaxRegionNumber.";
                    } else {
                        $MESSAGE_TO_PLAYER = "[RegionDefend] An error occured, try to contact administrator please.";
                    }
                    break;
                }
                                    /*if($this->pos1[$n]->getX() >= $this->pos2[$n]->getX()){
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
                                    }*/
                /*if($Intersection != false){
                    $MESSAGE_TO_PLAYER = "Your region intersects with another region!";
                }*/
                $region = new region(array("owners" => array($p->getName()),"name" => strtolower($args[1]),"flags" => array("edit" => true,"god" => false,"chest" => true,"pvp" => true), "members" => array($p->getName()),"pos1" => array($this->pos1[$n]->getX(),$this->pos1[$n]->getY(),$this->pos1[$n]->getZ()),"pos2" => array($this->pos2[$n]->getX(),$this->pos2[$n]->getY(),$this->pos2[$n]->getZ())),$this);
                $this->saveregions();
                unset($this->pos1[$n]);
                unset($this->pos2[$n]);
                $MESSAGE_TO_PLAYER = "[RegionDefend] Region created!";   
                break;
            case "list":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.list"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand";
                    break;
                }    
                $MESSAGE_TO_PLAYER = "[RegionDefend] regions:";
                foreach($this->regions as $region) {
                    $MESSAGE_TO_PLAYER = $MESSAGE_TO_PLAYER . " " . $region->getName() . ";";
                }
                break;
            case "flag":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.flag"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                if(!isset($args[1])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please, specify a region!";
                    break;
                }
                if(!isset($this->regions[strtolower($args[1])])){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Region doesn't exist.";
                    break;
                }
                $region = $this->regions[strtolower($args[1])];
                $Owners = $region->getOwners();
                if(!(in_array($nickname, $Owners) || ($p->hasPermission("regiondefend.edit.others") ) )){  
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You can't edit this region.";
                    break;
                }
                if(!isset($args[2])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please specify a flag. (Flags: edit, god, chest, pvp)";
                    break;
                }
                if(!isset($region->flags[strtolower($args[2])])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Flag not found. (Flags: edit, god, chest, pvp)";
                    break;
                }
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
                $MESSAGE_TO_PLAYER = "[RegionDefend] Flag " . $flag . " set to " . $status . " for region " . $region->getName() . "!";
                break;
            case "delete":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.delete"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                if(!isset($args[1])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please specify an region to delete.";
                    break;
                }
                if(isset($this->regions[strtolower($args[1])])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Region does not exist.";
                    break;
                }
                $region = $this->regions[strtolower($args[1])];
                $Owners = $region->getOwners();
                if(!(in_array($nickname, $Owners) || ($p->hasPermission("regiondefend.edit.others")))){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You can't edit this region.";
                    break;
                }
                $region->delete();
                $MESSAGE_TO_PLAYER = "[RegionDefend] Region deleted!";
                break;
            case "addmember":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.addmember"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                if(!isset($args[1])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please specify region.";
                    break;
                }
                if(!isset($this->regions[strtolower($args[1])])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Region doesn't exist.";
                }
                $region = $this->regions[strtolower($args[1])];
                $Owners = $region->getOwners();
                if(!(in_array($nickname, $Owners) || ($p->hasPermission("regiondefend.edit.others")))){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You can't edit this region.";
                }
                if(!isset($args[2])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please enter a nickname.";
                }
                $NewMember = $args[2];
                $region->addMember($NewMember);
                $MESSAGE_TO_PLAYER = "[RegionDefend] You have successfully added $NewMember as member.";
                break;
            case "addowner":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.addowner"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }    
                if(!isset($args[1])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please specify region you would like to add member.";
                    break;
                }
                if(!isset($this->regions[strtolower($args[1])])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Region doesn't exist.";
                    break;
                } 
                $region = $this->regions[strtolower($args[1])];
                $Owners = $region->getOwners();
                if(!(in_array($nickname, $Owners) || ($p->hasPermission("regiondefend.edit.others")))){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You can't edit this region.";
                    break;
                }
                if(!isset($args[2])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please enter a nickname.";
                    break;
                }
                $NewOwner = $args[2];
                $region->addOwner($NewOwner);
                $MESSAGE_TO_PLAYER = "[RegionDefend] You have successfully added $NewOwner as owner. ";
                break;
            case"removeowner":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.removeowner"))) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                if(!isset($args[1])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please specify a region to remove owner in.";
                    break;
                }
                if(!isset($this->regions[strtolower($args[1])])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Region doesn't exist.";
                    break;
                }
                $region = $this->regions[strtolower($args[1])];
                $Owners = $region->getOwners();
                if(!(in_array($nickname, $Owners) || ($p->hasPermission("regiondefend.edit.others")))){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You can't edit this region.";
                    break;
                }
                if(!isset($args[2])){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Specify a player to remove";
                }
                $Owner_To_Remove = $args[2];
                if(!(in_array($Owner_To_Remove, $Owners))){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Player is not an owner of this region.";
                }
                $region->removeOwner($Owner_To_Remove);
                $MESSAGE_TO_PLAYER = "[RegionDefend] Player $Owner_To_Remove removed from owners succesfully.";
                break;
            case"removemember":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.removemember"))){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                if(!isset($args[1])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Please specify a region to remove owner in.";
                    break;
                }
                if(!isset($this->regions[strtolower($args[1])])) {
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Region doesn't exist.";
                    break;
                }
                $region = $this->regions[strtolower($args[1])];
                $Owners = $region->getOwners();
                if(!(in_array($nickname, $Owners()) || ($p->hasPermission("regiondefend.edit.others")))){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You can't edit this region.";
                    break;
                }
                if(!isset($args[2])){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Specify a player to remove";
                    break;
                }
                $Member_To_Remove = $args[2];
                $Members = $region->getMembers();
                if(!in_array($Member_To_Remove, $Members)){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Player is not an member of this region.";
                    break;
                }
                $region->removeMember($Member_To_Remove);
                $MESSAGE_TO_PLAYER = "[RegionDefend] Player $Member_To_Remove removed from members succesfully.";
                break;     
            case "info":
                if(!($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.info"))){
                    $MESSAGE_TO_PLAYER = "[RegionDefend] You don't have permission to use this subcommand.";
                    break;
                }
                if(!isset($args[1])){
                    $MESSAGE_TO_PLAYER = "Specify a region.";
                }
                if(!isset($this->regions[strtolower($args[1])])){
                    $MESSAGE_TO_PLAYER = "Region doesn't exist.";
                    break;
                }
                $pos = new Vector3($p->getX(),$p->getY(),$p->getZ());
                $region = $this->regions[strtolower($args[1])];
                $members = $region->GetMembers();
                $Owners = $region->GetOwners();
                $flag_chest = $region->getFlag("chest");
                $flag_god = $region->getFlag("god");
                $flag_edit = $region->getFlag("edit");
                $pos1 = $region->getPos1();
                $pos2 = $region->getPos2();
                $name = $region->getName();
                $MESSAGE_TO_PLAYER = ("Region name: $name, pos1: $pos1, pos2: $pos2\n Flags: chest = $flag_chest, god = $flag_god, edit = $flag_edit.");
                $p->sendMessage($MESSAGE_TO_PLAYER);
                $p->sendMessage("Members: ");
                foreach($members as $member){
                    $p->sendMessage($member);
                }
                $p->sendMessage("Owners: ");
                foreach($Owners as $Owner){
                $p->sendMessage($Owner);
                }
                break;
            case "wand":
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.command") || $p->hasPermission("regiondefend.command.region") || $p->hasPermission("regiondefend.command.region.wand")){
                    $p->getInventory()->addItem(clone $wand);
                    $MESSAGE_TO_PLAYER = "[RegionDefend] Gived a wand to user.";
                }
                break;
                //Actually crashes the server if I remember correctly
            default:
                return false;
                break;
        }
        $p->sendMessage($MESSAGE_TO_PLAYER);
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
                $MESSAGE_TO_PLAYER = "Position 2 set to: (" . $this->pos2[$n]->getX() . "," . $this->pos2[$n]->getY() . "," . $this->pos2[$n]->getZ() . ")";
            }
            foreach($this->regions as $region) {
                if($p->hasPermission("regiondefend") || $p->hasPermission("regiondefend.access")){      
                    $array_members = $region->getMembers();
                    $array_owners = $region->getOwners();
                    if($region->contains($pos) && $region->getFlag("chest")){
                        if(in_array($block_main, $blocklist) && !(in_array($nickname, $array_members) || in_array($nickname, $array_owners))){
                            $cancel = true;
                            $MESSAGE_TO_PLAYER = "You can't interact with this block here";
                            $p->sendMessage($MESSAGE_TO_PLAYER); 
                        }
                        if(in_array($item_main, $itemlist) && !(in_array($nickname, $array_members) || in_array($nickname, $array_owners))){
                            $cancel = true; 
                            $MESSAGE_TO_PLAYER = "You can't interact using this item here";
                            $p->sendMessage($MESSAGE_TO_PLAYER); 
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
    public function onHurtByPlayer(EntityDamageByEntityEvent $event) {
        if(($event->getEntity() instanceof Player) && ($event->getDamager() instanceof Player)) {
            $Attacker = $event->getDamager();
            $p = $event->getEntity();
            $cancel = false;
            $pos = new Vector3($p->getX(),$p->getY(),$p->getZ());
            foreach($this->regions as $region) {
                if($region->contains($pos) && !$region->getFlag("pvp")) {
                $cancel = true;
                $Attacker->sendMessage("You can't pvp here!");
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
                    $MESSAGE_TO_PLAYER = "You can't break blocks hear.";
                    $p->sendMessage($MESSAGE_TO_PLAYER);
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
                    $MESSAGE_TO_PLAYER = "You can't place blocks hear.";
                    $p->sendMessage($MESSAGE_TO_PLAYER);
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

