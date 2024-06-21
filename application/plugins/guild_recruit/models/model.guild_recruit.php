<?php

    class Mguild_recruit extends model
    {
        private $characters = [];

        public function __contruct()
        {
            parent::__construct();
        }
        
        public function loadCharList($account, $server){
			$stmt = $this->website->db('game', $server)->prepare('SELECT Name FROM Character WHERE AccountId = :account');
			$stmt->execute([':account' => $account]);
			return $stmt->fetch_all();
		}
        
        public function getGuildData($name, $server){
            $stmt = $this->website->db('game', $server)->prepare('SELECT G_Name, G_Status FROM GuildMember WHERE Name = :name');
            $stmt->execute([':name' => $name]);
            $data = $stmt->fetch();
            if($data != false){
                if(in_array($data['G_Status'], [32,64,128])){
                    return [$data['G_Name'], $this->getGuildRecruitData($data['G_Name'], $server)];
                }
            }
            return [false, false];
        }
        
        private function getGuildRecruitData($gname, $server){
            $stmt = $this->website->db('web')->prepare('SELECT id, discord_link, gname FROM DmN_GuildRecruit WHERE gname = :gname AND server = :server');
            $stmt->execute([':gname' => $gname, ':server' => $server]);
            return $stmt->fetch();
        }
        
        public function updateDiscord($gname, $link, $server){
            $data = $this->getGuildRecruitData($gname, $server);
            if($data != false){
                $stmt = $this->website->db('web')->prepare('UPDATE DmN_GuildRecruit SET discord_link = :link WHERE gname = :gname AND server = :server');
                $stmt->execute([':link' => $link, ':gname' => $gname, ':server' => $server]);
            }
            else{
                $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_GuildRecruit (gname, discord_link, server) VALUES (:gname, :discord_link, :server)');
                $stmt->execute([':gname' => $gname, ':discord_link' => $link, ':server' => $server]);
            }
        }
        
        public function createRequest($amount, $reward, $payment_method, $payment_account, $user, $server){
            $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_WithdrawRequests (amount, reward_amount, memb___id, server, withdraw_account, withdraw_type, request_date) VALUES (:amount, :reward_amount, :memb___id, :server, :withdraw_account, :withdraw_type, :request_date)');
            $stmt->execute([
                ':amount' => $amount,
                ':reward_amount' => $reward,
                ':memb___id' => $user,
                ':server' => $server,
                ':withdraw_account' => $payment_account,
                ':withdraw_type' => $payment_method,
                ':request_date' => time()
            ]);
        }
        
        public function get_guid($user = '', $server)
        {
            $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid FROM MEMB_INFO WHERE memb___id = :user');
            $stmt->execute([':user' => $user]);
            $info = $stmt->fetch();
            return $info['memb_guid'];
        }
    }
