<?php
namespace Georggi\RegionDefend;

use pocketmine\math\Vector3;
class region {
    public function __construct($data,$plugin) {
        $this->name = strtolower($data["name"]);
        $this->flags = $data["flags"];
        $this->members = $data["members"];
        $this->owners = $data["owners"];
        $this->pos1 = new Vector3($data["pos1"][0],$data["pos1"][1],$data["pos1"][2]);
        $this->pos2 = new Vector3($data["pos2"][0],$data["pos2"][1],$data["pos2"][2]);
        $this->plugin = $plugin;
        $this->save();
    }
    public function getMembers() {
        return $this->members;
    }
    public function getOwners() {
        return $this->owners;
    }
    /*
    if(is_array($array_members) || is_array($array_owners)){
        if(in_array($array_members, $variable) || in_array($array_owners, $variable)){
            Some code...
        }
    }else{
        if($array_members === $variable || $array_owners === $variable){
            Some code...
        }
    }
    */   
    public function addMember($NewMember) {
        $this->members[] = $NewMember;
        $this->save();
        $this->plugin->saveregions();
        return $this->members;
    }
    public function getPos1() {
        return $this->pos1;
    }
    public function getPos2(){
        return $this->pos2;
    }
    public function addOwner($NewOwner) {
        $this->owners[] = $NewOwner;
        $this->save();
        $this->plugin->saveregions();
        return $this->owners;
    }
    public function removeOwner($Owner) {
        unset($this->owners[array_search($Owner,  $this->owners)]);
        $this->save();
        $this->plugin->saveregions();
        return $this->owners;
    }
    public function removeMember($Member) {
        unset($this->members[array_search($Member,  $this->members)]);
        $this->save();
        $this->plugin->saveregions();
        return $this->members;
    }
    public function getName() {
        return $this->name;
    }
    public function getFlags() {
        return $this->flags;
    }
    public function getFlag($flag) {
        return $this->flags[$flag];
    }
    public function setFlag($flag,$value) {
        $this->flags[$flag] = $value;
        $this->save();
        $this->plugin->saveregions();
        return $value;
    }
    public function contains($ppos) {
        if((min($this->pos1->getX(),$this->pos2->getX()) <= $ppos->getX()) && (max($this->pos1->getX(),$this->pos2->getX()) >= $ppos->getX()) && (min($this->pos1->getY(),$this->pos2->getY()) <= $ppos->getY()) && (max($this->pos1->getY(),$this->pos2->getY()) >= $ppos->getY()) && (min($this->pos1->getZ(),$this->pos2->getZ()) <= $ppos->getZ()) && (max($this->pos1->getZ(),$this->pos2->getZ()) >= $ppos->getZ())) {
            return true;
        } else {
            return false;
        }
    }
    public function toggleFlag($flag) {
        $this->flags[$flag] = !$this->flags[$flag];
        $this->save();
        $this->plugin->saveregions();
        return $this->flags[$flag];
    }
    public function getData() {
        return array(   "owners" => $this->owners,
                        "name" => $this->name,
                        "members" => $this->members,
                        "flags" => $this->flags,
                        "pos1" => array($this->pos1->getX(),$this->pos1->getY(),$this->pos1->getZ()),
                        "pos2" => array($this->pos2->getX(),$this->pos2->getY(),$this->pos2->getZ()));
    }
    public function save() {
        $this->plugin->regions[$this->name] = $this;
        $this->plugin->regiondata[$this->name] = $this->getData();
    }
    public function delete() {
        $name = $this->getName();
        unset($this->plugin->regions[$name]);
        unset($this->plugin->regiondata[$name]);
        $this->plugin->regionAreas();
    }
}

