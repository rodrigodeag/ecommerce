<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model
{
	const SESSION = "Cart";

	public static function getFromSession()
	{
		$cart = new Cart();

		if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
			$cart->get((int)$_SESSION[Cart::SESSION]['idcart'] > 0)
		} else {
			$cart->getFromSessionID();

			if (!(int)$cart->getidcart() > 0) {
				$data = [
					'dessessionid'=>session_id();
				];
				if(User::checkLogin(false)){
					$user = User::getFromSession();
					$data['iduser'] = $user->getiduser();
				}

				$cart->setData($data);
				$cart->save();
				$cart->setToSession();
				
			}

			return
		}
	}

	public function setToSession ()
	{
		$_SESSION[Cart::SESSION] = $this->getValues();
	}

	public function getFromSessionID ()
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid",[
			':dessessionid'=>session_id()
		]);

		$this->setData($results[0]);
	}


	public function get (int $idcart)
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart",[
			':idcart'=>$idcart
		]);

		if (coun($results) > 0) {
			$this->setData($results[0]);
		}

		
	}

	public function save()
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)",[
			':idcart'=>$this->getidcart(),
			':dessessionid'=>$this->getdessessionid(),
			':iduser'=>$this->getiduser(),
			':deszipcode'=>$this->getdeszipcode(),
			':vlfreight'=>$this->getvlfreight(),
			':nrdays'=>$this->getnrdays()
		]);

		$this->setData($results[0]);
	}

	public function addProduct(Product $product)
	{
		$sql = new Sql();
		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)",[
			':idcart'=>$this->getidcart();
			':idproduct'=>$this->getidproduct();
		]);
	}

	public function removeProduct(Product $product, $all = false)
	{
		$sql = new Sql();

		if ($all) {
			$sql->query("UPDATE tb_cartsproducts SET dtremove = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremove IS NULL",[
				':idcart'=>$this->getidcart();
				':idproduct'=>$this->getidproduct()
			]);
		} else {
			$sql->query("UPDATE tb_cartsproducts SET dtremove = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremove IS NULL LIMIT 1",[
				':idcart'=>$this->getidcart();
				':idproduct'=>$this->getidproduct();

			]);

		}
	}	

	public function getProducts()
	{
		$sql = new Sql();
		return Product::checkList($sql->select("
			SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b ON a.idproduct = b.idproduct
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
			GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
			ORDER BY b.desproduct 
		",[
			':idcart'=>$this->getidcart()
		]));
	}
}

 ?>