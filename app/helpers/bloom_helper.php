<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Bloom Express - small view helpers.
 */
if ( ! function_exists('peso'))
{
	function peso($amount)
	{
		return '₱' . number_format((float) $amount, 2);
	}
}

if ( ! function_exists('e'))
{
	function e($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}

if ( ! function_exists('occasion_emoji'))
{
	/** Emoji shown in place of a product photo, by occasion slug. */
	function occasion_emoji($slug)
	{
		$map = array('birthday' => '🎂', 'anniversary' => '💍', 'graduation' => '🎓', 'sympathy' => '🕊️', 'thank-you' => '💐');
		return isset($map[$slug]) ? $map[$slug] : '💐';
	}
}

if ( ! function_exists('status_label'))
{
	function status_label($status)
	{
		return ucwords(str_replace('_', ' ', (string) $status));
	}
}

if ( ! function_exists('demo_customer_id'))
{
	/**
	 * Until login exists, "My Orders" and the cart show this customer
	 * (Maria Santos, user_id 4 in the seed data). Replace with the
	 * logged-in user's id once authentication is added.
	 */
	function demo_customer_id()
	{
		return 4;
	}
}

/* ------------------------------------------------------------------
 * Checking 3 helpers: session, flash messages, CSRF token, form errors
 * ------------------------------------------------------------------ */
if ( ! function_exists('bloom_session'))
{
	/** Start the PHP session once (must run before any output). */
	function bloom_session()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
	}
}

if ( ! function_exists('flash_set'))
{
	/** Store a one-time message ('success' or 'error') for the next page. */
	function flash_set($type, $message)
	{
		bloom_session();
		$_SESSION['flash'] = array('type' => $type, 'message' => $message);
	}
}

if ( ! function_exists('flash_render'))
{
	/** Print and clear the one-time message. */
	function flash_render()
	{
		bloom_session();
		if (empty($_SESSION['flash'])) { return; }
		$f = $_SESSION['flash'];
		unset($_SESSION['flash']);
		echo '<div class="alert ' . e($f['type']) . '">' . e($f['message']) . '</div>';
	}
}

if ( ! function_exists('csrf_token'))
{
	function csrf_token()
	{
		bloom_session();
		if (empty($_SESSION['csrf'])) {
			$_SESSION['csrf'] = bin2hex(random_bytes(32));
		}
		return $_SESSION['csrf'];
	}
}

if ( ! function_exists('csrf_field'))
{
	/** Hidden input to put inside every POST form. */
	function csrf_field()
	{
		return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
	}
}

if ( ! function_exists('csrf_valid'))
{
	/** True when the submitted token matches the one stored in the session. */
	function csrf_valid()
	{
		bloom_session();
		$sent = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
		return ! empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
	}
}

if ( ! function_exists('field_error'))
{
	/** Red message under a form field. */
	function field_error($errors, $field)
	{
		return isset($errors[$field]) ? '<div class="field-error">' . e($errors[$field]) . '</div>' : '';
	}
}

