<?php

class Mpaghiper extends model{
	public $order_details = [];
	private $logs = [];
	
	public function __contruct(){
        parent::__construct();
    }
	
	/**
     * Load Paghiper package list
	 *
	 * @param bool $status
     *
     *
     * @return mixed
     */
	
	public function load_packages($status = false){
		$where = ($status == true) ? 'WHERE status = 1' : '';
        return $this->website->db('web')->query('SELECT id, package, reward, price, currency, orders, status, server FROM DmN_Donate_Paghiper_Packages '.$where.' ORDER BY orders ASC')->fetch_all();
    }
	
	/**
     * Check if Paghiper package exists
	 *
	 * @param int $id
     *
     *
     * @return mixed
     */
	
	public function check_package($id){
        $stmt = $this->website->db('web')->prepare('SELECT id, reward, price, currency FROM DmN_Donate_Paghiper_Packages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
	
	/**
     * Add Paghiper package
	 *
	 * @param string $title
	 * @param float $price
	 * @param int $currency
	 * @param int $reward
	 * @param string $server
     *
	 * @return mixed
     *
     */
	// @ioncube.dk cmsVersion('g8LU2sewjnwUpNnBTm9t85c3Xgf/0Y9V+rZWvw94O3A=', '009869451363953188238779430856374927754') -> "NewDmNIonCubeDynKeySecurityAlgo" RANDOM
	public function add_package($title, $price, $currency, $reward, $server){
        $max_orders = $this->website->db('web')->query('SELECT ISNULL(MAX(orders), 0) AS max_orders FROM DmN_Donate_Paghiper_Packages')->fetch();
        $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Donate_Paghiper_Packages (package, reward, price, currency, orders, status, server) VALUES (:title, :reward, :price, :currency, :count, 1, :server)');
        $stmt->execute([
			':title' => $title, 
			':reward' => $reward, 
			':price' => $price, 
			':currency' => $currency, 
			':count' => $max_orders['max_orders'], 
			':server' => $server
		]);
        return $this->website->db('web')->last_insert_id();
    }
	
	/**
     * Edit existing Paghiper package
	 *
	 * @param int $id
	 * @param string $title
	 * @param float $price
	 * @param int $currency
	 * @param int $reward
	 * @param string $server
     *
     *
     */
	// @ioncube.dk cmsVersion('g8LU2sewjnwUpNnBTm9t85c3Xgf/0Y9V+rZWvw94O3A=', '009869451363953188238779430856374927754') -> "NewDmNIonCubeDynKeySecurityAlgo" RANDOM
	public function edit_package($id, $title, $price, $currency, $reward, $server){
        $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Paghiper_Packages SET package = :title, reward = :reward, price = :price, currency = :currency, server = :server WHERE id = :id');
        $stmt->execute([
			':title' => $title, 
			':reward' => $reward, 
			':price' => $price, 
			':currency' => $currency, 
			':server' => $server, 
			':id' => $id
		]);
    }
	
	/**
     * Remove Paghiper package
	 *
	 * @param int $id
     *
     *
     */
	
	public function delete_package($id){
        $stmt = $this->website->db('web')->prepare('DELETE FROM DmN_Donate_Paghiper_Packages WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
	
	/**
     * Enable / Disabled Paghiper package
	 *
	 * @param int $id
	 * @param int $status
     *
     *
     */
	
	public function change_status($id, $status){
        $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Paghiper_Packages SET status = :status WHERE id = :id');
        $stmt->execute([
			':status' => $status, 
			':id' => $id
		]);
    }
	
	/**
     * Save Paghiper package order
     *
	 * @param array $orders
     *
     */
	
	public function save_order($orders){
        foreach ($orders as $key => $value){
            $id = explode('_', $value);
            $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Paghiper_Packages SET orders = :order WHERE id = :id');
            $stmt->execute([
				':order' => $key, 
				':id' => end($id)
			]);
        }
    }
	
	// @ioncube.dk cmsVersion('g8LU2sewjnwUpNnBTm9t85c3Xgf/0Y9V+rZWvw94O3A=', '009869451363953188238779430856374927754') -> "NewDmNIonCubeDynKeySecurityAlgo" RANDOM
	public function get_customer_data($id){
		$stmt = $this->website->db('web')->prepare('SELECT fname, lname, cpf_cnpj FROM DmN_Donate_Paghiper_Customer WHERE memb__guid = :id');
		$stmt->execute([':id' => $id]);
		return $stmt->fetch();
	}
	
	// @ioncube.dk cmsVersion('g8LU2sewjnwUpNnBTm9t85c3Xgf/0Y9V+rZWvw94O3A=', '009869451363953188238779430856374927754') -> "NewDmNIonCubeDynKeySecurityAlgo" RANDOM
	public function update_customer_data($id, $fname, $lname, $cpf_cnpj){
		if($this->get_customer_data($id) != false){
			$stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Paghiper_Customer SET fname = :fname, lname = :lname, cpf_cnpj = :cpf_cnpj WHERE memb__guid = :id');
		}
		else{
			$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Donate_Paghiper_Customer (fname, lname, cpf_cnpj, memb__guid) VALUES (:fname, :lname, :cpf_cnpj, :id)');
		}
		$stmt->execute([
			':fname' => $fname,
			':lname' => $lname,
			':cpf_cnpj' => $cpf_cnpj,
			':id' => $id
		]);
	}
	
	/**
     * Load Paghiper transactions for logs
	 *
	 * @param int $page
	 * @param int $per_page
	 * @param string $acc
	 * @param string $server
     *
     *
     */
	
	public function load_transactions($page = 1, $per_page = 25, $acc = '', $server = 'All'){
        if (($acc == '' || $acc == '-') && $server == 'All')
            $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_Donate_Paghiper_Transactions WHERE status = \'completed\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_Donate_Paghiper_Transactions ORDER BY id DESC) ORDER BY id DESC');
        else{
            if (($acc != '' && $acc != '-') && $server == 'All')
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_Donate_Paghiper_Transactions WHERE status = \'completed\' AND acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id FROM DmN_Donate_Paghiper_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' ORDER BY id DESC) ORDER BY id DESC');
            else
                $items = $this->website->db('web')->query('SELECT Top ' . $this->website->db('web')->escape($per_page) . ' transaction_id, amount, currency, acc, server, credits, order_date FROM DmN_Donate_Paghiper_Transactions WHERE status = \'completed\' AND acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' AND id Not IN (SELECT Top ' . $this->website->db('web')->escape($per_page * ($page - 1)) . ' id DmN_Donate_Paghiper_Transactions WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\' AND server = '.$this->website->db('web')->escape($server).' ORDER BY id DESC) ORDER BY id DESC');
        }

        foreach ($items->fetch_all() as $value){
            $this->logs[] = [
                'transaction' => $value['transaction_id'],
                'amount' => $value['amount'],
				'currency' => $value['currency'],
                'acc' => htmlspecialchars($value['acc']),
                'server' => htmlspecialchars($value['server']),
                'credits' => $value['credits'],
                'order_date' => date(DATETIME_FORMAT, $value['order_date'])
            ];
        }
        return $this->logs;
    }
	
	/**
     * Count total Paghiper transactions for pagination
	 *
	 * @param string $acc
	 * @param string $server
     *
     *
	 * @return int
     */
	
	public function count_total_transactions($acc = '', $server = 'All'){
        $sql = '';
        if ($acc != '' && $acc != '-'){
            $sql .= 'WHERE acc like \'%' . $this->website->db('web')->escape($acc) . '%\'';
            if ($server != 'All'){
                $sql .= ' AND server = '.$this->website->db('web')->escape($server).'';
            }
        }

        $count = $this->website->db('web')->snumrows('SELECT COUNT(acc) AS count FROM DmN_Donate_Paghiper_Transactions ' . $sql . '');
        return $count;
    }
	
	/**
     * Insert Paghiper order
	 *
	 * @param float $price
	 * @param int $currency
	 * @param int $reward
	 * @param string $item
	 * @param string $user
	 * @param string $server
     *
     *
	 * @return mixed
     */
	
	public function insert_order($price, $currency, $reward, $item, $user, $server){
        $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Donate_Paghiper_Orders (amount, currency, credits, account, server, hash) VALUES(:amount, :currency, :credits, :account, :server, :hash)');
        return $stmt->execute([
			':amount' => $price, 
			':currency' => $currency, 
			':credits' => $reward, 
			':account' => $user, 
			':server' => $server, 
			':hash' => $item
		]);
    }
	
	/**
     * Check if Paghiper order exists
	 *
	 * @param string $item
     *
     *
	 * @return mixed
     */
	
	public function check_order_number($item){
        $count = $this->website->db('web')->snumrows('SELECT COUNT(id) AS count FROM DmN_Donate_Paghiper_Orders where hash = '.$this->website->db('web')->escape($item).'');
        if ($count == 1){
            $this->order_details = $this->website->db('web')->query('SELECT amount, currency, account, server, credits, hash FROM DmN_Donate_Paghiper_Orders where hash = '.$this->website->db('web')->escape($item).'')->fetch();
            return true;
        } 
		else{
            return false;
        }
    }
	
	/**
     * Check if Paghiper transaction already processed
	 *
	 * @param string $item
     *
     *
	 * @return bool
     */
	
	public function check_completed_transaction($item){
        return $this->website->db('web')->query('SELECT amount, currency, acc, server, credits, status FROM DmN_Donate_Paghiper_Transactions where order_hash = '.$this->website->db('web')->escape($item).'')->fetch();
    }
	
	/**
     * insert Paghiper transaction
	 *
	 * @param string $item
     *
     *
	 * @return bool
     */
	
	public function insert_transaction_status($id, $item, $status){
        $stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Donate_Paghiper_Transactions (transaction_id, amount, currency, acc, server, credits, order_date, order_hash, status) VALUES (:trans_id, :gross, :currency, :account, :server, :credits, :time, :order_hash, :status)');
        return $stmt->execute([
			':trans_id' => $id, 
			':gross' => $this->order_details['amount'], 
			':currency' => $this->order_details['currency'], 
			':account' => $this->order_details['account'], 
			':server' => $this->order_details['server'],
			':credits' => $this->order_details['credits'], 
			':time' => time(),
			':order_hash' => $item,
			':status' => $status
		]);
    }
	
	public function update_transaction_status($item, $status){
        $stmt = $this->website->db('web')->prepare('UPDATE DmN_Donate_Paghiper_Transactions SET status = :status WHERE order_hash = :order_hash');
        return $stmt->execute([
			':status' => $status,
			':order_hash' => $item
		]);
    }
	
	// @ioncube.dk cmsVersion('g8LU2sewjnwUpNnBTm9t85c3Xgf/0Y9V+rZWvw94O3A=', '009869451363953188238779430856374927754') -> "NewDmNIonCubeDynKeySecurityAlgo" RANDOM
	public function add_total_recharge($account, $server, $credits){
		if($this->website->db('web')->check_if_table_exists('DmN_Total_Recharge')){
			$this->insert_recharge($account, $server, $credits);
		}
	}
	
	// @ioncube.dk cmsVersion('g8LU2sewjnwUpNnBTm9t85c3Xgf/0Y9V+rZWvw94O3A=', '009869451363953188238779430856374927754') -> "NewDmNIonCubeDynKeySecurityAlgo" RANDOM
	private function insert_recharge($account, $server, $credits){
		$stmt = $this->website->db('web')->prepare('INSERT INTO DmN_Total_Recharge (account, server, points, date) VALUES (:account, :server, :points, GETDATE())');
		$stmt->execute([':account' => $account, ':server' => $server, ':points' => $credits]);
	}
	
	/**
     * find out account memb_guid
	 *
	 * @param string $account
	 * @param string $server
     *
     *
	 * @return bool
     */
	
	public function get_guid($account, $server) {
        $stmt = $this->website->db('account', $server)->prepare('SELECT memb_guid FROM MEMB_INFO WHERE memb___id = :account');
        $stmt->execute([':account' => $account]);
        $guid = $stmt->fetch();
        if ($guid){
            return $guid['memb_guid'];
        }
        return false;
    }
	
	public function findReferral($acc){
		return $this->website->db('web')->query('SELECT refferer FROM DmN_Refferals WHERE refferal = '.$this->website->db('web')->escape($acc).'')->fetch()['refferer'];
	}
}
