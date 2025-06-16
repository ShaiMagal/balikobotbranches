<?php
/**
 * balikobotbranches - balikobot branches
 * 
 * @author Avalanche media s.r.o
 * @link https://www.prestago.cz
*/
if (!defined('_PS_VERSION_')) {
	exit;
}

class balikobotbranches extends Module
{
	public function __construct()
	{
		$this->name = 'balikobotbranches';
		$this->tab = 'analytics_stats';
		$this->version = '1.0.0';
		$this->author = 'Avalanche media s.r.o';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->translate('Rozšíření modulu balíkobot o napojení na výdejní místa dopravců', array(), 'Modules.balikobotbranches.Admin');
		$this->description = $this->translate('Umožňuje rozšířít modul balíkobotu o napojení na výdejní místa', array(), 'Modules.balikobotbranches.Admin');
		$this->ps_versions_compliancy = array('min' => '1.6.1.23', 'max' => '8.2.0');
	}

	/**
	 * Překlady
	 * 
	 * @return string
	 */
	public function translate($msg, $params = array(), $domain = 'Modules.balikobotbranches.Admin')
	{
		return !method_exists($this, "trans") ? $this->l($msg) : $this->trans($msg, $params, $domain);
	}

	/**
	 * Registrace hooků
	 *
	 * @return bool
	 */
	public function registerHooks()
	{
		return $this->registerHook('actionBalikobotPreparePackageData');
	}
	
	/**
	 * Instalace
	 *
	 * @return bool
	 */
	public function install()
	{
		return parent::install() && $this->registerHooks();
	}

