<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller {
	private $input = array();

	public function __construct() {
		parent::__construct();
		bloom_session();
		$this->call->model(array(
			'User_model', 'Category_model', 'Product_model', 'Addon_model',
			'Cart_model', 'Order_model', 'Delivery_model', 'Custom_order_model'
		));
	}

	public function session_info() {
		$this->method_only('GET');
		$this->respond(200, array('user' => $this->current_user(), 'csrf' => csrf_token()));
	}

	public function payment_options() {
		$this->method_only('GET');
		$this->respond(200, array(
			'deposit_percent' => 50,
			'online_available' => $this->paymongo_configured(),
		));
	}

	public function login() {
		$this->method_only('POST');
		$this->require_csrf();
		$input = $this->read_input();
		$email = strtolower(trim(isset($input['email']) ? (string) $input['email'] : ''));
		$password = isset($input['password']) ? (string) $input['password'] : '';
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
			$this->respond(422, array('error' => 'Enter a valid email address and password.'));
		}

		$row = $this->db_query('SELECT u.user_id, u.full_name, u.email, u.phone, u.password_hash, r.role_name
			FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE u.email = ? LIMIT 1', array($email));
		if ( ! $row || ! password_verify($password, $row['password_hash'])) {
			$this->respond(401, array('error' => 'Email or password is incorrect.'));
		}

		session_regenerate_id(true);
		$_SESSION['user_id'] = (int) $row['user_id'];
		unset($_SESSION['csrf']);
		$this->respond(200, array('user' => $this->public_user($row), 'csrf' => csrf_token()));
	}

	public function register() {
		$this->method_only('POST');
		$this->require_csrf();
		$input = $this->read_input();
		$name = trim(isset($input['full_name']) ? (string) $input['full_name'] : '');
		$email = strtolower(trim(isset($input['email']) ? (string) $input['email'] : ''));
		$phone = trim(isset($input['phone']) ? (string) $input['phone'] : '');
		$password = isset($input['password']) ? (string) $input['password'] : '';
		$name_length = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
		if ($name_length < 2 || $name_length > 100) {
			$this->respond(422, array('error' => 'Name must be between 2 and 100 characters.'));
		}
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
			$this->respond(422, array('error' => 'Enter a valid email address.'));
		}
		if ($phone !== '' && strlen($phone) > 20) {
			$this->respond(422, array('error' => 'Phone must be 20 characters or fewer.'));
		}
		if (strlen($password) < 8 || strlen($password) > 4096) {
			$this->respond(422, array('error' => 'Password must be at least 8 characters.'));
		}
		if ($this->db_query('SELECT user_id FROM users WHERE email = ? LIMIT 1', array($email))) {
			$this->respond(409, array('error' => 'An account with that email already exists.'));
		}
		$role = $this->db_query("SELECT role_id FROM roles WHERE role_name = 'customer' LIMIT 1");
		if ( ! $role) {
			error_log('Justine Klein registration failed: customer role is not configured.');
			$this->respond(500, array('error' => 'Account registration is temporarily unavailable.'));
		}
		$user_id = $this->db->table('users')->insert(array(
			'role_id' => (int) $role['role_id'],
			'full_name' => $name,
			'email' => $email,
			'phone' => $phone === '' ? null : $phone,
			'password_hash' => password_hash($password, PASSWORD_DEFAULT),
		));
		if ( ! $user_id) {
			error_log('Justine Klein registration failed: database did not return a new user id.');
			$this->respond(500, array('error' => 'Your account could not be created. Please try again.'));
		}

		session_regenerate_id(true);
		$_SESSION['user_id'] = (int) $user_id;
		unset($_SESSION['csrf']);
		$this->respond(201, array(
			'user' => array('user_id' => (int) $user_id, 'full_name' => $name, 'email' => $email, 'phone' => $phone, 'role' => 'customer'),
			'csrf' => csrf_token(),
		));
	}

	public function logout() {
		$this->method_only('POST');
		$this->require_csrf();
		$_SESSION = array();
		session_regenerate_id(true);
		$this->respond(200, array('user' => null, 'csrf' => csrf_token()));
	}

	public function catalog() {
		$this->method_only('GET');
		$this->respond(200, array(
			'categories' => $this->Category_model->list_all(),
			'products' => $this->Product_model->list_active(),
		));
	}

	public function product($id) {
		$this->method_only('GET');
		$product = $this->Product_model->get_product((int) $id);
		if ( ! $product) { $this->respond(404, array('error' => 'That bouquet could not be found.')); }
		$this->respond(200, array('product' => $product, 'addons' => $this->Addon_model->list_grouped()));
	}

	public function cart() {
		$this->method_only('GET');
		$user = $this->require_user();
		$this->respond(200, $this->Cart_model->get_cart((int) $user['user_id']));
	}

	public function cart_add() {
		$this->method_only('POST');
		$this->require_csrf();
		$user = $this->require_user();
		$input = $this->read_input();
		$product_id = isset($input['product_id']) ? filter_var($input['product_id'], FILTER_VALIDATE_INT) : false;
		$quantity = isset($input['quantity']) ? filter_var($input['quantity'], FILTER_VALIDATE_INT) : 1;
		$addon_ids = isset($input['addon_ids']) && is_array($input['addon_ids']) ? $input['addon_ids'] : array();
		if ( ! $product_id || ! $quantity || $quantity < 1 || $quantity > 99 || count($addon_ids) > 8) {
			$this->respond(422, array('error' => 'Choose a valid bouquet, quantity, and up to eight extras.'));
		}
		$addon_ids = array_values(array_unique(array_map('intval', $addon_ids)));
		if (in_array(0, $addon_ids, true) || count($addon_ids) > 8) {
			$this->respond(422, array('error' => 'One or more selected extras are invalid.'));
		}
		$product = $this->Product_model->get_product($product_id);
		if ( ! $product || (int) $product['stock_qty'] < $quantity) {
			$this->respond(409, array('error' => 'That bouquet is unavailable in the requested quantity.'));
		}
		$available_addons = $this->Addon_model->list_grouped();
		$available_ids = array();
		foreach ($available_addons as $group) {
			foreach ($group as $addon) { $available_ids[] = (int) $addon['addon_id']; }
		}
		if (array_diff($addon_ids, $available_ids)) {
			$this->respond(422, array('error' => 'One or more selected extras are no longer available.'));
		}
		if ( ! $this->Cart_model->add_item((int) $user['user_id'], (int) $product_id, (int) $quantity, $addon_ids)) {
			$this->respond(409, array('error' => 'There is not enough stock for that bouquet.'));
		}
		$this->respond(201, $this->Cart_model->get_cart((int) $user['user_id']));
	}

	public function cart_update($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$user = $this->require_user();
		$input = $this->read_input();
		$quantity = isset($input['quantity']) ? filter_var($input['quantity'], FILTER_VALIDATE_INT) : false;
		if ( ! $quantity || $quantity < 1 || $quantity > 99) {
			$this->respond(422, array('error' => 'Quantity must be between 1 and 99.'));
		}
		if ( ! $this->Cart_model->update_item((int) $user['user_id'], (int) $id, (int) $quantity)) {
			$this->respond(409, array('error' => 'The cart item is unavailable in that quantity.'));
		}
		$this->respond(200, $this->Cart_model->get_cart((int) $user['user_id']));
	}

	public function cart_remove($id) {
		$this->method_only('DELETE');
		$this->require_csrf();
		$user = $this->require_user();
		if ( ! $this->Cart_model->remove_item((int) $user['user_id'], (int) $id)) {
			$this->respond(404, array('error' => 'That item is no longer in your cart.'));
		}
		$this->respond(200, $this->Cart_model->get_cart((int) $user['user_id']));
	}

	public function orders() {
		$this->method_only('GET');
		$user = $this->require_user();
		$this->respond(200, array('orders' => $this->Order_model->list_for_user((int) $user['user_id'])));
	}

	public function create_order() {
		$this->method_only('POST');
		$this->require_csrf();
		$user = $this->require_user();
		$input = $this->read_input();
		$recipient = trim(isset($input['recipient_name']) ? (string) $input['recipient_name'] : '');
		$address = trim(isset($input['address']) ? (string) $input['address'] : '');
		$scheduled_input = trim(isset($input['scheduled_at']) ? (string) $input['scheduled_at'] : '');
		$message = trim(isset($input['card_message']) ? (string) $input['card_message'] : '');
		$payment_method = isset($input['payment_method']) ? (string) $input['payment_method'] : '';
		if ($recipient === '' || strlen($recipient) > 100) {
			$this->respond(422, array('error' => 'Recipient name is required and must be 100 characters or fewer.'));
		}
		if ($address === '' || strlen($address) > 255) {
			$this->respond(422, array('error' => 'Delivery address is required and must be 255 characters or fewer.'));
		}
		if (strlen($message) > 255) {
			$this->respond(422, array('error' => 'Card message must be 255 characters or fewer.'));
		}
		if ( ! in_array($payment_method, array('cash_at_shop', 'paymongo'), true)) {
			$this->respond(422, array('error' => 'Choose an available payment method.'));
		}
		if ($payment_method === 'paymongo' && ! $this->paymongo_configured()) {
			$this->respond(503, array('error' => 'Online payment is not configured yet. Choose cash at the shop or contact the shop.'));
		}
		$scheduled = DateTime::createFromFormat('!Y-m-d\TH:i', $scheduled_input);
		$date_errors = DateTime::getLastErrors();
		if ( ! $scheduled || ($date_errors && ($date_errors['warning_count'] || $date_errors['error_count']))
			|| $scheduled <= new DateTime()) {
			$this->respond(422, array('error' => 'Choose a valid future delivery date and time.'));
		}
		$result = $this->Order_model->create_from_cart((int) $user['user_id'], array(
			'recipient_name' => $recipient,
			'address' => $address,
			'scheduled_at' => $scheduled->format('Y-m-d H:i:s'),
			'card_message' => $message === '' ? null : $message,
		), $payment_method);
		if ( ! $result) {
			$this->respond(409, array('error' => 'Your cart is empty or a bouquet is no longer available. Review your cart and try again.'));
		}
		if ($payment_method === 'paymongo') {
			try {
				$result['payment_url'] = $this->create_paymongo_checkout($result, $user);
			} catch (Exception $exception) {
				error_log('Bloom Express PayMongo checkout creation failed for order ' . $result['order_number'] . ': ' . $exception->getMessage());
				$result['payment_error'] = 'Your order was saved, but the online payment page could not be opened. Retry payment from My Orders.';
			}
		}
		$this->respond(201, array('order' => $result));
	}

	public function retry_deposit($id) {
		$this->method_only('POST');
		$this->require_csrf();
		$user = $this->require_user();
		if ( ! $this->paymongo_configured()) {
			$this->respond(503, array('error' => 'Online payment is not configured yet. Contact the shop for help with your deposit.'));
		}
		$payment = $this->Order_model->find_pending_online_deposit((int) $user['user_id'], (int) $id);
		if ( ! $payment) {
			$this->respond(404, array('error' => 'No pending online deposit was found for that order.'));
		}
		if ($payment['payment_status'] === 'paid') {
			$this->respond(409, array('error' => 'The deposit for this order has already been paid.'));
		}
		if ( ! empty($payment['provider_url'])) {
			$this->respond(200, array('payment_url' => $payment['provider_url']));
		}
		try {
			$payment_url = $this->create_paymongo_checkout(array(
				'order_id' => (int) $payment['order_id'],
				'order_number' => $payment['order_number'],
				'deposit_amount' => number_format((float) $payment['amount'], 2, '.', ''),
			), array('full_name' => $payment['full_name'], 'email' => $payment['email']), (int) $payment['payment_id']);
		} catch (Exception $exception) {
			error_log('Bloom Express PayMongo retry failed for order ' . $payment['order_number'] . ': ' . $exception->getMessage());
			$this->respond(502, array('error' => 'The online payment page could not be opened. Please try again later.'));
		}
		$this->respond(200, array('payment_url' => $payment_url));
	}

	public function paymongo_webhook() {
		$this->method_only('POST');
		$secret = getenv('PAYMONGO_WEBHOOK_SECRET');
		$raw_body = file_get_contents('php://input');
		$signature_header = isset($_SERVER['HTTP_PAYMONGO_SIGNATURE']) ? $_SERVER['HTTP_PAYMONGO_SIGNATURE'] : '';
		if ( ! $secret || ! $this->valid_paymongo_signature($raw_body, $signature_header, $secret)) {
			$this->respond(401, array('error' => 'Invalid payment webhook signature.'));
		}
		$payload = json_decode($raw_body, true);
		if ( ! is_array($payload)) {
			$this->respond(400, array('error' => 'The payment webhook body is not valid JSON.'));
		}
		$event = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : array();
		if (isset($event['type']) && $event['type'] === 'checkout_session.payment.paid') {
			$session = isset($event['data']) && is_array($event['data']) ? $event['data'] : array();
			$attributes = isset($session['attributes']) && is_array($session['attributes']) ? $session['attributes'] : array();
			$payments = isset($attributes['payments']) && is_array($attributes['payments']) ? $attributes['payments'] : array();
			$paid_amount = null;
			foreach ($payments as $payment) {
				$payment_attributes = isset($payment['attributes']) && is_array($payment['attributes']) ? $payment['attributes'] : array();
				if (isset($payment_attributes['status']) && $payment_attributes['status'] === 'paid'
					&& isset($payment_attributes['amount'])) {
					$paid_amount = (int) $payment_attributes['amount'];
					break;
				}
			}
			if ($paid_amount === null || empty($session['id']) || empty($attributes['reference_number'])) {
				$this->respond(422, array('error' => 'The paid checkout event is missing its order or payment details.'));
			}
			$marked = $this->Order_model->mark_paymongo_deposit_paid(
				(string) $attributes['reference_number'],
				(string) $session['id'],
				$paid_amount
			);
			if ( ! $marked) {
				error_log('Bloom Express ignored a signed PayMongo event that did not match a pending order deposit.');
			}
		}
		$this->respond(200, array('received' => true));
	}

	public function cancel_order($id) {
		$this->method_only('POST');
		$this->require_csrf();
		$user = $this->require_user();
		$result = $this->Order_model->cancel_before_delivery((int) $user['user_id'], (int) $id);
		if ( ! $result) {
			$this->respond(409, array('error' => 'Orders can be cancelled before delivery starts. If an online payment is still being processed, wait for its result or contact the shop.'));
		}
		$this->respond(200, array('order' => $result));
	}

	public function set_order_visibility($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$user = $this->require_user();
		$input = $this->read_input();
		if ( ! isset($input['hidden']) || ! is_bool($input['hidden'])) {
			$this->respond(422, array('error' => 'Choose whether to hide or restore this order.'));
		}
		$result = $this->Order_model->set_customer_hidden((int) $user['user_id'], (int) $id, $input['hidden']);
		if ($result === false) {
			$this->respond(409, array('error' => 'Only your delivered or cancelled orders can be hidden or restored.'));
		}
		$this->respond(200, array('hidden' => $input['hidden'], 'hidden_at' => $result['hidden_at']));
	}

	public function track_order() {
		$this->method_only('POST');
		$this->require_csrf();
		$input = $this->read_input();
		$number = trim(isset($input['number']) ? (string) $input['number'] : '');
		$email = strtolower(trim(isset($input['email']) ? (string) $input['email'] : ''));
		if ($number === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->respond(422, array('error' => 'Enter the order number and the email used at checkout.'));
		}
		$order = $this->Order_model->find_by_number($number);
		if ( ! $order || strtolower($order['customer_email']) !== $email) {
			$this->respond(404, array('error' => 'No order matched that number and email.'));
		}
		$delivery = $this->Order_model->find_delivery((int) $order['order_id']);
		$this->respond(200, array('order' => array(
			'order_number' => $order['order_number'],
			'status' => $order['status'],
			'scheduled_at' => $delivery ? $delivery['scheduled_at'] : null,
			'delivered_at' => $order['status'] === 'delivered' ? $order['delivered_at'] : null,
			'delivered_to' => $order['status'] === 'delivered' ? $order['delivered_to'] : null,
			'confirmed_by_name' => $order['status'] === 'delivered' ? $order['confirmed_by_name'] : null,
			'history' => $this->Order_model->list_status_history((int) $order['order_id']),
		)));
	}

	public function custom_orders() {
		$this->method_only('GET');
		$user = $this->require_user();
		$this->respond(200, array('requests' => $this->Custom_order_model->list_for_customer((int) $user['user_id'])));
	}

	public function custom_order_create() {
		$this->method_only('POST');
		$this->require_csrf();
		$user = $this->require_user();
		$input = $this->read_input();
		$title = trim(isset($input['title']) ? (string) $input['title'] : '');
		$occasion = trim(isset($input['occasion']) ? (string) $input['occasion'] : '');
		$flowers = trim(isset($input['requested_flowers']) ? (string) $input['requested_flowers'] : '');
		$colors = trim(isset($input['colors']) ? (string) $input['colors'] : '');
		$budget_input = trim(isset($input['budget']) ? (string) $input['budget'] : '');
		$recipient = trim(isset($input['recipient_name']) ? (string) $input['recipient_name'] : '');
		$address = trim(isset($input['delivery_address']) ? (string) $input['delivery_address'] : '');
		$date_input = trim(isset($input['requested_delivery_at']) ? (string) $input['requested_delivery_at'] : '');
		if ($title === '' || strlen($title) > 120 || $flowers === '' || strlen($flowers) > 5000) {
			$this->respond(422, array('error' => 'Enter a request title and describe the flowers (up to 5,000 characters).'));
		}
		if (strlen($occasion) > 80 || strlen($colors) > 255) {
			$this->respond(422, array('error' => 'Occasion must be 80 characters or fewer and colors 255 characters or fewer.'));
		}
		$budget = null;
		if ($budget_input !== '') {
			if ( ! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $budget_input) || (float) $budget_input <= 0) {
				$this->respond(422, array('error' => 'Budget must be a positive amount with up to two decimal places.'));
			}
			$budget = number_format((float) $budget_input, 2, '.', '');
		}
		if ($recipient === '' || strlen($recipient) > 100 || $address === '' || strlen($address) > 255) {
			$this->respond(422, array('error' => 'Recipient name and delivery address are required and must fit their fields.'));
		}
		$requested_delivery = DateTime::createFromFormat('!Y-m-d\TH:i', $date_input);
		$date_errors = DateTime::getLastErrors();
		if ( ! $requested_delivery || ($date_errors && ($date_errors['warning_count'] || $date_errors['error_count']))
			|| $requested_delivery <= new DateTime()) {
			$this->respond(422, array('error' => 'Choose a valid future delivery date and time.'));
		}
		$result = $this->Custom_order_model->create_request((int) $user['user_id'], array(
			'title' => $title,
			'occasion' => $occasion === '' ? null : $occasion,
			'requested_flowers' => $flowers,
			'colors' => $colors === '' ? null : $colors,
			'budget' => $budget,
			'recipient_name' => $recipient,
			'delivery_address' => $address,
			'requested_delivery_at' => $requested_delivery->format('Y-m-d H:i:s'),
		));
		if ( ! $result) { $this->respond(500, array('error' => 'Your custom-order request could not be saved. Please try again.')); }
		$this->respond(201, array('request' => $result));
	}

	public function custom_order($id) {
		$this->method_only('GET');
		$user = $this->require_user();
		$request = $this->Custom_order_model->find_for_customer((int) $id, (int) $user['user_id']);
		if ( ! $request) { $this->respond(404, array('error' => 'That custom-order request could not be found.')); }
		$this->respond(200, array('request' => $request));
	}

	public function custom_order_message($id) {
		$this->method_only('POST');
		$this->require_csrf();
		$user = $this->require_user();
		$input = $this->read_input();
		$message = trim(isset($input['message']) ? (string) $input['message'] : '');
		if ($message === '' || strlen($message) > 5000) {
			$this->respond(422, array('error' => 'Message is required and must be 5,000 characters or fewer.'));
		}
		if ( ! $this->Custom_order_model->add_customer_message((int) $id, (int) $user['user_id'], $message)) {
			$this->respond(409, array('error' => 'This request is closed or is not available to your account.'));
		}
		$this->respond(200, array('request' => $this->Custom_order_model->find_for_customer((int) $id, (int) $user['user_id'])));
	}

	public function custom_order_decision($id) {
		$this->method_only('POST');
		$this->require_csrf();
		$user = $this->require_user();
		$input = $this->read_input();
		if ( ! isset($input['decision']) || ! in_array($input['decision'], array('accept', 'decline'), true)) {
			$this->respond(422, array('error' => 'Choose whether to accept or decline the quote.'));
		}
		if ( ! $this->Custom_order_model->customer_decision((int) $id, (int) $user['user_id'], $input['decision'] === 'accept')) {
			$this->respond(409, array('error' => 'This request does not have an active quote to decide on.'));
		}
		$this->respond(200, array('request' => $this->Custom_order_model->find_for_customer((int) $id, (int) $user['user_id'])));
	}

	public function admin_custom_orders() {
		$this->require_staff();
		$this->respond(200, array('requests' => $this->Custom_order_model->list_for_staff()));
	}

	public function admin_custom_order($id) {
		$this->require_staff();
		$request = $this->Custom_order_model->find_for_staff((int) $id);
		if ( ! $request) { $this->respond(404, array('error' => 'That custom-order request could not be found.')); }
		$this->respond(200, array('request' => $request));
	}

	public function admin_custom_order_message($id) {
		$this->method_only('POST');
		$this->require_csrf();
		$this->require_staff();
		$staff = $this->require_user();
		$input = $this->read_input();
		$message = trim(isset($input['message']) ? (string) $input['message'] : '');
		$quote_input = isset($input['quote_total']) ? trim((string) $input['quote_total']) : '';
		if ($message === '' || strlen($message) > 5000) {
			$this->respond(422, array('error' => 'Reply is required and must be 5,000 characters or fewer.'));
		}
		$quote = null;
		if ($quote_input !== '') {
			if ( ! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $quote_input) || (float) $quote_input <= 0) {
				$this->respond(422, array('error' => 'Quote must be a positive amount with up to two decimal places.'));
			}
			$quote = number_format((float) $quote_input, 2, '.', '');
		}
		if ( ! $this->Custom_order_model->add_staff_message((int) $id, (int) $staff['user_id'], $message, $quote)) {
			$this->respond(409, array('error' => 'This request is closed or could not be found.'));
		}
		$this->respond(200, array('request' => $this->Custom_order_model->find_for_staff((int) $id)));
	}

	public function admin_custom_order_convert($id) {
		$this->method_only('POST');
		$this->require_csrf();
		$this->require_staff();
		$staff = $this->require_user();
		$result = $this->Custom_order_model->convert_to_order((int) $id, (int) $staff['user_id']);
		if ( ! $result) {
			$this->respond(409, array('error' => 'Only accepted quotes with delivery details can be converted to an order.'));
		}
		$this->respond(201, array('order' => $result));
	}

	public function admin_dashboard() {
		$user = $this->require_staff();
		$dashboard = array(
			'pending' => $this->Order_model->count_by_status('pending'),
			'delivering' => $this->Order_model->count_by_status('out_for_delivery'),
			'low_stock' => $this->Product_model->count_low_stock(),
			'recent' => $this->Order_model->list_recent(5),
		);
		if ($user['role'] === 'admin') {
			$dashboard['week_sales'] = $this->Order_model->sales_since(date('Y-m-d H:i:s', strtotime('-7 days')));
		}
		$this->respond(200, $dashboard);
	}

	public function admin_products() { $this->require_admin(); $this->respond(200, array('products' => $this->Product_model->list_admin())); }

	public function admin_categories() {
		$this->require_admin();
		$this->respond(200, array('categories' => $this->Category_model->list_all()));
	}

	public function admin_product($id) {
		$this->require_admin();
		$product = $this->Product_model->find_admin((int) $id);
		if ( ! $product) { $this->respond(404, array('error' => 'That product could not be found.')); }
		$this->respond(200, array(
			'product' => $product,
			'usage' => $this->Product_model->usage((int) $id),
			'can_delete' => $this->Product_model->can_delete((int) $id),
		));
	}

	public function admin_product_create() {
		$this->method_only('POST');
		$this->require_csrf();
		$this->require_admin();
		$input = $this->read_input();
		list($clean, $errors) = $this->Product_model->validate($input);
		if ($errors) { $this->respond(422, array('error' => 'Please fix the highlighted fields.', 'errors' => $errors)); }
		$id = $this->Product_model->create_product($clean);
		if ( ! $id) { $this->respond(500, array('error' => 'The product could not be created.')); }
		$this->respond(201, array('product_id' => (int) $id));
	}

	public function admin_product_update($id) {
		$this->method_only('PUT');
		$this->require_csrf();
		$this->require_admin();
		$id = (int) $id;
		if ( ! $this->Product_model->find_admin($id)) { $this->respond(404, array('error' => 'That product could not be found.')); }
		$input = $this->read_input();
		list($clean, $errors) = $this->Product_model->validate($input, $id);
		if ($errors) { $this->respond(422, array('error' => 'Please fix the highlighted fields.', 'errors' => $errors)); }
		$this->Product_model->update_product($id, $clean);
		$this->respond(200, array('product_id' => $id));
	}

	public function admin_product_archive($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$this->require_admin();
		$id = (int) $id;
		$product = $this->Product_model->find_admin($id);
		if ( ! $product) { $this->respond(404, array('error' => 'That product could not be found.')); }
		$archived = ! (int) $product['is_archived'];
		$this->Product_model->set_archived($id, $archived);
		$this->respond(200, array('is_archived' => (bool) $archived));
	}

	public function admin_product_delete($id) {
		$this->method_only('DELETE');
		$this->require_csrf();
		$this->require_admin();
		$id = (int) $id;
		$product = $this->Product_model->find_admin($id);
		if ( ! $product) { $this->respond(404, array('error' => 'That product could not be found.')); }
		if ( ! $this->Product_model->can_delete($id)) {
			$this->respond(409, array('error' => 'This product has order or stock history and cannot be deleted. Archive it instead.'));
		}
		$this->Product_model->delete_product($id);
		$this->respond(200, array('product_id' => $id));
	}

	public function admin_orders() { $this->require_staff(); $this->respond(200, array('orders' => $this->Order_model->list_all())); }

	public function admin_archive_order($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$this->require_staff();
		$input = $this->read_input();
		if ( ! isset($input['archived']) || ! is_bool($input['archived'])) {
			$this->respond(422, array('error' => 'Choose whether to archive or restore this order.'));
		}
		$result = $this->Order_model->set_operations_archived((int) $id, $input['archived']);
		if ($result === false) {
			$this->respond(409, array('error' => 'Only delivered or cancelled orders can be archived or restored.'));
		}
		$this->respond(200, array('archived' => $input['archived'], 'archived_at' => $result['archived_at']));
	}

	public function admin_confirm_cash_deposit($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$staff = $this->require_staff();
		if ( ! $this->Order_model->mark_cash_deposit_paid((int) $id, (int) $staff['user_id'])) {
			$this->respond(409, array('error' => 'This order has no pending cash deposit to confirm.'));
		}
		$this->respond(200, array('status' => 'confirmed', 'deposit_status' => 'paid'));
	}

	public function admin_advance_order_status($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$staff = $this->require_staff();
		$input = $this->read_input();
		$status = isset($input['status']) ? (string) $input['status'] : '';
		if ( ! in_array($status, array('preparing', 'out_for_delivery'), true)) {
			$this->respond(422, array('error' => 'Choose preparation or dispatch; the assigned rider confirms delivery with the recipient PIN.'));
		}
		$result = $this->Order_model->advance_status((int) $id, $status, (int) $staff['user_id']);
		if ( ! $result) {
			$this->respond(409, array('error' => 'The order cannot move to that status yet. Confirm its deposit, assign a rider before dispatch, and record the delivery balance before marking it delivered.'));
		}
		$this->respond(200, array('order' => $result));
	}

	public function admin_confirm_cash_balance($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$staff = $this->require_staff();
		if ( ! $this->Order_model->mark_cash_balance_paid((int) $id, (int) $staff['user_id'])) {
			$this->respond(409, array('error' => 'The cash-on-delivery balance can only be recorded once while the order is out for delivery.'));
		}
		$this->respond(200, array('balance_status' => 'paid'));
	}

	public function admin_record_deposit_refund($id) {
		$this->method_only('POST');
		$this->require_csrf();
		$staff = $this->require_admin();
		$input = $this->read_input();
		if ( ! isset($input['amount']) || ! is_numeric($input['amount']) || (float) $input['amount'] <= 0) {
			$this->respond(422, array('error' => 'Enter a refund amount greater than zero.'));
		}
		$result = $this->Order_model->record_deposit_refund((int) $id, (int) $staff['user_id'], $input['amount']);
		if ( ! $result) {
			$this->respond(409, array('error' => 'Refunds can only be recorded for a cancelled order with a paid deposit, and cannot exceed the amount not yet refunded.'));
		}
		$this->respond(200, array('refund' => $result));
	}

	public function admin_deliveries() { $this->require_staff(); $this->respond(200, array('deliveries' => $this->Delivery_model->list_all())); }

	public function admin_archive_delivery($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$this->require_staff();
		$input = $this->read_input();
		if ( ! isset($input['archived']) || ! is_bool($input['archived'])) {
			$this->respond(422, array('error' => 'Choose whether to archive or restore this delivery.'));
		}
		$result = $this->Delivery_model->set_operations_archived((int) $id, $input['archived']);
		if ($result === false) {
			$this->respond(409, array('error' => 'Only closed deliveries for delivered or cancelled orders can be archived or restored.'));
		}
		$this->respond(200, array('archived' => $input['archived'], 'archived_at' => $result['archived_at']));
	}
	public function admin_assign_delivery($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$this->require_staff();
		$input = $this->read_input();
		$staff_id = isset($input['assigned_to']) ? (int) $input['assigned_to'] : 0;
		if ($staff_id < 1 || ! $this->Delivery_model->assign_rider((int) $id, $staff_id)) {
			$this->respond(409, array('error' => 'Choose a valid staff member for an order that is confirmed or being prepared.'));
		}
		$this->respond(200, array('assigned_to' => $staff_id));
	}
	public function admin_inventory() { $this->require_staff(); $this->respond(200, array('products' => $this->Product_model->list_inventory())); }
	public function admin_staff() { $this->require_admin(); $this->respond(200, array('staff' => $this->User_model->list_staff())); }
	public function admin_riders() { $this->require_staff(); $this->respond(200, array('riders' => $this->User_model->list_riders())); }
	public function admin_staff_role($id) {
		$this->method_only('PATCH');
		$this->require_csrf();
		$this->require_admin();
		$input = $this->read_input();
		$role = isset($input['role']) ? (string) $input['role'] : '';
		if ( ! $this->User_model->set_delivery_role((int) $id, $role)) {
			$this->respond(409, array('error' => 'Only staff and rider accounts can be switched. Reassign any active deliveries before changing a rider back to staff.'));
		}
		$this->respond(200, array('user_id' => (int) $id, 'role' => $role));
	}

	public function rider_deliveries() {
		$this->method_only('GET');
		$rider = $this->require_rider();
		$this->respond(200, array('deliveries' => $this->Delivery_model->list_assigned_to_rider((int) $rider['user_id'])));
	}

	public function rider_confirm_delivery($id) {
		$this->method_only('POST');
		$this->require_csrf();
		$rider = $this->require_rider();
		$input = $this->read_input();
		$pin = isset($input['pin']) ? trim((string) $input['pin']) : '';
		$recipient = isset($input['delivered_to']) ? trim((string) $input['delivered_to']) : '';
		$cash_collected = ! empty($input['cash_collected']);
		if ( ! preg_match('/^\d{6}$/', $pin)) {
			$this->respond(422, array('error' => 'Enter the six-digit recipient PIN.'));
		}
		if ($recipient === '' || strlen($recipient) > 100) {
			$this->respond(422, array('error' => 'Enter the name of the person who received the flowers.'));
		}
		$result = $this->Delivery_model->confirm_delivery((int) $id, (int) $rider['user_id'], $pin, $recipient, $cash_collected);
		if ( ! $result) {
			$this->respond(409, array('error' => 'Delivery could not be confirmed. Check that this delivery is assigned to you, the PIN is correct, and any cash-on-delivery balance was collected.'));
		}
		$this->respond(200, array('delivery' => $result));
	}

	public function admin_reports() {
		$this->require_admin();
		$since = date('Y-m-d 00:00:00', strtotime('-6 days'));
		$this->respond(200, array(
			'days' => $this->Order_model->report_by_day($since),
			'top' => $this->Order_model->report_top_products(5),
		));
	}

	private function current_user() {
		if (empty($_SESSION['user_id'])) { return null; }
		$row = $this->db_query('SELECT u.user_id, u.full_name, u.email, u.phone, r.role_name
			FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ? LIMIT 1',
			array((int) $_SESSION['user_id']));
		if ( ! $row) {
			unset($_SESSION['user_id']);
			return null;
		}
		return $this->public_user($row);
	}

	private function public_user(array $row) {
		return array(
			'user_id' => (int) $row['user_id'],
			'full_name' => $row['full_name'],
			'email' => $row['email'],
			'phone' => $row['phone'],
			'role' => $row['role_name'],
		);
	}

	private function require_user() {
		$user = $this->current_user();
		if ( ! $user) { $this->respond(401, array('error' => 'Sign in to continue.')); }
		return $user;
	}

	private function require_staff() {
		$user = $this->require_user();
		if ( ! in_array($user['role'], array('admin', 'staff'), true)) {
			$this->respond(403, array('error' => 'Staff access is required.'));
		}
		return $user;
	}

	private function require_admin() {
		$user = $this->require_user();
		if ($user['role'] !== 'admin') {
			$this->respond(403, array('error' => 'Admin access is required.'));
		}
		return $user;
	}

	private function require_rider() {
		$user = $this->require_user();
		if ($user['role'] !== 'rider') {
			$this->respond(403, array('error' => 'Rider access is required.'));
		}
		return $user;
	}

	private function paymongo_configured() {
		return (bool) getenv('PAYMONGO_SECRET_KEY')
			&& (bool) getenv('PAYMONGO_WEBHOOK_SECRET')
			&& function_exists('curl_init');
	}

	private function create_paymongo_checkout(array $order, array $user, $payment_id = null) {
		if ( ! $this->paymongo_configured()) {
			throw new RuntimeException('PayMongo credentials or the cURL extension are missing.');
		}
		$base_url = rtrim(getenv('APP_BASE_URL') ?: (getenv('RENDER_EXTERNAL_URL') ?: 'http://localhost:8000'), '/');
		if ( ! $payment_id) {
			$payment = $this->db_query('SELECT payment_id FROM payments
				WHERE order_id = ? AND payment_type = \'deposit\' AND provider = \'paymongo\' LIMIT 1',
				array((int) $order['order_id']));
			if ( ! $payment) {
				throw new RuntimeException('The order deposit record could not be found.');
			}
			$payment_id = (int) $payment['payment_id'];
		}
		$amount_cents = (int) round((float) $order['deposit_amount'] * 100);
		$request_body = array(
			'data' => array(
				'attributes' => array(
					'line_items' => array(array(
						'name' => '50% deposit for order ' . $order['order_number'],
						'amount' => $amount_cents,
						'currency' => 'PHP',
						'quantity' => 1,
					)),
					'payment_method_types' => array('card', 'gcash', 'qrph', 'paymaya'),
					'success_url' => $base_url . '/account/orders?payment=processing&order=' . rawurlencode($order['order_number']),
					'cancel_url' => $base_url . '/checkout?payment=cancelled&order=' . rawurlencode($order['order_number']),
					'reference_number' => $order['order_number'],
					'send_email_receipt' => true,
					'billing' => array(
						'name' => $user['full_name'],
						'email' => $user['email'],
					),
				),
			),
		);
		$encoded_body = json_encode($request_body);
		if ($encoded_body === false) {
			throw new RuntimeException('The payment request could not be encoded.');
		}
		$handle = curl_init('https://api.paymongo.com/v2/checkout_sessions');
		curl_setopt_array($handle, array(
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_USERPWD => getenv('PAYMONGO_SECRET_KEY') . ':',
			CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
			CURLOPT_POSTFIELDS => $encoded_body,
		));
		$response_body = curl_exec($handle);
		$curl_error = curl_error($handle);
		$status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);
		if ($response_body === false || $status < 200 || $status >= 300) {
			throw new RuntimeException($response_body === false ? $curl_error : 'PayMongo returned HTTP ' . $status . '.');
		}
		$response = json_decode($response_body, true);
		$session = isset($response['data']) && is_array($response['data']) ? $response['data'] : array();
		$attributes = isset($session['attributes']) && is_array($session['attributes']) ? $session['attributes'] : array();
		$checkout_url = isset($attributes['checkout_url']) ? (string) $attributes['checkout_url'] : '';
		if (empty($session['id']) || ! $this->is_paymongo_checkout_url($checkout_url)) {
			throw new RuntimeException('PayMongo returned an invalid checkout session.');
		}
		$this->Order_model->save_paymongo_session((int) $payment_id, (string) $session['id'], $checkout_url);
		return $checkout_url;
	}

	private function is_paymongo_checkout_url($url) {
		$parts = parse_url($url);
		return is_array($parts) && isset($parts['scheme'], $parts['host'])
			&& strtolower($parts['scheme']) === 'https'
			&& strtolower($parts['host']) === 'checkout.paymongo.com';
	}

	private function valid_paymongo_signature($body, $header, $secret) {
		$parts = array();
		foreach (explode(',', (string) $header) as $part) {
			$pair = explode('=', trim($part), 2);
			if (count($pair) === 2) { $parts[$pair[0]] = $pair[1]; }
		}
		if (empty($parts['t'])) { return false; }
		$expected = hash_hmac('sha256', $parts['t'] . '.' . $body, $secret);
		return (isset($parts['te']) && hash_equals($expected, $parts['te']))
			|| (isset($parts['li']) && hash_equals($expected, $parts['li']));
	}

	private function read_input() {
		if ( ! empty($this->input)) { return $this->input; }
		$raw = file_get_contents('php://input');
		$decoded = json_decode($raw, true);
		if ( ! is_array($decoded)) { $this->respond(400, array('error' => 'The request body must be valid JSON.')); }
		$this->input = $decoded;
		return $this->input;
	}

	private function require_csrf() {
		$sent = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? (string) $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
		if (empty($_SESSION['csrf']) || $sent === '' || ! hash_equals($_SESSION['csrf'], $sent)) {
			$this->respond(403, array('error' => 'The page security token expired. Refresh and try again.'));
		}
	}

	private function method_only($method) {
		$actual = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
		if ($actual !== $method) {
			header('Allow: ' . $method);
			$this->respond(405, array('error' => 'This request method is not allowed.'));
		}
	}

	private function db_query($sql, $values = array()) {
		return $this->db->raw($sql, $values)->fetch();
	}

	private function respond($status, array $payload) {
		http_response_code((int) $status);
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
		exit;
	}
}
