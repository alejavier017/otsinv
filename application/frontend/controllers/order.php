<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class order extends CI_Controller {
 
	function __construct()  
	{
		parent::__construct();
		$this->load->model("orderModel",'',true);
		$this->load->model("inventoryModel",'',true);
		$this->load->model("processModel",'',true);
		$this->load->model("productModel",'',true);
	}
	
	public function index()
	{
		$rsListing['orderNo'] = $this->orderModel->generateOrderNo();
		// Load Views
		$this->load->view('order/orderForm', $rsListing);	
	}

	### Auther : Nikunj Bambhroliya
	### Desc : save Order
	public function saveOrder()
	{
		$productArr = $_REQUEST['productArr'];	
		$cnt = 0;
		// Order Master Entry
		$arrData = array();
		$arrData['order_no'] = $this->Page->getRequest("txtOrderNo");
		$arrData['customer_id'] = $this->Page->getRequest("selCustomer");
		$arrData['tot_prod_qty'] = $this->Page->getRequest("totalQty");
		$arrData['sub_total_amount'] = $this->Page->getRequest("subTotal");
		$arrData['total_tax'] = $this->Page->getRequest("totalTax");
		$arrData['discount_type'] = $this->Page->getRequest("discountType");
		$arrData['discount_value'] = $this->Page->getRequest("discountValue");
		$arrData['discount_amount'] = $this->Page->getRequest("discountAmount");
		$arrData['adjust_amount'] = $this->Page->getRequest("adjustAmount");
		$arrData['final_total_amount'] = $this->Page->getRequest("finalTotalAmount");
		$arrData['order_date'] = $this->Page->getRequest("txtOrderDate");
		$arrData['order_ship_date'] = $this->Page->getRequest("txtShipDate");
		$arrData['order_note'] = $this->Page->getRequest("txtNote");
		$arrData['insertby']		=	$this->Page->getSession("intUserId");
		$arrData['insertdate'] 		= 	date('Y-m-d H:i:s');

		$this->orderModel->tbl = "order_master";
		$orderId = $this->orderModel->insert($arrData);
		
		if($orderId != "" && $orderId != 0)
		{
			foreach($productArr AS $prod_id=>$arr)
			{
				// Order Product Detail Entry
				$arrData = array();
				$arrData['order_id'] = $orderId;
				$arrData['prod_id'] = $prod_id;
				$arrData['prod_type'] = $arr['prodType'];
				$arrData['prod_qty'] = $arr['prodQty'];
				$arrData['price_per_qty'] = $arr['pricePerQty'];
				$arrData['tax_value'] = $arr['prodTax'];
				$arrData['tax_amount'] = $arr['prodTaxAmount'];
				$arrData['discount_value'] = $arr['prodDiscValue'];
				$arrData['discount_type'] = $arr['prodDiscType'];
				$arrData['discount_amount'] = $arr['prodDiscAmount'];
				$arrData['prod_total_amount'] = $arr['prodTotalAmount'];
				$arrData['insertby'] = $this->Page->getSession("intUserId");
				$arrData['insertdate'] = date('Y-m-d H:i:s');

				$this->orderModel->tbl = "order_product_detail";
				$this->orderModel->insert($arrData);
				$cnt++;
			}
		}

		if($cnt > 0)
		{
			echo "1"; exit;
		}
		else
		{
			echo "2"; exit;
		}
	}
	
	public function checkMftOrder()
	{
		// Get user assigned process stage
		$searchCriteria = array();
		$searchCriteria['userId'] = $this->Page->getSession("intUserId");
		$this->orderModel->searchCriteria = $searchCriteria;
		$usrStageArr = $this->orderModel->getOrderStages();
		$rsListing['usrStageArr'] = $usrStageArr;
		
		$stageIds = "";
		foreach($usrStageArr AS $row)
		{
			$stageIds .=  $row['ps_id'].",";
		}
		if(strpos($stageIds,",") == true)
		{
			$stageIds = rtrim($stageIds,",");
		}
		
		// Get Orders
		if($stageIds != "")
		{
			$searchCriteria = array();
			$searchCriteria['userId'] = $this->Page->getSession("intUserId");
			$searchCriteria['stageId'] = $stageIds;
			$this->orderModel->searchCriteria = $searchCriteria;
			$usrOrderArr = $this->orderModel->getMftOrders();
		}
		
		$productListArr = array(); // stage wise product array
		$orderListArr = array(); // stage wise order array
		$orderProductListArr = array(); // stage and order wise product array
		$productIds = "";

		if(count($usrOrderArr) > 0)
		{
			foreach($usrOrderArr AS $row)
			{
				$nxt_stage_id = (int)$row['nxt_stage_id'];
				$prv_proceed_qty = (int)$row['prv_proceed_qty'];
				$prod_tot_qty = ($row['seq'] == 1)?(int)$row['prod_tot_qty']:(int)$prv_proceed_qty;
				$stage_inv_qty = (int)$row['stage_inv'];
				$prod_proceed_qty = (int)$row['proceed_qty']+(int)$stage_inv_qty;
				$remain_proceed_qty = $prod_tot_qty - $prod_proceed_qty;
				
				if($prod_proceed_qty < $prod_tot_qty)
				{
					// set stage wise order array
					$orderListArr[$row['stage_id']][$row['mft_id']] =  $row['mft_no'];
					
					// set stage and order wise product array
					$temp = array();
					$temp["mft_id"] = $row['mft_id'];
					$temp["prod_id"] = $row['prod_id'];
					$temp["prod_tot_qty"] = $prod_tot_qty;
					$temp["proceed_qty"] = $prod_proceed_qty;
					$temp["remain_qty"] = $remain_proceed_qty;
					$temp["stage_inv_qty"] = $stage_inv_qty;
					$temp["seq"] = $row['seq'];
					$temp["last_seq"] = $row['last_seq'];
					$temp["nxt_stage_id"] = $nxt_stage_id;
					$orderProductListArr[$row['stage_id']][$row['prod_id']][$row['mft_no']] =  $temp;

					// set stage wise product array
					$productListArr[$row['stage_id']][$row['prod_id']]['prod_tot_qty'] += $prod_tot_qty;
					$productListArr[$row['stage_id']][$row['prod_id']]['proceed_qty'] += $prod_proceed_qty;
					$productListArr[$row['stage_id']][$row['prod_id']]['remain_qty'] += $remain_proceed_qty;
					$productListArr[$row['stage_id']][$row['prod_id']]['stage_inv_qty'] += $stage_inv_qty;

					$productIds .= $row['prod_id'].",";
				}
			}
		}

		//$this->Page->pr($productListArr); exit;
		
		if(strpos($productIds,",") == true)
		{
			$productIds = rtrim($productIds,",");
		}
		
		// Get Products
		if($productIds != "")
		{
			$searchCriteria = array();
			$searchCriteria['prod_id'] = $productIds;
			$searchCriteria['status'] = 'ACTIVE';
			$this->productModel->searchCriteria = $searchCriteria;
			$rsProducts = $this->productModel->getProduct();
		}
		
		$productsArr = array();
		if(count($rsProducts) > 0)
		{
			foreach($rsProducts as $row)
			{
				$productsArr[$row['prod_id']] = $row;
			}
		}

		$rsListing['productsArr']	=	$productsArr;
		$rsListing['orderListArr'] = $orderListArr;
		$rsListing['productListArr'] = $productListArr;
		$rsListing['orderProductListArr'] = $orderProductListArr;
		// Load Views
		$this->load->view('order/checkOrderForm', $rsListing);	
	}
	
	### Auther : Nikunj Bambhroliya
	### Desc : save menufecture order details
	public function saveMftOrder()
	{
		$dataArr = $_REQUEST['dataArr'];
		
		$arrData = array();
		$arrData['mft_no'] = "mo_".mt_rand();
		$arrData['insertby']		=	$this->Page->getSession("intUserId");
		$arrData['insertdate'] 		= 	date('Y-m-d H:i:s');
		
		$this->orderModel->tbl = "mft_master";
		$menufectureId = $this->orderModel->insert($arrData);
		
		if(count($dataArr)>0 && $menufectureId != "")
		{
			foreach($dataArr AS $row)
			{
				$initialStage = 0;
				// Get Process Stage Details
				$searchCriteria = array();
				$searchCriteria['selectField'] = "mpp.prod_id,mps.process_id,mps.stage_id,mps.seq,GROUP_CONCAT(mup.u_id) AS user_id";
				$searchCriteria['prod_id'] = $row["prodId"];
				$this->processModel->searchCriteria=$searchCriteria;
				$rsProcessStage = $this->processModel->getProcessStageByProduct();
				if(count($rsProcessStage) > 0)
				{
					foreach($rsProcessStage AS $rowStage)
					{
						// set initial stageid
						if($rowStage["seq"] == 1){
							$initialStage = $rowStage["stage_id"];
						}
						// Add Order time process stage details
						$arrData = array();
						$arrData['mft_id'] = $menufectureId;
						$arrData['prod_id'] = $row["prodId"];
						$arrData['process_id'] = $rowStage["process_id"];
						$arrData['stage_id'] = $rowStage["stage_id"];
						$arrData['stage_seq'] = $rowStage["seq"];
						$arrData['user_id'] = $rowStage["user_id"];
						$arrData['insertby'] =	$this->Page->getSession("intUserId");
						$arrData['insertdate'] = date('Y-m-d H:i:s');
						
						$this->processModel->tbl = "mft_order_time_process_stage";
						$this->processModel->insert($arrData);
					}
				}

				// Add menufecture product details
				$arrData = array();
				$arrData['mft_id'] = $menufectureId;
				$arrData['prod_id'] = $row["prodId"];
				$arrData['prod_qty'] = $row["prodQty"];
				$arrData['insertby'] =	$this->Page->getSession("intUserId");
				$arrData['insertdate'] = date('Y-m-d H:i:s');
				
				$this->orderModel->tbl = "mft_prod_detail";
				$this->orderModel->insert($arrData);
				
				// Add inventory details
				$arrData = array();
				$arrData['mft_id'] = $menufectureId;
				$arrData['prod_id'] = $row["prodId"];
				$arrData['prod_qty'] = $row["prodQty"];
				$arrData['action'] = "plus";
				$arrData['status'] = "in_process";
				$arrData['insertby'] =	$this->Page->getSession("intUserId");
				$arrData['insertdate'] = date('Y-m-d H:i:s');
				
				$this->inventoryModel->tbl = "inventory_master";
				$this->inventoryModel->insert($arrData);

				// Add inventory stage details for initial stage
				$arrData = array();
				$arrData['mft_id'] = $menufectureId;
				$arrData['prod_id'] = $row["prodId"];
				$arrData['stage_id'] = $initialStage;
				$arrData['prod_qty'] = $row["prodQty"];
				$arrData['action'] = "plus";
				$this->inventoryModel->tbl = "inventory_stage_detail";
				$this->inventoryModel->insert($arrData);
			}
		}
	}
	
	
	### Auther : Nikunj Bambhroliya
	### Desc : update order status submited by users from different stages with product qty.
	public function addMftOrderStatus()
	{
		// Get Proceed Qty
		$searchCriteria = array();
		$searchCriteria['selectField'] = "SUM(mps.qty) AS prod_qty ";
		$searchCriteria['mft_id'] = $this->Page->getRequest("order_id");
		$searchCriteria['prod_id'] = $this->Page->getRequest("product_id");
		$searchCriteria['stage_id'] = $this->Page->getRequest("stage_id");
		$this->orderModel->searchCriteria = $searchCriteria;
		$mftOrdStatusArr = $this->orderModel->getMftOrderStatus();
		$proceed_qty = (int)$mftOrdStatusArr[0]['prod_qty'];
		$proceed_qty = $proceed_qty + $this->Page->getRequest("qty");

		
		$total_qty = (int)$this->Page->getRequest("total_qty"); // Total Product Qty
		$seq = (int)$this->Page->getRequest("seq"); // seq
		$last_seq = (int)$this->Page->getRequest("last_seq"); // last seq
		$nxt_stage_id = $this->Page->getRequest("nxt_stage_id");
		
		if($proceed_qty <= $total_qty)
		{

			// Add menufecture Order status
			$arrData = array();
			$arrData['mft_id'] = $this->Page->getRequest("order_id");
			$arrData['prod_id'] = $this->Page->getRequest("product_id");
			$arrData['stage_id'] = $this->Page->getRequest("stage_id");
			$arrData['stage_seq'] = $this->Page->getRequest("stage_seq");
			$arrData['qty'] = $this->Page->getRequest("qty");
			$arrData['note'] = $this->Page->getRequest("note");
			$arrData['insertby'] =	$this->Page->getSession("intUserId");
			$arrData['insertdate'] = date('Y-m-d H:i:s');
			
			$this->orderModel->tbl = "mft_prod_status";
			$lst_id = $this->orderModel->insert($arrData);
			if($lst_id > 0)
			{
				// Add inventory stage details (in process forward entry)
				$arrData = array();
				$arrData['mft_id'] = $this->Page->getRequest("order_id");
				$arrData['prod_id'] = $this->Page->getRequest("product_id");
				$arrData['stage_id'] = $this->Page->getRequest("stage_id");
				$arrData['prod_qty'] = -1 * abs($this->Page->getRequest("qty"));
				$arrData['action'] = "minus";
				$this->inventoryModel->tbl = "inventory_stage_detail";
				$this->inventoryModel->insert($arrData);

				if($nxt_stage_id !=0)
				{
					// Add inventory stage details (in process revese entry)
					$arrData = array();
					$arrData['mft_id'] = $this->Page->getRequest("order_id");
					$arrData['prod_id'] = $this->Page->getRequest("product_id");
					$arrData['stage_id'] = $nxt_stage_id;
					$arrData['prod_qty'] = $this->Page->getRequest("qty");
					$arrData['action'] = "plus";
					
					$this->inventoryModel->tbl = "inventory_stage_detail";
					$this->inventoryModel->insert($arrData);
				}

				if($seq == $last_seq)
				{
					// Add inventory (in process reverse entry)
					$arrData = array();
					$arrData['mft_id'] = $this->Page->getRequest("order_id");
					$arrData['prod_id'] = $this->Page->getRequest("product_id");
					$arrData['prod_qty'] = -1 * abs($this->Page->getRequest("qty"));
					$arrData['action'] = "minus";
					$arrData['status'] = "in_process";
					$arrData['insertby'] =	$this->Page->getSession("intUserId");
					$arrData['insertdate'] = date('Y-m-d H:i:s');
					
					$this->inventoryModel->tbl = "inventory_master";
					$this->inventoryModel->insert($arrData);
					
					// Add inventory (in stock entry)
					$arrData = array();
					$arrData['mft_id'] = $this->Page->getRequest("order_id");
					$arrData['prod_id'] = $this->Page->getRequest("product_id");
					$arrData['prod_qty'] = $this->Page->getRequest("qty");
					$arrData['action'] = "plus";
					$arrData['status'] = "in_stock";
					$arrData['insertby'] =	$this->Page->getSession("intUserId");
					$arrData['insertdate'] = date('Y-m-d H:i:s');
					
					$this->inventoryModel->tbl = "inventory_master";
					$this->inventoryModel->insert($arrData);
				}
				echo "1";
			}
			else
			{
				echo "0";
			}
		}
		else
		{
			echo "2";
		}
		exit;
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */