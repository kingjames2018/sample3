<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use Validator;
use App\User;
use App\UserRole;
use App\Product;
use App\Cart;
use App\Order;
use App\OrderDetail;

class MobileController extends Controller
{
    //
    public function login(Request $request) {
        $data = array();
        $arr = array();
    	$this->validate($request, [
    		'username' => 'required',
    		'password' => 'required'
    	]);
    	$user_data = array(
    		'username' => $request->get('username'),
    		'password' => $request->get('password')
    	);

    	if(Auth::attempt($user_data)) {
    		if(Auth::user()->userRoles->first()->role_id==1) {
    			$result['success'] = "0";
				$result['message'] = "This an admin account";

				return response()->json($result);
    		}
            $id = Auth::user()->user_id;
            $user = User::where('user_id', $id)->get();

                foreach($user as $row) {
                    $arr['user_id'] = $row->user_id;
                    $arr['fname'] = $row->fname;
                    $arr['lname'] = $row->lname;
                    $arr['address'] = $row->address;
                    $arr['email'] = $row->email;
                    $arr['contact'] = $row->contact;
                    $arr['username'] = $row->username;
                    $data[] = $arr;
                }
    		$result['success'] = "1";
			$result['message'] = "success";
            $result['data'] = $data;
		
			return response()->json($result);
    	} else {
    		$result['success'] = "0";
			$result['message'] = "Invalid credentials";
		
			return response()->json($result);
    	}
    }

    public function register(Request $request) {

    	$this->validate($request, [
    		'fname' => 'required',
    		'lname' => 'required',
    		'address' => 'required',
    		'email' => 'required',
    		'contact' => 'required',
    		'username' => 'required',
    		'password' => 'required'
    	]);

    	$date = date('Ymd');
        $user = new User([
            'fname' => $request->get('fname'),
            'lname' => $request->get('lname'),
            'address' => $request->get('address'),
            'email' => $request->get('email'),
            'contact' => $request->get('contact'),
            'username' => $request->get('username'),
            'password' => Hash::make($request->get('password'))
        ]);

        $user->save();

        $userRole = new UserRole([
            'user_id' => $user->user_id,
            'role_id' => 2,
            'created_date' => $date

        ]);

        $userRole->save();

        $result['success'] = "1";
		$result['message'] = "success";
		
		return response()->json($result);
    }

    public function profile(Request $request) {
        $response = array();
        $data = array();
        $id = $request->get('id');
        $user = User::where('user_id', $id)->get();
        foreach($user as $row) {
            $result['user_id'] = $row->user_id;
            $result['image'] = $row->image;
            $result['fname'] = $row->fname;
            $result['lname'] = $row->lname;
            $result['address'] = $row->address;
            $result['email'] = $row->email;
            $result['contact'] = $row->contact;
            $result['username'] = $row->username;
            $data[] = $result;
        }

        $response['success'] = "1";
        $response['data'] = $data;

        return response()->json($response);
    }

    public function editprofile(Request $request) {

        $response = array();

        $id = $request->get('id');
        $user = User::where('user_id', $id)->first();

        $image = $request->get('image');
        $filename = "$id.jpg";
        $path = "img/users/".$filename;

        if($image!="") {
            $user->image = $filename;
            $user->fname = $request->get('fname');
            $user->lname = $request->get('lname');
            $user->address = $request->get('address');
            $user->email = $request->get('email');
            $user->contact = $request->get('contact');
            $user->username = $request->get('username');

            $user->save();

            if(file_put_contents($path, base64_decode($image))) {
                $response['success'] = "1";
                $response['message'] = "success";

            return response()->json($response);
            }
        } else {
            $user->fname = $request->get('fname');
            $user->lname = $request->get('lname');
            $user->address = $request->get('address');
            $user->email = $request->get('email');
            $user->contact = $request->get('contact');
            $user->username = $request->get('username');

            $user->save();

            $response['success'] = "1";
            $response['message'] = "success";

            return response()->json($response);
        }

    }

    public function product() {
        $result = array();
        $data = array();
        $product = Product::orderBy('product_id', 'DESC')->get();

        foreach($product as $row) {
            $result['product_id'] = $row->product_id;
            $result['name'] = $row->name;
            $result['image'] = $row->image;
            $result['stockin'] = $row->stockin;
            $result['price'] = $row->price;
            $data[] = $result;
        }

        $response['success'] = "1";
        $response['data'] = $data;

        return response()->json($response);
    }