	/**
	 * Možnost úpravy balíku při sestavení
	 *
	 * @param array $params
	 * @example $params = [
	 *      'module_version' => monster_balikobot::VERSION,
	 *      'packages' => &$packages,                           // pole balíků [upravitelný parametr]
	 *      'carrierCode' => $carrierCode,
	 *      'serviceType' => $serviceType,
	 *      'order' => $order,
	 *      'customer' => $customer,
	 *      'address' => $address,
	 *      'country' => $country,
	 *      'currency' => $currency,
	 * ]
	 * @return void
	 */
	public function hookActionBalikobotPreparePackageData($params)
	{
		if (!isset($params['packages'])) 
			return;
		if (!Validate::isLoadedObject($params['order']) || !Validate::isLoadedObject($params['country']))
			return;

		$order = $params['order'];
		$country = $params['country'];

		$carrierCode = $params['carrierCode'];
		$serviceType = $params['serviceType'];
		$packages = &$params['packages']; 		// load intial data

		// $package_attr_set('key', 'value'); // set value to all packages
		$package_attr_set = (function($key, $value) use (&$packages)  {
			foreach ($packages as $package_key => &$package) {
				(!$key || !$value) ?:$package[$key] = $value;
			}
		});

		/////////////////////// NAPOJENI NA DOPRAVCE ///////////////////////
		/////////////////////// NAPOJENI NA DOPRAVCE ///////////////////////
		/////////////////////// NAPOJENI NA DOPRAVCE ///////////////////////

        // ČESKÁ POŠTA
        if ($carrierCode == 'cp' && $serviceType == 'NB') {
            if (Module::isInstalled('monster_cpost')){
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT zip FROM '._DB_PREFIX_.'monster_cpost_expedition WHERE id_order=' . (int)$order->id);
                } catch(Exception $e) {}

                if($id_branch) {
					$package_attr_set('rec_zip', $id_branch);		//
                }
            } else if (Module::isInstalled('shaim_balikovna')) {
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT psc FROM '._DB_PREFIX_.'shaim_balikovna_data JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int)$order->id);
                } catch(Exception $e) {}

                if($id_branch) {
					$package_attr_set('rec_zip', $id_branch);
                }
            } else if (Module::isInstalled('add_ceskaposta_carriers')) {
                try {
                    $id_branch = Db::getInstance()->getValue(
                        'SELECT psc FROM '._DB_PREFIX_.'add_cp_all_orders o
                        JOIN '._DB_PREFIX_.'add_cp_all_branches_B b ON o.id_branch = b.id
                        WHERE id_order=' . (int) $order->id
                    );
                } catch(Exception $e) {}

				if($id_branch) {
					$package_attr_set('rec_zip', $id_branch);
				}
			}
        }
        else if ($carrierCode == 'cp' && $serviceType == 'NP'){
            if (Module::isInstalled('monster_cpost')) {
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT zip FROM '._DB_PREFIX_.'monster_cpost_expedition WHERE id_order=' . (int) $order->id);
                } catch(Exception $e) {}
                
                if($id_branch) {
					$package_attr_set('rec_zip', $id_branch);
                }
            } else if (Module::isInstalled('add_ceskaposta_carriers')) {
                try {
                    $id_branch = Db::getInstance()->getValue(
                        'SELECT psc FROM '._DB_PREFIX_.'add_cp_all_orders o
                        JOIN '._DB_PREFIX_.'add_cp_all_branches_NP b ON o.id_branch = b.id
                        WHERE id_order=' . (int) $order->id
                    );
                } catch(Exception $e) {}

				if($id_branch) {
					$package_attr_set('rec_zip', $id_branch);
				}
			} else if (Module::isInstalled('shaim_baliknapostu')) {
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT psc FROM '._DB_PREFIX_.'shaim_baliknapostu_data JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int) $order->id);
                } catch(Exception $e) {}

                if($id_branch) {
					$package_attr_set('rec_zip', $id_branch);
                }
            }
        }


        // DPD
        else if ($carrierCode == 'dpd' && in_array($serviceType, [ 3 ])){
            if(Module::isInstalled('dpdparcelshop')){
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT zip FROM '._DB_PREFIX_.'cart_parcelshop JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int)$order->id);
                } catch(Exception $e) {}
                
                if($id_branch) {
					$package_attr_set('branch_id', $country->iso_code.$id_branch);
                }
            }
            if(Module::isInstalled('shaim_dpdparcelshop')){
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT id FROM '._DB_PREFIX_.'shaim_dpdparcelshop_data JOIN '._DB_PREFIX_.'shaim_dpdparcelshop USING(id) JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int)$order->id);
                } catch(Exception $e) {}
                
                if($id_branch) {
					$package_attr_set('branch_id', $country->iso_code.$id_branch);
                }
            }
        }


        // GLS
        else if ($carrierCode == 'gls' && in_array($serviceType, [ 2 ])){
            if(Module::isInstalled('shaim_glsparcelshop')){
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT sgd.id FROM '._DB_PREFIX_.'shaim_glsparcelshop_data sgd JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int) $order->id );
                } catch(Exception $e) {}

                if($id_branch) {
					$package_attr_set('branch_id', $id_branch);
                }
            }
        }


        // ULOŽENKA
        else if ($carrierCode == 'ulozenka' && in_array($serviceType, [ 1, 11 ])){
			if(Module::isInstalled('shaim_ulozenka')){
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT id FROM '._DB_PREFIX_.'shaim_ulozenka_data JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int) $order->id);
                    $id_branch = str_replace('ID', '', $id_branch);
                } catch(Exception $e) {}
			} elseif(Module::isInstalled('monster_ulozenka')){
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT id_ulozenka FROM '._DB_PREFIX_.'monster_ulozenka_expedition WHERE id_order=' . (int) $order->id);
                } catch(Exception $e) {}
            } else {
				try { // původní oficiální modul na uloženku
					$id_branch = Db::getInstance()->getValue('SELECT id_ulozenka FROM ' . _DB_PREFIX_ . 'ulozenka WHERE id_order=' . (int) $order->id);
				} catch(Exception $e) {}
			}
			if ($id_branch) {
				$package_attr_set('branch_id', $id_branch);
			}
        }
        else if ($carrierCode == 'ulozenka' && in_array($serviceType, [ 5 ])){
            try {
				$id_branch = Db::getInstance()->getValue('SELECT id_ulozenka FROM '._DB_PREFIX_.'ulozenka WHERE id_order=' . (int) $order->id);
				if($id_branch) {
					$package_attr_set('branch_id', $id_branch);
				}
			}catch(Exception $e){}

			if(!$id_branch && Module::isInstalled('dpdparcelshop')){
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT zip FROM '._DB_PREFIX_.'cart_parcelshop JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int) $order->id);
                }catch(Exception $e){}
                
                if($id_branch) {
					$package_attr_set('branch_id', $country->iso_code.$id_branch);
				}
			}
        }


        // PPL
        else if ($carrierCode == 'ppl' && in_array($serviceType, [ 46, 48 ])){
            if(Module::isInstalled('shaim_pplparcelshop')) {
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT id FROM '._DB_PREFIX_.'shaim_pplparcelshop_data JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int) $order->id);
                }catch(Exception $e){}
                
                if($id_branch) {
					$package_attr_set('branch_id', $id_branch);
                }
            }
        }


		// SLOVENSKA POSTA
		if ($carrierCode == 'sp' && $serviceType == 'BNP')
        {
            if(Module::isInstalled('shaim_baliknapostu')){
                try {
                    $id_branch = Db::getInstance()->getValue('SELECT psc FROM '._DB_PREFIX_.'shaim_baliknapostu_data JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int) $order->id);
                }
                catch(Exception $e) {}

                if($id_branch) {
					$package_attr_set('rec_zip', $id_branch);
                }
            }
		}


        // ZÁSILKOVNA , 3060 - PL Packzkomat
        else if ($carrierCode == 'zasilkovna' && in_array($serviceType, [ "VMCZ" , "VMPL", "VMSK", "VMHU", "VMRO", 3060 ])){
            try { 
                if(Module::isInstalled('monster_zasilkovna')) {
                    $id_branch = Db::getInstance()->getValue('SELECT id_zasilkovna FROM '._DB_PREFIX_.'monster_zasilkovna_expedition WHERE id_order=' . (int) $order->id);
                } elseif(Module::isInstalled('shaim_zasilkovna_widget')) {
                    $row = Db::getInstance()->getRow('SELECT id, carrierPickupPointId FROM '._DB_PREFIX_.'shaim_zasilkovna_widget_data JOIN '._DB_PREFIX_.'orders USING(id_cart) WHERE id_order=' . (int) $order->id);
                    //carrierPickupPointId - pobocka mimo zasilkovnu v zahranici
                    $id_branch = $row['id'] ? $row['id'] : $row['carrierPickupPointId'];
                } else {
                    $id_branch = Db::getInstance()->getValue('SELECT id_branch FROM '._DB_PREFIX_.'packetery_order WHERE id_order=' . (int) $order->id);
                }
            }
            catch(Exception $e) {}

            if($id_branch) {
				$package_attr_set('branch_id', $id_branch);
            }
        }
		
	}


}
