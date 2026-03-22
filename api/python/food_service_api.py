import requests
from flask import current_app

# Global helper to get the food service API IP address from config (set in .env)
def get_food_service_ip():
    return current_app.config.get('FOOD_SERVICE_IPV4', '192.168.1.9')

def get_food_menu():
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/menu_api.php"
    try:
        response = requests.get(url, timeout=5, verify=False)  # Bypass SSL verification
        response.raise_for_status()
        try:
            return response.json()
        except Exception:
            return {"error": "Food service is currently unavailable. Please try again later."}
    except Exception:
        return {"error": "Food service is currently unavailable. Please try again later."}

def send_food_order(menu_item_id, address, user_id):
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/order_api.php"
    try:
        resp = requests.post(url, json={
            'menu_item_id': menu_item_id,
            'address': address,
            'user_id': user_id
        }, verify=False)
        return resp.json()
    except Exception:
        return {'success': False, 'message': 'Food service is currently unavailable. Please try again later.'}

def send_full_food_order(user_id, address, items, total, notes=None):
    """
    Send a full food order (multiple items) to the Eat n Run PHP API.
    items: list of dicts, each with menu_id, quantity, price
    """
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/full_order_api.php"
    payload = {
        'user_id': user_id,
        'address': address,
        'items': items,  # [{menu_id, quantity, price}, ...]
        'total': total
    }
    if notes:
        payload['notes'] = notes
    try:
        resp = requests.post(url, json=payload, verify=False)
        return resp.json()
    except Exception:
        return {'success': False, 'message': 'Food service is currently unavailable. Please try again later.'}

def send_order_to_checkout_php(user_id, full_name, phone, delivery_address, payment_method, email=None, notes=None, delivery_notes=None, subtotal=None, delivery_fee=None, total_amount=None, payment_proof_path=None, items=None):
    """
    Send an order to the PHP checkout.php endpoint as a form POST.
    If payment_proof_path is provided, it will be sent as a file upload.
    Returns a dict with success status and order_id if available.
    """
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/checkout_api.php"
    data = {
        'user_id': user_id,
        'full_name': full_name,
        'phone': phone,
        'email': email,
        'delivery_address': delivery_address,
        'payment_method': payment_method,
        'notes': notes or '',
        'delivery_notes': delivery_notes or '',
        'subtotal': subtotal or '',
        'delivery_fee': delivery_fee or '',
        'total_amount': total_amount or ''
    }
    if items is not None:
        data['items'] = items
    files = {}
    if payment_proof_path:
        files['payment_proof'] = open(payment_proof_path, 'rb')
    try:
        resp = requests.post(url, data=data, files=files if files else None, timeout=10)
        if files:
            files['payment_proof'].close()
        # Try to parse order_id from response
        try:
            resp_json = resp.json()
            order_id = resp_json.get('order_id')
            return {'success': resp_json.get('success', False), 'order_id': order_id, 'message': resp_json.get('message')}
        except Exception:
            return {'success': False, 'message': 'Order placed, but could not parse order ID.'}
    except Exception as e:
        return {'success': False, 'message': f'Food service is currently unavailable. Error: {str(e)}'}


def get_checkout_details(order_id):
    """
    Fetch delivery/checkout details from the PHP backend for a given order_id.
    Returns a dict with order details or an error.
    """
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/checkout_details_api.php?order_id={order_id}"
    try:
        resp = requests.get(url, timeout=5)
        resp.raise_for_status()
        return resp.json()
    except Exception as e:
        return {'error': f'Could not fetch checkout details: {str(e)}'}


def get_food_order_history(user_id):
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/order_history_api.php?user_id={user_id}"
    try:
        resp = requests.get(url, timeout=5)
        print(resp.text)  # Debug: print raw response
        resp.raise_for_status()
        return resp.json()
    except Exception as e:
        return {'success': False, 'error': f'Could not fetch order history: {str(e)}'}

def cancel_food_order(order_id):
    """
    Cancel a food order by calling the PHP API.
    """
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/cancel_order_api.php"
    print(f"[DEBUG] Cancel Order API URL: {url}")
    try:
        resp = requests.post(url, json={"order_id": int(order_id)}, timeout=5, verify=False)
        return resp.json()
    except Exception as e:
        return {"success": False, "message": f"Could not cancel order: {str(e)}"}

def delete_food_order(order_id):
    """
    Delete a food order by calling the PHP API.
    """
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/delete_order_api.php"
    print(f"[DEBUG] Delete Order API URL: {url}")
    try:
        resp = requests.delete(url, json={"order_id": int(order_id)}, timeout=5, verify=False)
        return resp.json()
    except Exception as e:
        return {"success": False, "message": f"Could not delete order: {str(e)}"}

def get_food_rating(user_id, order_id, menu_item_id):
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/ratings_api.php"
    params = {
        'user_id': user_id,
        'order_id': order_id,
        'menu_item_id': menu_item_id
    }
    try:
        resp = requests.get(url, params=params, timeout=5)
        resp.raise_for_status()
        return resp.json()
    except Exception as e:
        return {'success': False, 'error': f'Could not fetch rating: {str(e)}'}

def post_food_rating(user_id, order_id, menu_item_id, rating, comment=None):
    food_service_ip = get_food_service_ip()
    url = f"http://{food_service_ip}/online-food-ordering/ratings_api.php"
    payload = {
        'user_id': user_id,
        'order_id': order_id,
        'menu_id': menu_item_id,
        'rating': rating,
        'comment': comment or ''
    }
    try:
        resp = requests.post(url, json=payload, timeout=5)
        resp.raise_for_status()
        return resp.json()
    except Exception as e:
        return {'success': False, 'error': f'Could not submit rating: {str(e)}'}