    public function productdetail(Request $request) {
        $response = array();
        $data = array();
        $id = $request->get('product_id');
        $user = Product::where('product_id', $id)->get();
        foreach($user as $row) {
            $result['product_id'] = $row->product_id;
            $result['image'] = $row->image;
            $result['name'] = $row->name;
            $result['desc'] = $row->desc;
            $result['stockin'] = $row->stockin;
            $result['price'] = $row->price;
            $data[] = $result;
        }

        $response['success'] = "1";
        $response['data'] = $data;

        return response()->json($response);
    }

    public function addtocart(Request $request) {
        $userid = $request->get('userid');
        $productid = $request->get('productid');
        $stockout = $request->get('stockout');

        $product = Product::where('product_id', $productid)->first();
        $count = Cart::where('product_id', $productid)->get();
        if($stockout > $product->stockin || $stockout == 0) {
            $response['success'] = "0";
            $response['message'] = "Invalid input";

            return response()->json($response);
        } else if(count($count)>0) {
            $response['success'] = "2";
            $response['message'] = "Item already in the cart";

            return response()->json($response);
        } else {
            $cart = new Cart([
                'user_id' =>  $userid,
                'product_id' => $productid,
                'stockout' => $stockout

            ]);

            $cart->save();

            $response['success'] = "1";
            $response['message'] = "success";

            return response()->json($response);
        }
    }

    public function cart(Request $request) {
        $data = array();
        $result = array();
        $response = array();
        $id = $request->get('id');
        $cart = Cart::where('user_id', $id)->get();

        foreach($cart as $row) {
            $data['cart_id'] = $row->cart_id;
            $data['image'] = $row->products->image;
            $data['name'] = $row->products->name;
            $data['price'] = $row->products->price;
            $data['stockout'] = $row->stockout;
            $result[] = $data;
        }

        $response['success'] = "1";
        $response['data'] = $result;

        return response()->json($response);

    }

    public function delete(Request $request) {
        $id = $request->get('id');
        $cart = Cart::where('cart_id', $id);
        $cart->delete();

        $response['success'] = "1";
        $response['message'] = "success";

        return response()->json($response);

    }

    public function checkout(Request $request) {
        $response = array();
        $id = $request->get('id');
        $date = date("Ymd");

        $order = new Order([
            'user_id' => $id,
            'order_date' => $date,
            'status_id' => 1
        ]);

        $order->save();

        $cart = Cart::where('user_id', $id)->get();

        if(count($cart)>0) {
            foreach($cart as $row) {
                $cartid = $row->cart_id;
                $orderno = $order->order_no;
                $productid = $row->product_id;
                $stockout = $row->stockout;

                $product = Product::find($productid);
                    $product->stockin = $product->stockin - $stockout;
                    $product->stockout = $product->stockout + $stockout;
                    $product->save();

                $orderDetail = new OrderDetail([
                    'order_no' => $orderno,
                    'product_id' => $productid,
                    'stockout' => $stockout
                ]);

                $orderDetail->save();

                $q = Cart::find($cartid);
                $q->delete();
            }
        }

        $response['success'] = "1";
        $response['message'] = "success";

        return response()->json($response);
    }

    public function order(Request $request) {
        $response = array();
        $data = array();
        $result = array();
        $id = $request->get('id');
        $order = Order::where('user_id', $id)->get();

        foreach($order as $row) {
            $data['order_no'] = $row->order_no;
            $data['order_date'] = $row->order_date;
            $data['status_desc'] = $row->status->status_desc;
            $result[] = $data;
        }

       
        $response['success'] = "1";
        $response['data'] = $result;

        return response()->json($response);
    }

    public function orderdetail(Request $request) {
        $response = array();
        $data = array();
        $result = array();
        $orderno = $request->get('orderno');

        $orderDetail = OrderDetail::where('order_no', $orderno)->get();

        foreach($orderDetail as $row) {
            $data['order_no'] = $row->order_no;
            $data['image'] = $row->products->image;
            $data['name'] = $row->products->name;
            $data['price'] = $row->products->price;
            $data['stockout'] = $row->products->stockout;
            $result[] = $data;
        }

        $response['success'] = "1";
        $response['data'] = $result;

        return response()->json($response);
    }
}
